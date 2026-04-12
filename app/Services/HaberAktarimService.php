<?php

namespace App\Services;

use App\Enums\HaberDurumu;
use App\Jobs\GorselOptimizeJob;
use App\Models\Haber;
use App\Models\HaberGorseli;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class HaberAktarimService
{
    public function haberleriAktar(array $secenekler): array
    {
        try {
            $kaynakBaglanti = $this->kaynakBaglantisiniOlustur($secenekler);
            $kaynakBaseUrl = rtrim((string) ($secenekler['kaynak_base_url'] ?? ''), '/');
            $kaynakCdnHaberUrl = rtrim((string) ($secenekler['kaynak_cdn_haber_url'] ?? 'https://arspetlojdepo.fra1.digitaloceanspaces.com/kpmedia/haber'), '/');
            $kaynakLokalRoot = rtrim((string) ($secenekler['kaynak_lokal_root'] ?? '/var/www/vhosts/kestanepazari.org.tr/httpdocs'), '/');
            $kaynakLokalGorselDizin = rtrim((string) ($secenekler['kaynak_lokal_gorsel_dizin'] ?? '/var/www/vhosts/kestanepazari.org.tr/httpdocs/images'), '/');
            $yoneticiId = (int) ($secenekler['yonetici_id'] ?? 1);
            $kategoriId = (int) ($secenekler['kategori_id'] ?? 16);
            $limit = (int) ($secenekler['limit'] ?? 0);
            $offset = (int) ($secenekler['offset'] ?? 0);
            $dryRun = (bool) ($secenekler['dry_run'] ?? false);
            $gorselOptimizasyon = (bool) ($secenekler['gorsel_optimizasyon'] ?? true);
            $siralama = (string) ($secenekler['siralama'] ?? 'id_asc');
            $sadeceIdler = (array) ($secenekler['sadece_idler'] ?? []);

            $sorgu = $kaynakBaglanti->table('news');

            if ($siralama === 'tarih_desc') {
                $sorgu->orderByRaw('COALESCE(release_date, created_at, updated_at) DESC')->orderByDesc('id');
            } else {
                $sorgu->orderBy('id');
            }

            if ($offset > 0) {
                $sorgu->offset($offset);
            }

            if ($limit > 0) {
                $sorgu->limit($limit);
            }

            if ($sadeceIdler !== []) {
                $sorgu->whereIn('id', $sadeceIdler);
            }

            $kaynakHaberler = $sorgu->get();

            $istatistik = [
                'toplam' => $kaynakHaberler->count(),
                'eklenen' => 0,
                'guncellenen' => 0,
                'atlanan' => 0,
                'hata' => 0,
                'gorsel_hata' => 0,
            ];

            foreach ($kaynakHaberler as $kaynakHaber) {
                try {
                    $legacyKaynakId = (int) $kaynakHaber->id;
                    $legacyReferans = 'legacy-news-id:' . $legacyKaynakId;
                    $mevcutHaber = Haber::query()
                        ->where('legacy_kaynak_id', $legacyKaynakId)
                        ->orWhere('canonical_url', $legacyReferans)
                        ->first();

                    $slugTemel = (string) ($kaynakHaber->slug ?: $kaynakHaber->title ?: 'haber-' . $kaynakHaber->id);
                    $slug = $this->benzersizSlugOlustur($slugTemel, $mevcutHaber?->id);

                    $icerik = $this->icerigiTemizle((string) ($kaynakHaber->description ?? ''));
                    $ozet = $this->ozetiHazirla($kaynakHaber->small_description, $icerik);
                    $durum = $this->durumuEsle($kaynakHaber->status, $kaynakHaber->release_date);
                    $yayinTarihi = $this->tarihiDonustur($kaynakHaber->release_date);

                    if ($dryRun) {
                        continue;
                    }

                    $veriler = [
                        'yonetici_id' => $yoneticiId,
                        'baslik' => mb_substr((string) ($kaynakHaber->title ?: 'Başlıksız Haber'), 0, 100, 'UTF-8'),
                        'seo_baslik' => mb_substr((string) ($kaynakHaber->title ?: 'Başlıksız Haber'), 0, 60, 'UTF-8'),
                        'slug' => $slug,
                        'ozet' => $ozet,
                        'icerik' => $icerik,
                        'durum' => $durum,
                        'oncelik' => 'normal',
                        'kategori_id' => $kategoriId,
                        'manset' => false,
                        'yayin_tarihi' => $yayinTarihi,
                        'meta_description' => $this->metaDescriptionHazirla($ozet),
                        'robots' => 'index',
                        'canonical_url' => null,
                        'legacy_kaynak_id' => $legacyKaynakId,
                        'ai_islendi' => false,
                        'ai_islem_yuzde' => 0,
                        'ai_islem_adim' => null,
                        'ai_onay' => false,
                    ];

                    if ($mevcutHaber) {
                        $mevcutHaber->update($veriler);
                        $haber = $mevcutHaber->fresh();
                    } else {
                        $haber = Haber::query()->create($veriler);
                    }

                    if ($mevcutHaber) {
                        $istatistik['guncellenen']++;
                    } else {
                        $istatistik['eklenen']++;
                    }

                    $this->haberGorselleriniAktar(
                        haber: $haber,
                        kaynakBaglanti: $kaynakBaglanti,
                        kaynakHaberId: (int) $kaynakHaber->id,
                        kaynakAnaGorsel: (string) ($kaynakHaber->image ?? ''),
                        kaynakBaseUrl: $kaynakBaseUrl,
                        kaynakCdnHaberUrl: $kaynakCdnHaberUrl,
                        kaynakLokalRoot: $kaynakLokalRoot,
                        kaynakLokalGorselDizin: $kaynakLokalGorselDizin,
                        gorselOptimizasyon: $gorselOptimizasyon,
                        istatistik: $istatistik,
                    );
                } catch (Throwable $e) {
                    $istatistik['hata']++;

                    Log::error('HaberAktarimService@haberleriAktar kayit hatasi', [
                        'kaynak_haber_id' => $kaynakHaber->id ?? null,
                        'mesaj' => $e->getMessage(),
                        'satir' => $e->getLine(),
                    ]);
                }
            }

            return $istatistik;
        } catch (Throwable $e) {
            Log::error('HaberAktarimService@haberleriAktar genel hata', [
                'mesaj' => $e->getMessage(),
                'satir' => $e->getLine(),
            ]);

            return [
                'toplam' => 0,
                'eklenen' => 0,
                'guncellenen' => 0,
                'atlanan' => 0,
                'hata' => 1,
                'gorsel_hata' => 0,
            ];
        }
    }

    private function haberGorselleriniAktar(
        Haber $haber,
        ConnectionInterface $kaynakBaglanti,
        int $kaynakHaberId,
        string $kaynakAnaGorsel,
        string $kaynakBaseUrl,
        string $kaynakCdnHaberUrl,
        string $kaynakLokalRoot,
        string $kaynakLokalGorselDizin,
        bool $gorselOptimizasyon,
        array &$istatistik
    ): void {
        try {
            $anaGorselUrl = $this->gorselKaynakUrliniOlustur(
                hamYol: $kaynakAnaGorsel,
                kaynakBaseUrl: $kaynakBaseUrl,
                kaynakCdnHaberUrl: $kaynakCdnHaberUrl,
                kaynakLokalRoot: $kaynakLokalRoot,
                kaynakLokalGorselDizin: $kaynakLokalGorselDizin,
            );

            if (filled($anaGorselUrl)) {
                $anaGeciciYol = $this->gorseliGeciciyeKaydet($anaGorselUrl, $haber->id, 0);

                if ($anaGeciciYol) {
                    if ($gorselOptimizasyon) {
                        dispatch_sync(new GorselOptimizeJob($haber->id, 'haber', 'ana_gorsel', $anaGeciciYol, 1));
                    } else {
                        $haber->update([
                            'gorsel_orijinal' => $anaGorselUrl,
                            'gorsel_lg' => $anaGorselUrl,
                            'gorsel_og' => $anaGorselUrl,
                            'gorsel_sm' => $anaGorselUrl,
                            'gorsel_mobil_lg' => $anaGorselUrl,
                        ]);
                    }

                    Storage::disk('local')->delete($anaGeciciYol);
                } else {
                    $istatistik['gorsel_hata']++;
                }
            }

            $galeriKayitlari = $kaynakBaglanti->table('news_images')
                ->where('news_id', $kaynakHaberId)
                ->orderByRaw('COALESCE(sort, 999999), id')
                ->get();

            $siralanmis = 0;

            foreach ($galeriKayitlari as $galeriKaydi) {
                $galeriUrl = $this->gorselKaynakUrliniOlustur(
                    hamYol: (string) ($galeriKaydi->image ?? ''),
                    kaynakBaseUrl: $kaynakBaseUrl,
                    kaynakCdnHaberUrl: $kaynakCdnHaberUrl,
                    kaynakLokalRoot: $kaynakLokalRoot,
                    kaynakLokalGorselDizin: $kaynakLokalGorselDizin,
                );

                if (blank($galeriUrl)) {
                    continue;
                }

                $siralanmis++;
                $sira = (int) ($galeriKaydi->sort ?: $siralanmis);
                $geciciYol = $this->gorseliGeciciyeKaydet($galeriUrl, $haber->id, $sira);

                if (! $geciciYol) {
                    $istatistik['gorsel_hata']++;
                    continue;
                }

                if ($gorselOptimizasyon) {
                    dispatch_sync(new GorselOptimizeJob($haber->id, 'haber', 'galeri_gorseli', $geciciYol, $sira));
                } else {
                    HaberGorseli::query()->updateOrCreate(
                        ['haber_id' => $haber->id, 'sira' => $sira],
                        [
                            'orijinal_yol' => $galeriUrl,
                            'lg_yol' => $galeriUrl,
                            'og_yol' => $galeriUrl,
                            'sm_yol' => $galeriUrl,
                        ]
                    );
                }

                Storage::disk('local')->delete($geciciYol);
            }
        } catch (Throwable $e) {
            $istatistik['gorsel_hata']++;

            Log::error('HaberAktarimService@haberGorselleriniAktar hatasi', [
                'haber_id' => $haber->id,
                'mesaj' => $e->getMessage(),
                'satir' => $e->getLine(),
            ]);
        }
    }

    private function gorselKaynakUrliniOlustur(
        string $hamYol,
        string $kaynakBaseUrl,
        string $kaynakCdnHaberUrl,
        string $kaynakLokalRoot,
        string $kaynakLokalGorselDizin,
    ): ?string
    {
        $hamYol = trim($hamYol);
        $varsayilanEskiSiteBaseUrl = 'https://kestanepazari.org.tr';

        if ($hamYol === '') {
            return null;
        }

        $adaylar = [];

        if (Str::startsWith($hamYol, ['http://', 'https://'])) {
            $adaylar[] = $hamYol;

            $urlYolu = (string) (parse_url($hamYol, PHP_URL_PATH) ?: '');
            $dosyaAdi = basename($urlYolu);

            if ($dosyaAdi !== '' && $dosyaAdi !== '/') {
                $eskiSiteBaseUrl = $kaynakBaseUrl !== '' ? $kaynakBaseUrl : $varsayilanEskiSiteBaseUrl;

                $adaylar[] = $eskiSiteBaseUrl . '/images/news/' . $dosyaAdi;
                $adaylar[] = $eskiSiteBaseUrl . '/images/' . $dosyaAdi;
                $adaylar[] = $eskiSiteBaseUrl . '/news/' . $dosyaAdi;

                if (str_contains($urlYolu, '/kpmedia/haber/images/news/')) {
                    $adaylar[] = $eskiSiteBaseUrl . '/images/news/' . $dosyaAdi;
                }

                if (str_contains($urlYolu, '/kpmedia/haber/news/')) {
                    $adaylar[] = $eskiSiteBaseUrl . '/images/news/' . $dosyaAdi;
                    $adaylar[] = $eskiSiteBaseUrl . '/news/' . $dosyaAdi;
                }
            }
        } elseif (Str::startsWith($hamYol, '//')) {
            $adaylar[] = 'https:' . $hamYol;
        } else {
            $temizYol = ltrim($hamYol, '/');
            $dosyaAdi = basename($temizYol);

            $lokalAdaylar = array_values(array_unique(array_filter([
                $kaynakLokalGorselDizin !== '' ? $kaynakLokalGorselDizin . '/' . $temizYol : null,
                $kaynakLokalGorselDizin !== '' ? $kaynakLokalGorselDizin . '/' . $dosyaAdi : null,
                $kaynakLokalRoot !== '' ? $kaynakLokalRoot . '/' . $temizYol : null,
                $kaynakLokalRoot !== '' ? $kaynakLokalRoot . '/images/' . $dosyaAdi : null,
                $kaynakLokalRoot !== '' ? $kaynakLokalRoot . '/news/' . $dosyaAdi : null,
            ])));

            foreach ($lokalAdaylar as $lokalAday) {
                if (is_file($lokalAday)) {
                    return 'local://' . $lokalAday;
                }
            }

            if ($kaynakBaseUrl !== '') {
                $adaylar[] = $kaynakBaseUrl . '/' . $temizYol;
                $adaylar[] = $kaynakBaseUrl . '/images/' . $temizYol;
                $adaylar[] = $kaynakBaseUrl . '/images/' . $dosyaAdi;
                $adaylar[] = $kaynakBaseUrl . '/images/news/' . $dosyaAdi;
                $adaylar[] = $kaynakBaseUrl . '/kpmedia/haber/' . $temizYol;
                $adaylar[] = $kaynakBaseUrl . '/haber/' . $temizYol;
                $adaylar[] = $kaynakBaseUrl . '/uploads/' . $temizYol;
            }

            $adaylar[] = $varsayilanEskiSiteBaseUrl . '/images/news/' . $dosyaAdi;
            $adaylar[] = $varsayilanEskiSiteBaseUrl . '/images/' . $dosyaAdi;

            if ($kaynakCdnHaberUrl !== '') {
                $adaylar[] = $kaynakCdnHaberUrl . '/' . $temizYol;
            }
        }

        $adaylar = array_values(array_unique(array_filter($adaylar)));

        foreach ($adaylar as $adayUrl) {
            try {
                $head = Http::timeout(8)->retry(1, 150)->head($adayUrl);

                if ($head->successful()) {
                    return $adayUrl;
                }
            } catch (Throwable) {
                // Bir sonraki aday URL denensin.
            }
        }

        return $adaylar[0] ?? null;
    }

    private function gorseliGeciciyeKaydet(string $url, int $haberId, int $sira): ?string
    {
        try {
            if (Str::startsWith($url, 'local://')) {
                $lokalYol = Str::after($url, 'local://');

                if (! is_file($lokalYol)) {
                    return null;
                }

                $icerik = (string) file_get_contents($lokalYol);
                $uzanti = pathinfo($lokalYol, PATHINFO_EXTENSION);
            } else {
                $yanit = Http::timeout(20)->retry(2, 300)->get($url);

                if (! $yanit->successful()) {
                    return null;
                }

                $icerik = $yanit->body();
                $uzanti = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);
            }

            $uzanti = $uzanti !== '' ? strtolower($uzanti) : 'jpg';

            if (! in_array($uzanti, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $uzanti = 'jpg';
            }

            $geciciYol = sprintf('tmp/haber-aktarim/%d-%03d-%s.%s', $haberId, $sira, Str::random(8), $uzanti);
            Storage::disk('local')->put($geciciYol, $icerik);

            return $geciciYol;
        } catch (Throwable $e) {
            Log::warning('HaberAktarimService@gorseliGeciciyeKaydet hatasi', [
                'url' => $url,
                'mesaj' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function durumuEsle(mixed $durum, mixed $releaseDate): string
    {
        $durum = (int) ($durum ?? 0);

        if ($durum === 1) {
            return HaberDurumu::Yayinda->value;
        }

        $yayinTarihi = $this->tarihiDonustur($releaseDate);

        if ($yayinTarihi && $yayinTarihi->isFuture()) {
            return HaberDurumu::Planli->value;
        }

        return HaberDurumu::Taslak->value;
    }

    private function tarihiDonustur(mixed $deger): ?Carbon
    {
        if (blank($deger)) {
            return null;
        }

        try {
            return Carbon::parse((string) $deger);
        } catch (Throwable) {
            return null;
        }
    }

    private function ozetiHazirla(mixed $smallDescription, string $icerik): ?string
    {
        $ozet = trim((string) ($smallDescription ?? ''));
        $ozet = strip_tags($this->teknikCopMetniniTemizle($ozet));
        $ozet = trim(preg_replace('/\s+/u', ' ', $ozet) ?? $ozet);

        if ($ozet !== '') {
            $duzenliOzet = $this->cumleyiTamamlayarakKirp($ozet, 300);

            if (mb_strlen($duzenliOzet, 'UTF-8') >= 110) {
                return $duzenliOzet;
            }
        }

        $duzMetin = trim(strip_tags($this->teknikCopMetniniTemizle($icerik)));

        if ($duzMetin === '') {
            return null;
        }

        return $this->cumleyiTamamlayarakKirp($duzMetin, 300);
    }

    private function metaDescriptionHazirla(?string $ozet): ?string
    {
        if (blank($ozet)) {
            return null;
        }

        return $this->cumleyiTamamlayarakKirp((string) $ozet, 160);
    }

    private function icerigiTemizle(string $icerik): string
    {
        $icerik = html_entity_decode($icerik, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $icerik = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $icerik) ?? $icerik;
        $icerik = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $icerik) ?? $icerik;

        $icerik = preg_replace('/\sstyle="[^"]*"/iu', '', $icerik) ?? $icerik;
        $icerik = preg_replace('/\sclass="[^"]*"/iu', '', $icerik) ?? $icerik;
        $icerik = preg_replace('/\sid="[^"]*"/iu', '', $icerik) ?? $icerik;

        $icerik = preg_replace('/(?:&raquo;|»)?\s*Discover more[^<\n]*(?:<br\s*\/?\s*>|<\/p>)?/iu', '', $icerik) ?? $icerik;
        $icerik = preg_replace('/(?:&raquo;|»)?\s*(Read more|Click here|Powered by)[^<\n]*(?:<br\s*\/?\s*>|<\/p>)?/iu', '', $icerik) ?? $icerik;
        $icerik = $this->teknikCopMetniniTemizle($icerik);

        $icerik = trim($icerik);

        if ($icerik === '') {
            return '';
        }

        $tagVar = preg_match('/<\/?[a-z][^>]*>/i', $icerik) === 1;

        if (! $tagVar) {
            return $this->duzMetniParagraflandir($icerik);
        }

        return $icerik;
    }

    private function duzMetniParagraflandir(string $metin): string
    {
        $metin = preg_replace('/\r\n?|\n/u', "\n", $metin) ?? $metin;
        $metin = preg_replace('/[ \t]+/u', ' ', $metin) ?? $metin;
        $metin = trim($metin);

        if ($metin === '') {
            return '';
        }

        $paragraflar = preg_split('/\n{2,}/u', $metin);
        $paragraflar = array_values(array_filter(array_map(static fn ($satir) => trim((string) $satir), $paragraflar)));

        if ($paragraflar === []) {
            return '';
        }

        if (count($paragraflar) === 1) {
            $cumleler = preg_split('/(?<=[.!?])\s+/u', $paragraflar[0]);
            $cumleler = array_values(array_filter(array_map(static fn ($cumle) => trim((string) $cumle), $cumleler)));

            if (count($cumleler) > 4) {
                $toplanan = [];
                $gecici = [];

                foreach ($cumleler as $index => $cumle) {
                    $gecici[] = $cumle;

                    if (count($gecici) === 3 || $index === array_key_last($cumleler)) {
                        $toplanan[] = implode(' ', $gecici);
                        $gecici = [];
                    }
                }

                $paragraflar = $toplanan;
            }
        }

        return collect($paragraflar)
            ->map(static fn ($paragraf) => '<p>' . e($paragraf) . '</p>')
            ->implode("\n");
    }

    private function benzersizSlugOlustur(string $hamSlug, ?int $haricHaberId = null): string
    {
        $slugTemel = Str::slug($hamSlug);

        if ($slugTemel === '') {
            $slugTemel = 'haber';
        }

        $slug = Str::limit($slugTemel, 90, '');
        $sayac = 2;

        while (
            Haber::query()
                ->where('slug', $slug)
                ->when($haricHaberId, fn ($q) => $q->where('id', '!=', $haricHaberId))
                ->exists()
        ) {
            $slug = Str::limit($slugTemel, 85, '') . '-' . $sayac;
            $sayac++;
        }

        return $slug;
    }

    private function teknikCopMetniniTemizle(string $metin): string
    {
        $kalip = '/(?:CSS framework subscription|JavaScript library access|Stock photo subscriptions|Cloud storage solutions|HTML editing|Targeted advertising opt-out|HTML Tidy|HTML cleaning service|Code markup highlighter|JavaScript cleaning tool|PDFs tidy HTML|Personal data opt-out word|Inline style removal|Source code cleaner|Graphic design software)(?:[\s\S]*)$/iu';

        return preg_replace($kalip, '', $metin) ?? $metin;
    }

    private function cumleyiTamamlayarakKirp(string $metin, int $maxKarakter): string
    {
        $metin = trim(preg_replace('/\s+/u', ' ', strip_tags($metin)) ?? $metin);

        if ($metin === '') {
            return '';
        }

        $cumleler = preg_split('/(?<=[.!?…])\s+/u', $metin);
        $cumleler = array_values(array_filter(array_map(static fn ($cumle) => trim((string) $cumle), $cumleler)));

        if ($cumleler === []) {
            return $this->kelimeyiBolmedenKirp($metin, $maxKarakter);
        }

        $ozet = '';
        foreach ($cumleler as $cumle) {
            $aday = trim($ozet . ' ' . $cumle);
            if (mb_strlen($aday, 'UTF-8') > $maxKarakter) {
                break;
            }
            $ozet = $aday;
        }

        if ($ozet === '') {
            $ozet = $this->kelimeyiBolmedenKirp($cumleler[0], $maxKarakter);
        }

        if (! preg_match('/[.!?…]$/u', $ozet)) {
            $ozet .= '.';
        }

        return trim($ozet);
    }

    private function kelimeyiBolmedenKirp(string $metin, int $maxKarakter): string
    {
        if (mb_strlen($metin, 'UTF-8') <= $maxKarakter) {
            return trim($metin);
        }

        $parca = mb_substr($metin, 0, $maxKarakter, 'UTF-8');
        $sonBosluk = mb_strrpos($parca, ' ', 0, 'UTF-8');

        if ($sonBosluk !== false && $sonBosluk > (int) ($maxKarakter * 0.6)) {
            $parca = mb_substr($parca, 0, $sonBosluk, 'UTF-8');
        }

        return trim($parca);
    }

    private function kaynakBaglantisiniOlustur(array $secenekler): ConnectionInterface
    {
        $baglantiAdi = 'mysql_old_runtime';
        $mysqlVarsayilan = (array) config('database.connections.mysql');

        config([
            'database.connections.' . $baglantiAdi => array_merge($mysqlVarsayilan, [
                'host' => (string) ($secenekler['old_host'] ?? env('DB_HOST_OLD', '127.0.0.1')),
                'port' => (string) ($secenekler['old_port'] ?? env('DB_PORT_OLD', '3306')),
                'database' => (string) ($secenekler['old_database'] ?? env('DB_DATABASE_OLD', 'kestanepazariorg_groiraz')),
                'username' => (string) ($secenekler['old_username'] ?? env('DB_USERNAME_OLD', '')),
                'password' => (string) ($secenekler['old_password'] ?? env('DB_PASSWORD_OLD', '')),
            ]),
        ]);

        DB::purge($baglantiAdi);

        return DB::connection($baglantiAdi);
    }
}
