<?php

namespace App\Jobs;

use App\Models\Bagis;
use App\Models\Yonetici;
use App\Services\ZeptomailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MakbuzOlusturJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(public readonly Bagis $bagis)
    {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function handle(ZeptomailService $zeptomailService): void
    {
        $bagis = Bagis::query()
            ->with(['kalemler.bagisTuru', 'kisiler'])
            ->findOrFail($this->bagis->getKey());

        $bagisci = $bagis->odeyenKisi() ?? $bagis->kisiler->first();
        $tempDizin = storage_path('app/private/tmp');

        if (! is_dir($tempDizin)) {
            mkdir($tempDizin, 0755, true);
        }

        $tempDosyaYolu = $tempDizin.'/'.sprintf('bagis-makbuz-%s.pdf', $bagis->id);
        $spacesRoot = trim((string) config('filesystems.disks.spaces.root', ''), '/');
        $relativeYol = sprintf('pdf26/bagis/%s/%s-makbuz.pdf', $bagis->odeme_tarihi?->format('Y') ?? now()->format('Y'), $bagis->bagis_no);
        $kaydedilecekYol = $spacesRoot !== '' ? $spacesRoot.'/'.$relativeYol : $relativeYol;

        $logoDataUri = null;
        $logoYolu = public_path('images/logo-kare.png');
        if (is_file($logoYolu)) {
            $logoDataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoYolu));
        }

        try {
            $pdf = Pdf::loadView('pdf.bagis-makbuz', [
                'bagis' => $bagis,
                'bagisci' => $bagisci,
                'logoDataUri' => $logoDataUri,
            ])->setPaper('a4');

            file_put_contents($tempDosyaYolu, $pdf->output());

            $yuklendi = Storage::disk('spaces')->put($relativeYol, (string) file_get_contents($tempDosyaYolu), 'public');

            if (! $yuklendi) {
                throw new \RuntimeException('Makbuz PDF DigitalOcean Spaces alanina yuklenemedi.');
            }

            $bagis->forceFill([
                'makbuz_yol' => $kaydedilecekYol,
            ])->save();

            if ($bagisci?->eposta) {
                $bagisTuru = $bagis->kalemler->first()?->bagisTuru;

                $gonderildi = $zeptomailService->makbuzGonder(
                    eposta: $bagisci->eposta,
                    ad: $bagisci->ad_soyad ?? 'Bağışçı',
                    makbuzUrl: $bagis->fresh()->makbuzUrl() ?? '',
                    bagisNo: $bagis->bagis_no,
                    tutar: (string) $bagis->toplam_tutar,
                    tarih: $bagis->odeme_tarihi ?? now(),
                    bagisSlug: $bagisTuru?->slug ?? 'bagis',
                    gorselUrl: $bagisTuru?->gorsel_orijinal
                        ? Storage::disk('spaces')->url($bagisTuru->gorsel_orijinal)
                        : null,
                );

                if ($gonderildi) {
                    $bagis->forceFill([
                        'makbuz_gonderildi' => true,
                    ])->save();
                }
            }

            activity('bagis_makbuz')
                ->performedOn($bagis)
                ->withProperties([
                    'bagis_no' => $bagis->bagis_no,
                    'makbuz_yol' => $kaydedilecekYol,
                ])
                ->log('Bağış makbuzu oluşturuldu.');
        } finally {
            if (is_file($tempDosyaYolu)) {
                unlink($tempDosyaYolu);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        activity('bagis_makbuz')
            ->withProperties([
                'bagis_id' => $this->bagis->getKey(),
                'bagis_no' => $this->bagis->bagis_no,
                'hata' => $exception?->getMessage(),
            ])
            ->log('Bağış makbuzu oluşturma işi başarısız oldu.');

        $alicilar = Yonetici::query()
            ->where('aktif', true)
            ->whereNotNull('eposta')
            ->get(['ad_soyad as ad', 'eposta'])
            ->map(fn (Yonetici $yonetici) => ['ad' => $yonetici->ad, 'eposta' => $yonetici->eposta])
            ->all();

        if ($alicilar !== []) {
            app(ZeptomailService::class)->yoneticiAlertGonder(
                $alicilar,
                'Bağış Makbuz Oluşturma Hatası',
                sprintf('Bağış No: %s\nHata: %s', $this->bagis->bagis_no, $exception?->getMessage() ?? 'Bilinmeyen hata')
            );
        }
    }
}