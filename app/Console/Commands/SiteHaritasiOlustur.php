<?php

namespace App\Console\Commands;

use App\Models\BagisTuru;
use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\KurumsalSayfa;
use App\Support\KurumsalStatikSayfalar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SiteHaritasiOlustur extends Command
{
    protected $signature = 'site-haritasi:olustur';

    protected $description = 'Public sitemap.xml dosyasini olusturur veya gunceller.';

    public function handle(): int
    {
        try {
            $urlKayitlari = $this->urlKayitlariniHazirla();

            $xml = new \XMLWriter();
            $xml->openMemory();
            $xml->startDocument('1.0', 'UTF-8');
            $xml->setIndent(true);

            $xml->startElement('urlset');
            $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

            foreach ($urlKayitlari as $kayit) {
                $xml->startElement('url');
                $xml->writeElement('loc', $kayit['loc']);

                if (! empty($kayit['lastmod'])) {
                    $xml->writeElement('lastmod', $kayit['lastmod']);
                }

                if (! empty($kayit['changefreq'])) {
                    $xml->writeElement('changefreq', $kayit['changefreq']);
                }

                if (! empty($kayit['priority'])) {
                    $xml->writeElement('priority', $kayit['priority']);
                }

                $xml->endElement();
            }

            $xml->endElement();
            $xml->endDocument();

            file_put_contents(public_path('sitemap.xml'), $xml->outputMemory());

            $this->info('Sitemap olusturuldu: '.public_path('sitemap.xml'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('SiteHaritasiOlustur komutunda hata', [
                'hata' => $e->getMessage(),
                'iz' => $e->getTraceAsString(),
            ]);

            $this->error('Sitemap olusturulamadi: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function urlKayitlariniHazirla(): array
    {
        $kayitlar = [];

        $this->kayitEkle($kayitlar, route('home'), now()->toAtomString(), 'daily', '1.0');
        $this->kayitEkle($kayitlar, route('haberler.index'), now()->toAtomString(), 'hourly', '0.9');
        $this->kayitEkle($kayitlar, route('etkinlikler.index'), now()->toAtomString(), 'daily', '0.8');
        $this->kayitEkle($kayitlar, route('bagis.index'), now()->toAtomString(), 'daily', '0.9');
        $this->kayitEkle($kayitlar, route('kurumsal.show'), now()->toAtomString(), 'weekly', '0.8');
        $this->kayitEkle($kayitlar, route('kurumsal.show', ['slug' => 'kurumlar']), now()->toAtomString(), 'weekly', '0.7');
        $this->kayitEkle($kayitlar, route('kurumsal.show', ['slug' => 'atolyeler']), now()->toAtomString(), 'weekly', '0.7');
        $this->kayitEkle($kayitlar, route('iletisim.index'), now()->toAtomString(), 'monthly', '0.6');
        $this->kayitEkle($kayitlar, route('mezunlar.index'), now()->toAtomString(), 'weekly', '0.6');
        $this->kayitEkle($kayitlar, route('ekayit.index'), now()->toAtomString(), 'weekly', '0.7');

        foreach (KurumsalStatikSayfalar::tumu() as $statikSayfa) {
            if (! ($statikSayfa['aktif'] ?? false)) {
                continue;
            }

            $slug = (string) ($statikSayfa['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $this->kayitEkle(
                $kayitlar,
                route('kurumsal.show', ['slug' => $slug]),
                now()->toAtomString(),
                'monthly',
                '0.6'
            );
        }

        $kurumsalSayfalar = KurumsalSayfa::query()
            ->where('durum', 'yayinda')
            ->whereNotNull('slug')
            ->get(['slug', 'updated_at']);

        foreach ($kurumsalSayfalar as $sayfa) {
            $this->kayitEkle(
                $kayitlar,
                route('kurumsal.show', ['slug' => $sayfa->slug]),
                optional($sayfa->updated_at)->toAtomString(),
                'weekly',
                '0.7'
            );
        }

        $haberler = Haber::query()
            ->where('durum', 'yayinda')
            ->whereNotNull('slug')
            ->get(['slug', 'updated_at', 'yayin_tarihi']);

        foreach ($haberler as $haber) {
            $lastmod = optional($haber->updated_at ?: $haber->yayin_tarihi)->toAtomString();

            $this->kayitEkle(
                $kayitlar,
                route('haberler.show', ['slug' => $haber->slug]),
                $lastmod,
                'daily',
                '0.8'
            );
        }

        $etkinlikler = Etkinlik::query()
            ->where('durum', 'yayinda')
            ->whereNotNull('slug')
            ->get(['slug', 'updated_at']);

        foreach ($etkinlikler as $etkinlik) {
            $this->kayitEkle(
                $kayitlar,
                route('etkinlikler.show', ['slug' => $etkinlik->slug]),
                optional($etkinlik->updated_at)->toAtomString(),
                'weekly',
                '0.7'
            );
        }

        $bagisTurleri = BagisTuru::query()
            ->where('aktif', true)
            ->whereNotNull('slug')
            ->get(['slug', 'updated_at']);

        foreach ($bagisTurleri as $bagisTuru) {
            $this->kayitEkle(
                $kayitlar,
                route('bagis.show', ['slug' => $bagisTuru->slug]),
                optional($bagisTuru->updated_at)->toAtomString(),
                'weekly',
                '0.8'
            );
        }

        return array_values($kayitlar);
    }

    private function kayitEkle(array &$kayitlar, string $loc, ?string $lastmod, string $changefreq, string $priority): void
    {
        $anahtar = rtrim($loc, '/');

        $kayitlar[$anahtar] = [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
