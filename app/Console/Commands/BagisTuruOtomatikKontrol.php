<?php

namespace App\Console\Commands;

use App\Services\HicriTakvimService;
use Illuminate\Console\Command;

class BagisTuruOtomatikKontrol extends Command
{
    protected $signature = 'bagis:hicri-kontrol';

    protected $description = 'Hicri tarihe gore otomatik bagis turlerini ac/kapat';

    public function handle(HicriTakvimService $hicriTakvimService): int
    {
        $acilan = $hicriTakvimService->acilacakTurleriAc();
        $kapanan = $hicriTakvimService->kapanacakTurleriKapat();

        $this->info("Acilan: {$acilan}, Kapanan: {$kapanan}");

        return self::SUCCESS;
    }
}
