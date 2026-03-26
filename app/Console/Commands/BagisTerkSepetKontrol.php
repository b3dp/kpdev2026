<?php

namespace App\Console\Commands;

use App\Services\SepetService;
use Illuminate\Console\Command;

class BagisTerkSepetKontrol extends Command
{
    protected $signature = 'bagis:terk-sepet';

    protected $description = '8 saatten eski aktif sepetleri terk edildi olarak isaretle';

    public function handle(SepetService $sepetService): int
    {
        $sepetService->terkEdilenSepetleriTemizle();

        $this->info('Terk sepet kontrolu tamamlandi.');

        return self::SUCCESS;
    }
}
