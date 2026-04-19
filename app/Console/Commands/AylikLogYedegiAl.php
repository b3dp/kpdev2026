<?php

namespace App\Console\Commands;

use App\Services\YedeklemeService;
use Illuminate\Console\Command;

class AylikLogYedegiAl extends Command
{
    protected $signature = 'yedek:log-aylik';

    protected $description = 'Bir önceki ayın activity log kayıtlarını arşivler ve doğrulama sonrası temizler.';

    public function handle(YedeklemeService $yedeklemeService): int
    {
        return $yedeklemeService->aylikLogYedegiAl()
            ? self::SUCCESS
            : self::FAILURE;
    }
}