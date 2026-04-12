<?php

namespace App\Console\Commands;

use App\Services\HaberAktarimService;
use Illuminate\Console\Command;

class HaberEskiSitedenAktar extends Command
{
    protected $signature = 'haber:eski-siteden-aktar
                            {--kaynak-base-url= : Relatif görsel yolları için ana URL}
                            {--kaynak-cdn-haber-url=https://arspetlojdepo.fra1.digitaloceanspaces.com/kpmedia/haber : Dosya adı gelen görseller için eski CDN haber yolu}
                            {--kaynak-lokal-root=/var/www/vhosts/kestanepazari.org.tr/httpdocs : Eski sitenin lokal web root yolu}
                            {--kaynak-lokal-gorsel-dizin=/var/www/vhosts/kestanepazari.org.tr/httpdocs/images : Eski ana görsellerin lokal dizini}
                            {--old-host=127.0.0.1 : Eski DB host}
                            {--old-port=3306 : Eski DB port}
                            {--old-database=kestanepazariorg_groiraz : Eski DB adı}
                            {--old-username= : Eski DB kullanıcı adı}
                            {--old-password= : Eski DB şifresi}
                            {--yonetici-id=1 : Aktarılan haberlerin yazar yöneticisi}
                            {--kategori-id=16 : Tüm aktarımlar için kategori id}
                            {--limit=0 : Aktarılacak kayıt limiti}
                            {--offset=0 : Başlangıç offset}
                            {--sirala=id_asc : id_asc veya tarih_desc}
                            {--ids= : Sadece belirli id listesi (virgüllü)}
                            {--dry-run : Sadece simülasyon yapar, yazmaz}
                            {--optimize-gorseller : Görselleri optimize job ile işler}';

    protected $description = 'Eski sitedeki news/news_images verilerini haberler/haber_gorselleri tablolarına aktarır.';

    public function handle(): int
    {
        $ids = collect(explode(',', (string) $this->option('ids')))
            ->map(static fn ($id) => (int) trim($id))
            ->filter(static fn ($id) => $id > 0)
            ->values()
            ->all();

        $sonuc = app(HaberAktarimService::class)->haberleriAktar([
            'kaynak_base_url' => (string) $this->option('kaynak-base-url'),
            'kaynak_cdn_haber_url' => (string) $this->option('kaynak-cdn-haber-url'),
            'kaynak_lokal_root' => (string) $this->option('kaynak-lokal-root'),
            'kaynak_lokal_gorsel_dizin' => (string) $this->option('kaynak-lokal-gorsel-dizin'),
            'old_host' => (string) $this->option('old-host'),
            'old_port' => (string) $this->option('old-port'),
            'old_database' => (string) $this->option('old-database'),
            'old_username' => (string) $this->option('old-username'),
            'old_password' => (string) $this->option('old-password'),
            'yonetici_id' => (int) $this->option('yonetici-id'),
            'kategori_id' => (int) $this->option('kategori-id'),
            'limit' => (int) $this->option('limit'),
            'offset' => (int) $this->option('offset'),
            'siralama' => (string) $this->option('sirala'),
            'sadece_idler' => $ids,
            'dry_run' => (bool) $this->option('dry-run'),
            'gorsel_optimizasyon' => (bool) $this->option('optimize-gorseller'),
        ]);

        $this->newLine();
        $this->info('Haber aktarım sonucu:');
        $this->line('Toplam: ' . ($sonuc['toplam'] ?? 0));
        $this->line('Eklenen: ' . ($sonuc['eklenen'] ?? 0));
        $this->line('Güncellenen: ' . ($sonuc['guncellenen'] ?? 0));
        $this->line('Atlanan: ' . ($sonuc['atlanan'] ?? 0));
        $this->line('Hata: ' . ($sonuc['hata'] ?? 0));
        $this->line('Görsel Hata: ' . ($sonuc['gorsel_hata'] ?? 0));

        return self::SUCCESS;
    }
}
