<?php

namespace App\Services;

use App\Models\EkayitEvrakSablonu;
use App\Models\EkayitKayit;
use App\Models\EkayitOlusturulanEvrak;
use Barryvdh\DomPDF\Facade\Pdf;
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
    public function olustur(EkayitKayit $kayit): ?array
    {
        $geciciDocxYolu = null;
        $geciciPdfYolu = null;
        $indirilenSablonYolu = null;

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

            $sablon = $this->sablonuHazirla();
            $sablonDosyasi = $this->sablonDosyasiniBul($sablon, $indirilenSablonYolu);

            $kayitNo = $this->kayitNo($kayit);
            $pdfDosyaAdi = $this->depolamaDosyaAdi($kayit, $kayitNo, 'pdf');
            $docxDosyaAdi = $this->depolamaDosyaAdi($kayit, $kayitNo, 'docx');
            $indirmeDosyaAdi = $this->indirmeDosyaAdi($kayit, $kayitNo);
            $ogretimYili = (string) ($kayit->sinif?->donem?->ogretim_yili ?? now()->format('Y'));

            $geciciDizin = storage_path('app/private/tmp/ekayit');

            if (! is_dir($geciciDizin)) {
                mkdir($geciciDizin, 0755, true);
            }

            $geciciDocxYolu = $geciciDizin.'/'.$docxDosyaAdi;
            $geciciPdfYolu = $geciciDizin.'/'.$pdfDosyaAdi;

            $this->docxSablonunuDoldur($sablonDosyasi, $geciciDocxYolu, $this->dokumanVerileriniHazirla($kayit));
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
                    'sablon_id' => $sablon->id,
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
            foreach ([$geciciDocxYolu, $geciciPdfYolu, $indirilenSablonYolu] as $dosyaYolu) {
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
            'sablon_yol' => blank($sablon->sablon_yol) || $sablon->sablon_yol === 'pdf.ekayit.kayit-formu'
                ? 'docs/ekayit_belgeler_taslak2.docx'
                : $sablon->sablon_yol,
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

        $varsayilanYol = base_path('docs/ekayit_belgeler_taslak2.docx');

        if (! is_file($varsayilanYol)) {
            throw new RuntimeException('E-Kayıt DOCX şablonu bulunamadı.');
        }

        return $varsayilanYol;
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
            $zip->addFromString($dosyaAdi, $guncelXml);
        }

        $zip->close();
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
        } else {
            Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
            Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

            $phpWord = IOFactory::load($docxYolu, 'Word2007');
            $yazici = IOFactory::createWriter($phpWord, 'PDF');
            $yazici->save($pdfYolu);
        }

        if (! is_file($pdfYolu) || filesize($pdfYolu) === 0) {
            throw new RuntimeException('DOCX şablonundan PDF üretilemedi.');
        }
    }

    private function depolamaDosyaAdi(EkayitKayit $kayit, string $kayitNo, string $uzanti): string
    {
        $ogrenciAdSoyad = trim((string) ($kayit->ogrenciBilgisi?->ad_soyad ?? 'ogrenci'));
        $slug = Str::slug($ogrenciAdSoyad, '-');

        if ($slug === '') {
            $slug = 'ogrenci';
        }

        return sprintf('%s-%s.%s', $slug, $kayitNo, $uzanti);
    }

    private function indirmeDosyaAdi(EkayitKayit $kayit, string $kayitNo): string
    {
        $ogrenciAdSoyad = trim((string) ($kayit->ogrenciBilgisi?->ad_soyad ?? 'Öğrenci'));

        return sprintf('%s - %s.pdf', $ogrenciAdSoyad, $kayitNo);
    }
}
