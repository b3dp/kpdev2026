<?php

namespace App\Services;

use App\Models\EkayitEvrakSablonu;
use App\Models\EkayitKayit;
use App\Models\EkayitOlusturulanEvrak;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class EkayitPdfService
{
    public function olustur(EkayitKayit $kayit): ?array
    {
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

            $sablon = EkayitEvrakSablonu::query()->firstOrCreate(
                ['dosya_adi' => 'kayit-formu'],
                [
                    'ad' => 'Kayıt Formu PDF',
                    'sablon_yol' => 'pdf.ekayit.kayit-formu',
                    'degiskenler' => [
                        'ogrenci_ad_soyad',
                        'ogrenci_tc_kimlik',
                        'ogrenci_telefon',
                        'ogrenci_eposta',
                        'veli_ad_soyad',
                        'veli_cep1',
                        'veli_cep2',
                        'veli_adres',
                        'veli_il',
                        'veli_ilce',
                        'ogrenci_mezun okul',
                        'ogrenci_okul_no',
                    ],
                    'sadece_onayliya' => false,
                    'sira' => 1,
                    'aktif' => true,
                ]
            );

            $kayitNo = $this->kayitNo($kayit);
            $dosyaAdi = $this->depolamaDosyaAdi($kayit, $kayitNo);
            $indirmeDosyaAdi = $this->indirmeDosyaAdi($kayit, $kayitNo);
            $ogretimYili = (string) ($kayit->sinif?->donem?->ogretim_yili ?? now()->format('Y'));
            $relativeYol = sprintf('pdf26/ekayit/%s/%s/%s', $ogretimYili, $kayitNo, $dosyaAdi);

            $pdf = Pdf::loadView('pdf.ekayit.kayit-formu', [
                'kayit' => $kayit,
                'sinif' => $kayit->sinif,
                'ogrenci' => $kayit->ogrenciBilgisi,
                'kimlik' => $kayit->kimlikBilgisi,
                'okul' => $kayit->okulBilgisi,
                'veli' => $kayit->veliBilgisi,
                'baba' => $kayit->babaBilgisi,
            ])->setPaper('a4');

            $icerik = $pdf->output();
            $yuklendi = Storage::disk('spaces')->put($relativeYol, $icerik, 'public');

            if (! $yuklendi) {
                throw new \RuntimeException('E-Kayıt PDF dosyası DigitalOcean Spaces alanına yüklenemedi.');
            }

            EkayitOlusturulanEvrak::query()->updateOrCreate(
                [
                    'kayit_id' => $kayit->id,
                    'sablon_id' => $sablon->id,
                ],
                [
                    'dosya_yol' => $relativeYol,
                    'olusturulma_tarihi' => now(),
                ]
            );

            return [
                'kayit_no' => $kayitNo,
                'dosya_yol' => $relativeYol,
                'url' => Storage::disk('spaces')->url($relativeYol),
                'indirme_dosya_adi' => $indirmeDosyaAdi,
                'icerik' => $icerik,
            ];
        } catch (Throwable $e) {
            Log::error('E-Kayıt PDF oluşturulamadı', [
                'kayit_id' => $kayit->id ?? null,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);

            return null;
        }
    }

    public function kayitNo(EkayitKayit $kayit): string
    {
        return str_pad((string) $kayit->id, 5, '0', STR_PAD_LEFT);
    }

    private function depolamaDosyaAdi(EkayitKayit $kayit, string $kayitNo): string
    {
        $ogrenciAdSoyad = trim((string) ($kayit->ogrenciBilgisi?->ad_soyad ?? 'ogrenci'));
        $slug = Str::slug($ogrenciAdSoyad, '-');

        if ($slug === '') {
            $slug = 'ogrenci';
        }

        return sprintf('%s-%s.pdf', $slug, $kayitNo);
    }

    private function indirmeDosyaAdi(EkayitKayit $kayit, string $kayitNo): string
    {
        $ogrenciAdSoyad = trim((string) ($kayit->ogrenciBilgisi?->ad_soyad ?? 'Öğrenci'));

        return sprintf('%s - %s.pdf', $ogrenciAdSoyad, $kayitNo);
    }
}
