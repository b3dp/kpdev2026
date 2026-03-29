<?php

namespace App\Jobs;

use App\Enums\EkayitDurumu;
use App\Models\EkayitKayit;
use App\Services\ZeptomailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EkayitDurumEpostasiJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 3;

    public function __construct(
        public int $ekayitKayitId,
        public string $durum,
    ) {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function handle(ZeptomailService $zeptomailService): void
    {
        $kayit = EkayitKayit::query()
            ->with(['sinif.kurum', 'ogrenciBilgisi', 'veliBilgisi'])
            ->find($this->ekayitKayitId);

        if (! $kayit || blank($kayit->veliBilgisi?->eposta)) {
            return;
        }

        $durumEnum = EkayitDurumu::tryFrom($this->durum);

        $zeptomailService->ekayitDurumGonder(
            eposta: (string) $kayit->veliBilgisi->eposta,
            ad: (string) ($kayit->veliBilgisi->ad_soyad ?? 'Veli'),
            ogrenciAdSoyad: (string) ($kayit->ogrenciBilgisi?->ad_soyad ?? ''),
            sinif: (string) ($kayit->sinif?->ad ?? ''),
            kurum: (string) ($kayit->sinif?->kurum?->ad ?? ''),
            durum: $durumEnum?->label() ?? (string) $this->durum,
            durumNotu: $kayit->durum_notu,
        );
    }
}
