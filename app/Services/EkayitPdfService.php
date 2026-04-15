<?php

namespace App\Services;

use App\Models\EkayitEvrakSablonu;
use App\Models\EkayitKayit;
use App\Models\EkayitOlusturulanEvrak;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use Throwable;
use ZipArchive;

class EkayitPdfService
{
    private const SAYFA_KENAR_BOSLUGU_TWIP = 850;

    public function olustur(EkayitKayit $kayit): ?array
    {
        $geciciDocxYolu = null;
        $geciciPdfYolu = null;
        $indirilenSablonYolu = null;
        $geciciParcaDocxYollari = [];
        $indirilenSablonYollari = [];

        try {
            $kayit->loadMissing([
                'sinif.donem',
                'sinif.kurum',
                'ogrenciBilgisi',
                'kimlikBilgisi',
                'okulBilgisi',
                'veliBilgisi',
                'babaBilgisi',
            ]);

            $sablonKaynaklari = $this->sablonKaynaklariniGetir();
            $kayitSablonu = $this->kayitSablonunuBelirle($sablonKaynaklari);

            $kayitNo = $this->kayitNo($kayit);
            $dosyaSurumu = now()->format('YmdHis');
            $pdfDosyaAdi = $this->depolamaDosyaAdi($kayit, $kayitNo, 'pdf', $dosyaSurumu);
            $docxDosyaAdi = $this->depolamaDosyaAdi($kayit, $kayitNo, 'docx', $dosyaSurumu);
            $indirmeDosyaAdi = $this->indirmeDosyaAdi($kayit, $kayitNo);
            $ogretimYili = (string) ($kayit->sinif?->donem?->ogretim_yili ?? now()->format('Y'));

            $geciciDizin = storage_path('app/private/tmp/ekayit');

            if (! is_dir($geciciDizin)) {
                mkdir($geciciDizin, 0755, true);
            }

            $geciciDocxYolu = $geciciDizin.'/'.$docxDosyaAdi;
            $geciciPdfYolu = $geciciDizin.'/'.$pdfDosyaAdi;

            $dokumanVerileri = $this->dokumanVerileriniHazirla($kayit);

            foreach ($sablonKaynaklari as $index => $sablonKaynagi) {
                $indirilenSablonYolu = null;
                $sablonDosyasi = $this->sablonKaynaginiBul($sablonKaynagi, $indirilenSablonYolu);

                if (filled($indirilenSablonYolu)) {
                    $indirilenSablonYollari[] = $indirilenSablonYolu;
                }

                $parcaDocxAdi = sprintf(
                    '%s-%02d-%s.docx',
                    pathinfo($docxDosyaAdi, PATHINFO_FILENAME),
                    $index + 1,
                    Str::slug((string) pathinfo((string) ($sablonKaynagi['dosya_adi'] ?? 'sablon'), PATHINFO_FILENAME), '-') ?: 'sablon'
                );

                $parcaDocxYolu = $geciciDizin.'/'.$parcaDocxAdi;
                $this->docxSablonunuDoldur($sablonDosyasi, $parcaDocxYolu, $dokumanVerileri);
                $geciciParcaDocxYollari[] = $parcaDocxYolu;
            }

            $this->docxDosyalariniBirlestir($geciciParcaDocxYollari, $geciciDocxYolu);
            $this->docxDosyasiniPdfyeCevir($geciciDocxYolu, $geciciPdfYolu, $kayit);

            $pdfRelativeYol = sprintf('pdf26/ekayit/%s/%s/%s', $ogretimYili, $kayitNo, $pdfDosyaAdi);
            $docxRelativeYol = sprintf('pdf26/ekayit/%s/%s/%s', $ogretimYili, $kayitNo, $docxDosyaAdi);

            $pdfIcerik = (string) file_get_contents($geciciPdfYolu);
            $docxIcerik = (string) file_get_contents($geciciDocxYolu);

            if (! Storage::disk('spaces')->put($pdfRelativeYol, $pdfIcerik, 'public')) {
                throw new RuntimeException('E-Kayıt PDF dosyası DigitalOcean Spaces alanına yüklenemedi.');
            }

            if (! Storage::disk('spaces')->put($docxRelativeYol, $docxIcerik, 'public')) {
                throw new RuntimeException('E-Kayıt DOCX dosyası DigitalOcean Spaces alanına yüklenemedi.');
            }

            EkayitOlusturulanEvrak::query()->updateOrCreate(
                [
                    'kayit_id' => $kayit->id,
                    'sablon_id' => $kayitSablonu->id,
                ],
                [
                    'dosya_yol' => $pdfRelativeYol,
                    'olusturulma_tarihi' => now(),
                ]
            );

            return [
                'kayit_no' => $kayitNo,
                'dosya_yol' => $pdfRelativeYol,
                'docx_dosya_yol' => $docxRelativeYol,
                'url' => Storage::disk('spaces')->url($pdfRelativeYol),
                'indirme_dosya_adi' => $indirmeDosyaAdi,
                'icerik' => $pdfIcerik,
                'sablon_tipi' => 'docx',
            ];
        } catch (Throwable $e) {
            Log::error('E-Kayıt PDF oluşturulamadı', [
                'kayit_id' => $kayit->id ?? null,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);

            return null;
        } finally {
            foreach (array_merge([$geciciDocxYolu, $geciciPdfYolu, $indirilenSablonYolu], $geciciParcaDocxYollari, $indirilenSablonYollari) as $dosyaYolu) {
                if (filled($dosyaYolu) && is_file($dosyaYolu)) {
                    @unlink($dosyaYolu);
                }
            }
        }
    }

    public function kayitNo(EkayitKayit $kayit): string
    {
        return str_pad((string) $kayit->id, 5, '0', STR_PAD_LEFT);
    }

    private function sablonuHazirla(): EkayitEvrakSablonu
    {
        $sablon = EkayitEvrakSablonu::query()->firstOrNew(['dosya_adi' => 'kayit-formu']);

        $sablon->fill([
            'ad' => $sablon->ad ?: 'Kayıt Formu DOCX Şablonu',
            'sablon_yol' => $this->varsayilanSablonGoreliYoluBelirle($sablon),
            'degiskenler' => [
                'ogrenci_ad_soyad', 'ogrenci_tc_kimlik', 'ogrenci_telefon', 'ogrenci_eposta',
                'ogrenci_anne_ad', 'ogrenci_baba_ad', 'ogrenci_dogum_tarih', 'ogrenci_doğum_yeri',
                'ogrenci_nufus_il', 'ogrenci_nufus_ilce', 'ogrenci_nufus_mahallekoy',
                'ogrenci_nufus_cilt', 'ogrenci_nufus_ailesira', 'ogrenci_nufus_sira',
                'ogrenci_mezun okul', 'ogrenci_okul_no', 'veli_ad_soyad', 'veli_cep1',
                'veli_tel01', 'veli_cep2', 'veli_eposta', 'veli_adres', 'veli_il', 'veli_ilce',
            ],
            'sadece_onayliya' => false,
            'sira' => $sablon->sira ?? 1,
            'aktif' => true,
        ]);

        $sablon->save();

        return $sablon;
    }

    private function sablonKaynaklariniGetir(): Collection
    {
        $varsayilanSablonYollari = $this->varsayilanCokluSablonYollari();

        if ($varsayilanSablonYollari->isNotEmpty()) {
            return $varsayilanSablonYollari->map(fn (string $sablonYolu): array => [
                'sablon' => null,
                'sablon_yol' => $sablonYolu,
                'dosya_adi' => basename($sablonYolu),
            ]);
        }

        $kayitliSablonlar = EkayitEvrakSablonu::query()
            ->where('aktif', true)
            ->get()
            ->sort(function (EkayitEvrakSablonu $ilk, EkayitEvrakSablonu $ikinci): int {
                $ilkSira = $this->dosyaAdindanSiraBul($ilk->sablon_yol ?: $ilk->dosya_adi);
                $ikinciSira = $this->dosyaAdindanSiraBul($ikinci->sablon_yol ?: $ikinci->dosya_adi);

                if ($ilkSira !== $ikinciSira) {
                    return $ilkSira <=> $ikinciSira;
                }

                if ((int) $ilk->sira !== (int) $ikinci->sira) {
                    return (int) $ilk->sira <=> (int) $ikinci->sira;
                }

                return strcmp((string) $ilk->dosya_adi, (string) $ikinci->dosya_adi);
            })
            ->values();

        if ($kayitliSablonlar->isNotEmpty()) {
            return $kayitliSablonlar->map(fn (EkayitEvrakSablonu $sablon): array => [
                'sablon' => $sablon,
                'sablon_yol' => (string) $sablon->sablon_yol,
                'dosya_adi' => (string) ($sablon->dosya_adi ?: basename((string) $sablon->sablon_yol)),
            ]);
        }

        $varsayilanSablon = $this->sablonuHazirla();

        return collect([[
            'sablon' => $varsayilanSablon,
            'sablon_yol' => (string) $varsayilanSablon->sablon_yol,
            'dosya_adi' => (string) $varsayilanSablon->dosya_adi,
        ]]);
    }

    private function kayitSablonunuBelirle(Collection $sablonKaynaklari): EkayitEvrakSablonu
    {
        $kayitliSablon = $sablonKaynaklari
            ->pluck('sablon')
            ->first(fn (mixed $sablon) => $sablon instanceof EkayitEvrakSablonu);

        if ($kayitliSablon instanceof EkayitEvrakSablonu) {
            return $kayitliSablon;
        }

        return $this->sablonuHazirla();
    }

    private function varsayilanCokluSablonYollari(): Collection
    {
        $yollar = glob(base_path('docs/[0-9]*.docx')) ?: [];

        usort($yollar, function (string $ilk, string $ikinci): int {
            $ilkSira = $this->dosyaAdindanSiraBul(basename($ilk));
            $ikinciSira = $this->dosyaAdindanSiraBul(basename($ikinci));

            if ($ilkSira !== $ikinciSira) {
                return $ilkSira <=> $ikinciSira;
            }

            return strcmp(basename($ilk), basename($ikinci));
        });

        return collect($yollar)->filter(fn (string $yol): bool => is_file($yol))->values();
    }

    private function dosyaAdindanSiraBul(string $dosyaAdi): int
    {
        return preg_match('/^(\d+)/', basename($dosyaAdi), $eslesme)
            ? (int) ($eslesme[1] ?? PHP_INT_MAX)
            : PHP_INT_MAX;
    }

    private function sablonKaynaginiBul(array $sablonKaynagi, ?string &$indirilenSablonYolu = null): string
    {
        $sablonYolu = trim((string) ($sablonKaynagi['sablon_yol'] ?? ''));

        if ($sablonYolu !== '' && is_file($sablonYolu)) {
            return $sablonYolu;
        }

        if ($sablonYolu !== '' && is_file(base_path($sablonYolu))) {
            return base_path($sablonYolu);
        }

        if ($sablonYolu !== '' && Storage::disk('spaces')->exists($sablonYolu)) {
            $indirilenSablonYolu = storage_path('app/private/tmp/'.basename($sablonYolu));
            $icerik = Storage::disk('spaces')->get($sablonYolu);
            file_put_contents($indirilenSablonYolu, $icerik);

            return $indirilenSablonYolu;
        }

        if (($sablonKaynagi['sablon'] ?? null) instanceof EkayitEvrakSablonu) {
            return $this->sablonDosyasiniBul($sablonKaynagi['sablon'], $indirilenSablonYolu);
        }

        throw new RuntimeException('E-Kayıt DOCX şablonu bulunamadı: '.($sablonKaynagi['dosya_adi'] ?? 'Bilinmeyen şablon'));
    }

    private function sablonDosyasiniBul(EkayitEvrakSablonu $sablon, ?string &$indirilenSablonYolu = null): string
    {
        $sablonYolu = trim((string) $sablon->sablon_yol);

        if ($sablonYolu !== '' && is_file($sablonYolu)) {
            return $sablonYolu;
        }

        if ($sablonYolu !== '' && is_file(base_path($sablonYolu))) {
            return base_path($sablonYolu);
        }

        if ($sablonYolu !== '' && Storage::disk('spaces')->exists($sablonYolu)) {
            $indirilenSablonYolu = storage_path('app/private/tmp/'.basename($sablonYolu));
            $icerik = Storage::disk('spaces')->get($sablonYolu);
            file_put_contents($indirilenSablonYolu, $icerik);

            return $indirilenSablonYolu;
        }

        return $this->varsayilanSablonMutlakYolu();
    }

    private function varsayilanSablonGoreliYoluBelirle(EkayitEvrakSablonu $sablon): string
    {
        $mevcutYol = trim((string) $sablon->sablon_yol);
        $varsayilanYollar = [
            '',
            'pdf.ekayit.kayit-formu',
            'docs/ekayit_belgeler_taslak2.docx',
            'docs/ekayit_belgeler_taslak3.docx',
        ];

        if (in_array($mevcutYol, $varsayilanYollar, true)) {
            return $this->varsayilanSablonGoreliYolu();
        }

        return $mevcutYol;
    }

    private function varsayilanSablonGoreliYolu(): string
    {
        foreach (['docs/ekayit_belgeler_taslak3.docx', 'docs/ekayit_belgeler_taslak2.docx'] as $goreliYol) {
            if (is_file(base_path($goreliYol))) {
                return $goreliYol;
            }
        }

        throw new RuntimeException('E-Kayıt DOCX şablonu bulunamadı.');
    }

    private function varsayilanSablonMutlakYolu(): string
    {
        return base_path($this->varsayilanSablonGoreliYolu());
    }

    private function dokumanVerileriniHazirla(EkayitKayit $kayit): array
    {
        $ogrenci = $kayit->ogrenciBilgisi;
        $kimlik = $kayit->kimlikBilgisi;
        $okul = $kayit->okulBilgisi;
        $veli = $kayit->veliBilgisi;

        $dogumTarihi = $ogrenci?->dogum_tarihi?->format('d.m.Y')
            ?? ($ogrenci?->dogum_tarihi ? date('d.m.Y', strtotime((string) $ogrenci->dogum_tarihi)) : '—');

        return [
            'ogrenci_ad_soyad' => $ogrenci?->ad_soyad ?? '—',
            'ogrenci_tc_kimlik' => $ogrenci?->tc_kimlik ?? '—',
            'ogrenci_telefon' => $ogrenci?->telefon ?: ($veli?->telefon_1 ?? '—'),
            'ogrenci_eposta' => $ogrenci?->eposta ?: ($veli?->eposta ?? '—'),
            'dogum_yeri' => $ogrenci?->dogum_yeri ?? '—',
            'ogrenci_dogum_yeri' => $ogrenci?->dogum_yeri ?? '—',
            'ogrenci_doğum_yeri' => $ogrenci?->dogum_yeri ?? '—',
            'dogum_tarihi' => $dogumTarihi,
            'ogrenci_dogum_tarih' => $dogumTarihi,
            'anne_ad' => $ogrenci?->anne_adi ?? '—',
            'ogrenci_anne_ad' => $ogrenci?->anne_adi ?? '—',
            'ogrenci_baba_ad' => $ogrenci?->baba_adi ?? '—',
            'ogrenci_nufus_il' => $kimlik?->kayitli_il ?? '—',
            'ogrenci_nufus_ilce' => $kimlik?->kayitli_ilce ?? '—',
            'ogrenci_nufus_mahallekoy' => $kimlik?->kayitli_mahalle_koy ?? '—',
            'ogrenci_nufus_cilt' => $kimlik?->cilt_no ?? '—',
            'ogrenci_nufus_ailesira' => $kimlik?->aile_sira_no ?? '—',
            'ogrenci_nufus_sira' => $kimlik?->sira_no ?? '—',
            'ogrenci_mezun okul' => $okul?->okul_adi ?? '—',
            'ogrenci_mezun_okul' => $okul?->okul_adi ?? '—',
            'ogrenci_okul_no' => $okul?->okul_numarasi ?? '—',
            'veli_ad_soyad' => $veli?->ad_soyad ?? '—',
            'veli_ad' => $veli?->ad_soyad ?? '—',
            'veli_cep1' => $veli?->telefon_1 ?? '—',
            'veli_tel01' => $veli?->telefon_1 ?? '—',
            'veli_cep2' => $veli?->telefon_2 ?? '—',
            'veli_tel02' => $veli?->telefon_2 ?? '—',
            'veli_eposta' => $veli?->eposta ?? '—',
            'veli_adres' => $veli?->adres ?? '—',
            'veli_il' => $veli?->ikamet_il ?? '—',
            'veli_ilce' => $veli?->ikamet_ilce ?? '—',
        ];
    }

    private function docxSablonunuDoldur(string $sablonDosyasi, string $hedefDocxYolu, array $degerler): void
    {
        $templateProcessor = new TemplateProcessor($sablonDosyasi);
        $templateProcessor->setMacroChars('[', ']');

        foreach ($degerler as $anahtar => $deger) {
            $templateProcessor->setValue((string) $anahtar, $this->sablonaYazilacakMetin($deger));
        }

        $templateProcessor->saveAs($hedefDocxYolu);

        $zip = new ZipArchive();

        if ($zip->open($hedefDocxYolu) !== true) {
            throw new RuntimeException('Doldurulmuş DOCX şablonu açılamadı.');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $dosyaAdi = $zip->getNameIndex($index);

            if (! is_string($dosyaAdi) || ! str_starts_with($dosyaAdi, 'word/') || ! str_ends_with($dosyaAdi, '.xml')) {
                continue;
            }

            $xmlIcerik = $zip->getFromName($dosyaAdi);

            if (! is_string($xmlIcerik) || $xmlIcerik === '') {
                continue;
            }

            $guncelXml = $this->xmlIcindekiPlaceholderlariDoldur($xmlIcerik, $degerler);
            $guncelXml = $this->xmlTablolariStabilizeEt($guncelXml);
            $guncelXml = $this->xmlSayfaKenarBosluklariniAyarla($guncelXml);
            $zip->addFromString($dosyaAdi, $guncelXml);
        }

        $zip->close();
    }

    private function docxDosyalariniBirlestir(array $docxDosyaYollari, string $hedefDocxYolu): void
    {
        $docxDosyaYollari = array_values(array_filter($docxDosyaYollari, fn (mixed $yol): bool => filled($yol) && is_file((string) $yol)));

        if ($docxDosyaYollari === []) {
            throw new RuntimeException('Birleştirilecek DOCX dosyası bulunamadı.');
        }

        if (count($docxDosyaYollari) === 1) {
            copy($docxDosyaYollari[0], $hedefDocxYolu);

            return;
        }

        copy($docxDosyaYollari[0], $hedefDocxYolu);

        $hedefZip = new ZipArchive();

        if ($hedefZip->open($hedefDocxYolu) !== true) {
            throw new RuntimeException('Birleşik DOCX için temel dosya açılamadı.');
        }

        $hedefBelgeXml = $hedefZip->getFromName('word/document.xml');

        if (! is_string($hedefBelgeXml) || $hedefBelgeXml === '') {
            $hedefZip->close();

            throw new RuntimeException('Temel DOCX içindeki belge XML verisi okunamadı.');
        }

        [$baslangic, $govde, $sectPr, $bitis] = $this->docxBelgeGovdesiniAyir($hedefBelgeXml);
        $ekGovde = '';

        foreach (array_slice($docxDosyaYollari, 1) as $docxDosyaYolu) {
            $zip = new ZipArchive();

            if ($zip->open($docxDosyaYolu) !== true) {
                continue;
            }

            $belgeXml = $zip->getFromName('word/document.xml');
            $zip->close();

            if (! is_string($belgeXml) || $belgeXml === '') {
                continue;
            }

            [, $parcaGovde] = $this->docxBelgeGovdesiniAyir($belgeXml);
            $ekGovde .= $this->sayfaSonuXml().$parcaGovde;
        }

        $birlestirilmisBelgeXml = $baslangic.$govde.$ekGovde.$sectPr.$bitis;
        $birlestirilmisBelgeXml = $this->xmlSayfaKenarBosluklariniAyarla($birlestirilmisBelgeXml);

        $hedefZip->addFromString('word/document.xml', $birlestirilmisBelgeXml);
        $hedefZip->close();
    }

    private function docxBelgeGovdesiniAyir(string $xmlIcerik): array
    {
        if (! preg_match('/^(.*?<w:body[^>]*>)(.*?)(<w:sectPr\b.*?<\/w:sectPr>)?\s*(<\/w:body>.*)$/su', $xmlIcerik, $eslesme)) {
            throw new RuntimeException('DOCX belge gövdesi ayrıştırılamadı.');
        }

        return [
            $eslesme[1] ?? '',
            $eslesme[2] ?? '',
            $eslesme[3] ?? '',
            $eslesme[4] ?? '',
        ];
    }

    private function sayfaSonuXml(): string
    {
        return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }

    private function xmlIcindekiPlaceholderlariDoldur(string $xmlIcerik, array $degerler): string
    {
        preg_match_all('/\[[^\]]+\]/u', $xmlIcerik, $eslesmeler);
        $hamPlaceholderlar = array_unique($eslesmeler[0] ?? []);

        foreach ($hamPlaceholderlar as $hamPlaceholder) {
            $anahtar = $this->placeholderAnahtariniBul($hamPlaceholder, $degerler);

            if ($anahtar === null) {
                continue;
            }

            $xmlIcerik = str_replace(
                $hamPlaceholder,
                $this->xmlGuvenliMetin($degerler[$anahtar] ?? '—'),
                $xmlIcerik
            );
        }

        return $xmlIcerik;
    }

    private function placeholderAnahtariniBul(string $hamPlaceholder, array $degerler): ?string
    {
        $normalize = $this->placeholderiNormalizeEt($hamPlaceholder);
        $bosluksuzNormalize = str_replace([' ', '_'], '', $normalize);

        foreach (array_keys($degerler) as $anahtar) {
            $anahtarNormalize = $this->placeholderiNormalizeEt((string) $anahtar);

            if (
                $normalize === $anahtarNormalize
                || $normalize === str_replace('_', ' ', $anahtarNormalize)
                || $normalize === str_replace(' ', '_', $anahtarNormalize)
                || $bosluksuzNormalize === str_replace([' ', '_'], '', $anahtarNormalize)
            ) {
                return (string) $anahtar;
            }
        }

        return null;
    }

    private function xmlTablolariStabilizeEt(string $xmlIcerik): string
    {
        return preg_replace_callback('/<w:tbl>.*?<\/w:tbl>/su', function (array $eslesme): string {
            $tabloXml = $eslesme[0];

            if (! str_contains($tabloXml, 'w:tblpPr')) {
                $tabloXml = preg_replace(
                    '/(<w:tblStyle\b[^>]*\/>)\s*/u',
                    '$1<w:tblpPr w:leftFromText="141" w:rightFromText="141" w:vertAnchor="text" w:horzAnchor="margin" w:tblpXSpec="center" w:tblpY="0"/>',
                    $tabloXml,
                    1
                ) ?? $tabloXml;
            }

            if (! str_contains($tabloXml, 'w:jc ')) {
                $tabloXml = preg_replace(
                    '/(<w:tblW\b[^>]*\/>)\s*/u',
                    '$1<w:jc w:val="center"/>',
                    $tabloXml,
                    1
                ) ?? $tabloXml;
            }

            if (! str_contains($tabloXml, 'w:tblLayout')) {
                $tabloXml = preg_replace(
                    '/(<w:tblW\b[^>]*\/>)\s*/u',
                    '$1<w:tblLayout w:type="fixed"/>',
                    $tabloXml,
                    1
                ) ?? $tabloXml;
            }

            $tabloXml = preg_replace_callback(
                '/<w:tblInd\b[^>]*w:w="(-?\d+)"[^>]*\/>/u',
                function (array $girintiEslesme): string {
                    $girinti = (int) ($girintiEslesme[1] ?? 0);

                    if ($girinti >= 0) {
                        return preg_replace('/w:w="\d+"/u', 'w:w="0"', $girintiEslesme[0], 1) ?? $girintiEslesme[0];
                    }

                    return preg_replace('/w:w="-?\d+"/u', 'w:w="0"', $girintiEslesme[0], 1) ?? $girintiEslesme[0];
                },
                $tabloXml
            ) ?? $tabloXml;

            return $tabloXml;
        }, $xmlIcerik) ?? $xmlIcerik;
    }

    private function xmlSayfaKenarBosluklariniAyarla(string $xmlIcerik): string
    {
        return preg_replace_callback('/<w:sectPr\b[^>]*>.*?<\/w:sectPr>/su', function (array $eslesme): string {
            $sectPrXml = $eslesme[0];

            if (preg_match('/<w:pgMar\b[^>]*\/>/u', $sectPrXml, $pgMarEslesme) !== 1) {
                return preg_replace(
                    '/(<w:sectPr\b[^>]*>)/u',
                    '$1'.$this->sayfaKenarBosluklariXml(),
                    $sectPrXml,
                    1
                ) ?? $sectPrXml;
            }

            $guncelPgMarXml = $pgMarEslesme[0];

            foreach (['w:top', 'w:right', 'w:bottom', 'w:left'] as $nitelik) {
                $guncelPgMarXml = $this->xmlNiteliginiAyarla($guncelPgMarXml, $nitelik, self::SAYFA_KENAR_BOSLUGU_TWIP);
            }

            return str_replace($pgMarEslesme[0], $guncelPgMarXml, $sectPrXml);
        }, $xmlIcerik) ?? $xmlIcerik;
    }

    private function sayfaKenarBosluklariXml(): string
    {
        return sprintf(
            '<w:pgMar w:top="%1$d" w:right="%1$d" w:bottom="%1$d" w:left="%1$d" w:header="708" w:footer="708" w:gutter="0"/>',
            self::SAYFA_KENAR_BOSLUGU_TWIP
        );
    }

    private function xmlNiteliginiAyarla(string $xml, string $nitelik, int $deger): string
    {
        if (preg_match('/'.preg_quote($nitelik, '/').'="[^"]*"/u', $xml) === 1) {
            return preg_replace(
                '/'.preg_quote($nitelik, '/').'="[^"]*"/u',
                sprintf('%s="%d"', $nitelik, $deger),
                $xml,
                1
            ) ?? $xml;
        }

        return preg_replace('/\/>$/u', sprintf(' %s="%d"/>', $nitelik, $deger), $xml, 1) ?? $xml;
    }

    private function placeholderiNormalizeEt(string $metin): string
    {
        $metin = html_entity_decode($metin, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $metin = strip_tags($metin);
        $metin = trim($metin, "[] \t\n\r\0\x0B");
        $metin = mb_strtolower($metin, 'UTF-8');
        $metin = strtr($metin, [
            'ç' => 'c',
            'ğ' => 'g',
            'ı' => 'i',
            'ö' => 'o',
            'ş' => 's',
            'ü' => 'u',
        ]);
        $metin = preg_replace('/[^\pL\pN_\s]+/u', '', $metin) ?? $metin;
        $metin = preg_replace('/\s*_\s*/u', '_', $metin) ?? $metin;
        $metin = preg_replace('/\s+/u', ' ', $metin) ?? $metin;

        return trim($metin);
    }

    private function sablonaYazilacakMetin(mixed $deger): string
    {
        $metin = trim((string) ($deger ?? ''));
        $metin = $metin !== '' ? $metin : '—';

        return str_replace(["\r\n", "\n", "\r"], ' / ', $metin);
    }

    private function xmlGuvenliMetin(mixed $deger): string
    {
        return htmlspecialchars($this->sablonaYazilacakMetin($deger), ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function docxDosyasiniPdfyeCevir(string $docxYolu, string $pdfYolu, EkayitKayit $kayit): void
    {
        if (app()->environment('testing')) {
            $this->bladeSablonundanPdfOlustur($pdfYolu, $kayit);
        } else {
            try {
                app(GoogleDriveService::class)->docxDosyasiniPdfyeCevir(
                    $docxYolu,
                    $pdfYolu,
                    pathinfo($docxYolu, PATHINFO_FILENAME)
                );
            } catch (Throwable $exception) {
                Log::warning('Google Drive ile DOCX PDF cevirimi basarisiz, PhpWord fallback deneniyor.', [
                    'kayit_id' => $kayit->id,
                    'mesaj' => $exception->getMessage(),
                ]);

                Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
                Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

                $phpWord = IOFactory::load($docxYolu, 'Word2007');
                $yazici = IOFactory::createWriter($phpWord, 'PDF');
                $yazici->save($pdfYolu);
            }
        }

        if (! is_file($pdfYolu) || filesize($pdfYolu) === 0) {
            throw new RuntimeException('DOCX şablonundan PDF üretilemedi.');
        }
    }

    private function bladeSablonundanPdfOlustur(string $pdfYolu, EkayitKayit $kayit): void
    {
        $pdf = Pdf::loadView('pdf.ekayit.kayit-formu', [
            'kayit' => $kayit,
            'sinif' => $kayit->sinif,
            'ogrenci' => $kayit->ogrenciBilgisi,
            'kimlik' => $kayit->kimlikBilgisi,
            'okul' => $kayit->okulBilgisi,
            'veli' => $kayit->veliBilgisi,
            'baba' => $kayit->babaBilgisi,
        ])->setPaper('a4');

        file_put_contents($pdfYolu, $pdf->output());
    }

    private function depolamaDosyaAdi(EkayitKayit $kayit, string $kayitNo, string $uzanti, ?string $surum = null): string
    {
        $ogrenciAdSoyad = trim((string) ($kayit->ogrenciBilgisi?->ad_soyad ?? 'ogrenci'));
        $slug = Str::slug($ogrenciAdSoyad, '-');

        if ($slug === '') {
            $slug = 'ogrenci';
        }

        $surum = $surum ?: now()->format('YmdHis');

        return sprintf('%s-%s-%s.%s', $slug, $kayitNo, $surum, $uzanti);
    }

    private function indirmeDosyaAdi(EkayitKayit $kayit, string $kayitNo): string
    {
        $ogrenciAdSoyad = trim((string) ($kayit->ogrenciBilgisi?->ad_soyad ?? 'Öğrenci'));

        return sprintf('%s - %s.pdf', $ogrenciAdSoyad, $kayitNo);
    }
}
