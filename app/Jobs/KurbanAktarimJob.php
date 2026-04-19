<?php

namespace App\Jobs;

use App\Models\Bagis;
use App\Services\KurbanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class KurbanAktarimJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $bagisId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            $bagis = Bagis::query()
                ->with(['kalemler.bagisTuru', 'kisiler'])
                ->find($this->bagisId);

            if (! $bagis) {
                throw new \RuntimeException('Bağış kaydı bulunamadı.');
            }

            app(KurbanService::class)->bagisAktar($bagis);
        } catch (Throwable $exception) {
            Log::error('KurbanAktarimJob başarısız.', [
                'bagis_id' => $this->bagisId,
                'hata' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}