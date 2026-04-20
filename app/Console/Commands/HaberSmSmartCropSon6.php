<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\GorselOptimizeJob;
use App\Models\Haber;

class HaberSmSmartCropSon6 extends Command
{
    protected $signature = 'haber:sm-smart-crop-son6';
    protected $description = 'Son 6 haberin SM görselini smart crop ile yeniden oluşturur (tmp/haberler dizininden)';

    public function handle()
    {
        $this->info('Son 6 haber için SM smart crop başlatılıyor...');
        $haberler = Haber::orderBy('created_at', 'desc')->take(6)->get();
        foreach ($haberler as $haber) {
            $slug = $haber->slug ?: 'haber-' . $haber->id;
            $orijinalPath = 'tmp/haberler/' . $slug . '-ana-orijinal.jpeg';
            if (!file_exists(base_path($orijinalPath))) {
                $this->error("Dosya yok: $orijinalPath");
                continue;
            }
            dispatch(new GorselOptimizeJob($haber->id, 'haber', 'ana_gorsel', $orijinalPath));
            $this->info("Job queued for: {$haber->id} ({$slug})");
        }
        $this->info('Tüm işler kuyruğa eklendi.');
        return 0;
    }
}
