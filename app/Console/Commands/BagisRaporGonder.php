<?php

namespace App\Console\Commands;

use App\Enums\BagisDurumu;
use App\Enums\RaporPeriyot;
use App\Exports\BagisExport;
use App\Models\Bagis;
use App\Models\BagisOtomatikRapor;
use App\Services\GoogleDriveService;
use App\Services\ZeptomailService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class BagisRaporGonder extends Command
{
    protected $signature = 'bagis:rapor-gonder {periyot : gunluk|haftalik|aylik} {--tarih=dun : dun|bugun|test}';

    protected $description = 'Bağış raporlarını Excel olarak üretir, Drivea yükler ve e-posta ile gönderir.';

    public function handle(GoogleDriveService $googleDriveService, ZeptomailService $zeptomailService): int
    {
        $periyot = RaporPeriyot::tryFrom((string) $this->argument('periyot'));
        $tarihSecenegi = strtolower((string) $this->option('tarih'));

        if (! $periyot) {
            $this->error('Geçersiz periyot.');
            return self::FAILURE;
        }

        if (! in_array($tarihSecenegi, ['dun', 'bugun', 'test'], true)) {
            $this->error('Geçersiz tarih seçeneği. Sadece dun, bugun veya test kullanılabilir.');

            return self::FAILURE;
        }

        $raporAyari = BagisOtomatikRapor::query()
            ->where('periyot', $periyot->value)
            ->where('aktif', true)
            ->first();

        if (! $raporAyari) {
            $this->info('Aktif rapor ayarı bulunamadı.');
            return self::SUCCESS;
        }

        $alicilar = collect($raporAyari->alicilar)->filter()->values();

        if ($alicilar->isEmpty()) {
            $this->info('Alıcı listesi boş, işlem atlandı.');
            return self::SUCCESS;
        }

        [$baslangic, $bitis] = $this->tarihAraligiHesapla($tarihSecenegi);
        $dosyaAdi = GoogleDriveService::dosyaAdiUret('bagis', $baslangic->format('dmY'), $bitis->format('dmY'));
        $tempDizin = storage_path('app/private/tmp');
        $tempDosyaYolu = $tempDizin.'/'.$dosyaAdi;

        if (! is_dir($tempDizin)) {
            mkdir($tempDizin, 0755, true);
        }

        try {
            $bagislar = Bagis::query()
                ->with(['kalemler.bagisTuru', 'kisiler'])
                ->where('durum', BagisDurumu::Odendi->value)
                ->whereBetween('odeme_tarihi', [$baslangic, $bitis])
                ->orderByDesc('odeme_tarihi')
                ->get();

            (new BagisExport($bagislar))->saveToFile($tempDosyaYolu);

            // Drive yüklemesi opsiyonel — başarısız olursa hata fırlatma
            $driveUrl = null;
            $anaKlasorId = (string) config('services.google_drive.bagis_klasor_id');
            if ($anaKlasorId !== '') {
                try {
                    $driveUrl = $googleDriveService->excelYukle($anaKlasorId, $tempDosyaYolu, $dosyaAdi, $bitis);
                } catch (Throwable $driveException) {
                    \Illuminate\Support\Facades\Log::warning('Drive yüklemesi atlandı: '.$driveException->getMessage());
                    $this->warn('Drive yüklemesi atlandı: '.$driveException->getMessage());
                }
            }

            $tarihAraligi = $baslangic->format('d.m.Y').' - '.$bitis->format('d.m.Y');

            foreach ($alicilar as $alici) {
                $zeptomailService->bagisRaporGonder(
                    eposta: (string) $alici,
                    ad: (string) $alici,
                    dosyaYolu: $tempDosyaYolu,
                    dosyaAdi: $dosyaAdi,
                    driveUrl: $driveUrl ?? '',
                    periyot: $periyot->label(),
                    tarihAraligi: $tarihAraligi,
                );
            }

            $raporAyari->forceFill([
                'son_gonderim' => now(),
            ])->save();

            activity('bagis_rapor')
                ->performedOn($raporAyari)
                ->withProperties([
                    'periyot' => $periyot->value,
                    'baslangic' => $baslangic->toIso8601String(),
                    'bitis' => $bitis->toIso8601String(),
                    'kayit_sayisi' => $bagislar->count(),
                    'drive_url' => $driveUrl,
                ])
                ->log('Bağış raporu gönderildi.');

            $this->info('Bağış raporu başarıyla gönderildi.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            activity('bagis_rapor')
                ->performedOn($raporAyari)
                ->withProperties([
                    'periyot' => $periyot->value,
                    'hata' => $exception->getMessage(),
                ])
                ->log('Bağış raporu gönderimi başarısız oldu.');

            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if (is_file($tempDosyaYolu)) {
                unlink($tempDosyaYolu);
            }
        }
    }

    private function tarihAraligiHesapla(string $tarihSecenegi): array
    {
        return match ($tarihSecenegi) {
            'dun' => [
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
            ],
            'bugun' => [
                Carbon::today()->startOfDay(),
                Carbon::now(),
            ],
            'test' => [
                Carbon::now()->subDays(30)->startOfDay(),
                Carbon::now(),
            ],
        };
    }
}