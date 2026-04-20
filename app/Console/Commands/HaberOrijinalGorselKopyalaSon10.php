<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Haber;

class HaberOrijinalGorselKopyalaSon10 extends Command
{
    protected $signature = 'haber:orijinal-gorsel-kopyala-son10';
    protected $description = 'Son 10 haberin orijinal görsellerini tmp/haberler klasörüne kopyalar';

    public function handle()
    {
        $this->info('Son 10 haberin orijinal görselleri kopyalanıyor...');
        $haberler = Haber::orderBy('created_at', 'desc')->take(10)->get();
        foreach ($haberler as $haber) {
            if ($haber->gorsel_orijinal) {
                $slug = $haber->slug ?: 'haber-' . $haber->id;
                $url = $haber->gorsel_orijinal;
                $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $dest = base_path('tmp/haberler/' . $slug . '-ana-orijinal.' . $ext);
                try {
                    file_put_contents($dest, file_get_contents($url));
                    $this->info("Kopyalandı: $dest");
                } catch (\Exception $e) {
                    $this->error("Hata: $dest - " . $e->getMessage());
                }
            }
        }
        $this->info('İşlem tamamlandı.');
        return 0;
    }
}
