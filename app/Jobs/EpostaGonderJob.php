<?php

namespace App\Jobs;

use App\Models\EpostaGonderim;
use App\Services\ZeptomailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class EpostaGonderJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 3;

    public function __construct(
        public int $gonderimId,
        public string $icerik,
        public string $aliciEposta,
        public string $aliciAd,
        public string $konu,
    ) {}

    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function handle(ZeptomailService $zeptomail): void
    {
        $zeptomail->apiGonder(
            gonderimId: $this->gonderimId,
            icerik: $this->icerik,
            aliciEposta: $this->aliciEposta,
            aliciAd: $this->aliciAd,
            konu: $this->konu,
        );
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('EpostaGonderJob kalıcı olarak başarısız', [
            'gonderim_id'  => $this->gonderimId,
            'alici_eposta' => $this->aliciEposta,
            'hata'         => $exception?->getMessage(),
        ]);

        EpostaGonderim::where('id', $this->gonderimId)->update([
            'durum'       => 'basarisiz',
            'hata_mesaji' => mb_substr($exception?->getMessage() ?? 'Bilinmeyen hata', 0, 500),
        ]);
    }
}
