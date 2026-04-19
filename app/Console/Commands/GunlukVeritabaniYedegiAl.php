<?php

namespace App\Console\Commands;

use App\Services\YedeklemeService;
use Illuminate\Console\Command;

class GunlukVeritabaniYedegiAl extends Command
{
    protected $signature = 'yedek:db-gunluk';

    protected $description = 'Günlük veritabanı yedeğini alır, doğrular ve Spaces üzerinde retention uygular.';

    public function handle(YedeklemeService $yedeklemeService): int
    {
        return $yedeklemeService->gunlukVeritabaniYedegiAl()
            ? self::SUCCESS
            : self::FAILURE;
    }
}