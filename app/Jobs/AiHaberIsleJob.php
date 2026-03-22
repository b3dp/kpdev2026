<?php

namespace App\Jobs;

use App\Models\Haber;
use App\Models\Kisi;
use App\Models\Kurum;
use App\Services\GeminiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class AiHaberIsleJob implements ShouldQueue
{
    use Queueable;

    public string $queue = 'default';

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public int $haberId)
    {
    }

    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function handle(GeminiService $geminiService): void
    {
        $haber = Haber::query()->find($this->haberId);

        if (! $haber || blank($haber->icerik)) {
            return;
        }

        $duzeltilmisMetin = $geminiService->imlaDuzelt((string) $haber->icerik);
        $ozet = filled($haber->ozet) ? $haber->ozet : $geminiService->ozetUret($duzeltilmisMetin);
        $metaDescription = filled($haber->meta_description) ? $haber->meta_description : $geminiService->metaDescriptionUret($duzeltilmisMetin);

        $haber->update([
            'icerik' => $duzeltilmisMetin,
            'ozet' => $ozet,
            'meta_description' => $metaDescription,
            'ai_islendi' => true,
        ]);

        $kisiSonuclar = $geminiService->kisiTespitEt($duzeltilmisMetin);
        foreach ($kisiSonuclar as $kisiVerisi) {
            $adSoyad = trim((string) ($kisiVerisi['ad_soyad'] ?? ''));
            if (! filled($adSoyad)) {
                continue;
            }

            $parcalar = preg_split('/\s+/', $adSoyad);
            $ad = $parcalar[0] ?? null;
            $soyad = count($parcalar) > 1 ? implode(' ', array_slice($parcalar, 1)) : null;

            if (! filled($ad) || ! filled($soyad)) {
                continue;
            }

            $kisi = Kisi::query()->firstOrCreate(
                ['ad' => $ad, 'soyad' => $soyad],
                ['ai_onaylandi' => false]
            );

            DB::table('haber_kisiler')->updateOrInsert(
                ['haber_id' => $haber->id, 'kisi_id' => $kisi->id],
                [
                    'rol' => $kisiVerisi['rol'] ?? null,
                    'onay_durumu' => 'beklemede',
                    'updated_at' => now(),
                    'created_at' => now(),
                    'deleted_at' => null,
                ]
            );
        }

        $kurumSonuclar = $geminiService->kurumTespitEt($duzeltilmisMetin);
        foreach ($kurumSonuclar as $kurumVerisi) {
            $ad = trim((string) ($kurumVerisi['ad'] ?? ''));

            if (! filled($ad)) {
                continue;
            }

            $kurum = Kurum::query()->firstOrCreate(
                ['ad' => $ad],
                ['tip' => 'diger', 'aktif' => false]
            );

            DB::table('haber_kurumlar')->updateOrInsert(
                ['haber_id' => $haber->id, 'kurum_id' => $kurum->id],
                [
                    'onay_durumu' => 'beklemede',
                    'updated_at' => now(),
                    'created_at' => now(),
                    'deleted_at' => null,
                ]
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        $haber = Haber::query()->find($this->haberId);
        if ($haber) {
            $haber->update(['ai_islendi' => false]);
        }

        activity('ai_haber_isleme_hata')
            ->withProperties([
                'haber_id' => $this->haberId,
                'hata' => $exception->getMessage(),
            ])
            ->log('AI haber işleme job başarısız oldu');
    }
}
