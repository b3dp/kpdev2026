<?php

namespace App\Console\Commands;

use App\Services\YedeklemeService;
use Illuminate\Console\Command;

class AylikVeritabaniYedegiAl extends Command
{
    protected $signature = 'yedek:db-aylik';

    protected $description = 'Aylık veritabanı yedeğini alır, doğrular ve Spaces üzerinde retention uygular.';

    public function handle(YedeklemeService $yedeklemeService): int
    {
        return $yedeklemeService->aylikVeritabaniYedegiAl()
            ? self::SUCCESS
            : self::FAILURE;
    }
}