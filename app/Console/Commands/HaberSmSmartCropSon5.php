<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Haber;
use App\Jobs\GorselOptimizeJob;

class HaberSmSmartCropSon5 extends Command
{
    protected $signature = 'haber:sm-smart-crop-son5';
    protected $description = 'Son 5 haberin SM görselini smart crop ile yeniden oluşturur';

    public function handle()
    {
        $this->info('Son 5 haber için SM smart crop başlatılıyor...');
        $haberler = Haber::orderBy('created_at', 'desc')->take(5)->get();
        foreach ($haberler as $haber) {
            $slug = $haber->slug ?: 'haber-' . $haber->id;
            $orijinalPath = "tmp/haberler/{$slug}-ana-orijinal.jpg";
            dispatch(new GorselOptimizeJob($haber->id, 'haber', 'ana_gorsel', $orijinalPath));
            $this->info("Job queued for: {$haber->id} ({$slug})");
        }
        $this->info('Tüm işler kuyruğa eklendi.');
        return 0;
    }
}
