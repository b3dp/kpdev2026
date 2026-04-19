<?php

namespace App\Jobs;

use App\Services\ModulRolBildirimService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModulRolBildirimJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $modul,
        public int $kayitId,
    ) {
        $this->onQueue('default');
    }

    public function handle(ModulRolBildirimService $modulRolBildirimService): void
    {
        try {
            match ($this->modul) {
                'mezun' => $modulRolBildirimService->mezunKaydiOlustu($this->kayitId),
                'kurban' => $modulRolBildirimService->kurbanKaydiOlustu($this->kayitId),
                default => throw new \InvalidArgumentException('Desteklenmeyen modül bildirimi: '.$this->modul),
            };
        } catch (Throwable $exception) {
            Log::error('Modül rol bildirim işi başarısız.', [
                'modul' => $this->modul,
                'kayit_id' => $this->kayitId,
                'hata' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}