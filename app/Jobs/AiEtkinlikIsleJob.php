<?php

namespace App\Jobs;

use App\Models\Etkinlik;
use App\Services\GeminiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class AiEtkinlikIsleJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public readonly int $etkinlikId)
    {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function handle(GeminiService $geminiService): void
    {
        $etkinlik = Etkinlik::query()->find($this->etkinlikId);

        if (! $etkinlik) {
            return;
        }

        $metin = trim((string) ($etkinlik->aciklama ?? $etkinlik->ozet ?? $etkinlik->baslik));

        if ($metin === '') {
            $etkinlik->update(['ai_islendi' => true]);
            return;
        }

        $duzeltilmis = $geminiService->imlaDuzelt($metin);
        $ozet = filled($etkinlik->ozet) ? $etkinlik->ozet : $geminiService->ozetUret($duzeltilmis);
        $metaDescription = filled($etkinlik->meta_description)
            ? $etkinlik->meta_description
            : $geminiService->metaDescriptionUret($duzeltilmis);
        $seoBaslik = filled($etkinlik->seo_baslik)
            ? $etkinlik->seo_baslik
            : $geminiService->seoBaslikUret((string) $etkinlik->baslik);

        $etkinlik->update([
            'ozet' => $ozet,
            'meta_description' => $metaDescription,
            'seo_baslik' => $seoBaslik,
            'ai_islendi' => true,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $etkinlik = Etkinlik::query()->find($this->etkinlikId);
        if ($etkinlik) {
            $etkinlik->update(['ai_islendi' => false]);
        }

        activity('ai_etkinlik_isleme_hata')
            ->withProperties([
                'etkinlik_id' => $this->etkinlikId,
                'hata' => $exception->getMessage(),
            ])
            ->log('AI etkinlik işleme job başarısız oldu');
    }
}
