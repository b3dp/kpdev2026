<?php

namespace App\Services;

use App\Enums\BagisDurumu;
use App\Enums\OdemeSaglayici;
use App\Enums\SepetDurumu;
use App\Jobs\KurbanAktarimJob;
use App\Jobs\MakbuzOlusturJob;
use App\Models\Bagis;
use App\Models\BagisKalemi;
use App\Models\BagisTuru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class BagisOdemeService
{
    public function testModuAktifMi(): bool
    {
        try {
            return (bool) config('services.bagis.test_mode', true);
        } catch (Throwable $exception) {
            Log::error('Bağış test modu kontrolü başarısız.', [
                'hata' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function testKartlariniGetir(): array
    {
        try {
            return config('services.bagis.test_cards', [
                [
                    'etiket' => 'Başarılı Visa',
                    'kart_no' => '4111 1111 1111 1111',
                    'sonuc' => 'basarili',
                    'mesaj' => 'Test ödeme başarıyla tamamlandı.',
                ],
                [
                    'etiket' => 'Başarılı Mastercard',
                    'kart_no' => '5555 5555 5555 4444',
                    'sonuc' => 'basarili',
                    'mesaj' => 'Test ödeme başarıyla tamamlandı.',
                ],
                [
                    'etiket' => 'Yetersiz Bakiye',
                    'kart_no' => '4000 0000 0000 0002',
                    'sonuc' => 'hatali',
                    'mesaj' => 'Test kartı yetersiz bakiye senaryosuna düştü.',
                ],
                [
                    'etiket' => 'Banka Reddi',
                    'kart_no' => '4000 0000 0000 9995',
                    'sonuc' => 'hatali',
                    'mesaj' => 'Test kartı banka reddi senaryosuna düştü.',
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Bağış test kartları okunamadı.', [
                'hata' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    public function odemeYap(Request $request, array $veri): Bagis
    {
        try {
            if (! $this->testModuAktifMi()) {
                throw ValidationException::withMessages([
                    'genel' => 'Test ödeme modu şu anda kapalı.',
                ]);
            }

            $bagisTuru = BagisTuru::query()->where('slug', $veri['slug'])->firstOrFail();
            $tutar = max((float) ($veri['tutar'] ?? 0), 0);
            $maksimumAdet = ($bagisTuru->fiyat_tipi?->value ?? $bagisTuru->fiyat_tipi) === 'sabit'
                && ($bagisTuru->ozellik?->value ?? $bagisTuru->ozellik) !== 'buyukbas_kurban'
                ? 30
                : 7;
            $adet = min(max((int) ($veri['adet'] ?? 1), 1), $maksimumAdet);
            $sahipTipi = (string) ($veri['sahip_tipi'] ?? 'kendi');
            $formVerisi = is_array($veri['form_verisi'] ?? null) ? $veri['form_verisi'] : [];

            if ($bagisTuru->minimum_tutar && $tutar < (float) $bagisTuru->minimum_tutar) {
                throw ValidationException::withMessages([
                    'tutar' => 'Minimum bağış tutarı ₺'.number_format((float) $bagisTuru->minimum_tutar, 0, ',', '.').' olmalıdır.',
                ]);
            }

            $kartSenaryosu = $this->kartSenaryosunuBelirle((string) ($veri['kart_no'] ?? ''));

            if (! ($kartSenaryosu['basarili'] ?? false)) {
                throw ValidationException::withMessages([
                    'kart_no' => (string) ($kartSenaryosu['mesaj'] ?? 'Kart doğrulanamadı.'),
                ]);
            }

            $odeyen = $this->odeyenBilgileriniHazirla($formVerisi);
            $sepetService = app(SepetService::class);
            $sepet = $sepetService->aktifSepetAl($request);
            $sessionSepet = collect($request->session()->get('sepet', []))->values();

            if ($sessionSepet->isEmpty()) {
                $ilkSatir = $sepetService->sepeteEkle($sepet, $bagisTuru, $adet, $sahipTipi, $tutar);

                $sessionSepet->push([
                    'satir_id' => $ilkSatir->id,
                    'bagis_turu_id' => $bagisTuru->id,
                    'slug' => $bagisTuru->slug,
                    'ad' => $bagisTuru->ad,
                    'adet' => $adet,
                    'birim_fiyat' => $tutar,
                    'toplam' => $tutar * $adet,
                    'sahip_tipi' => $sahipTipi,
                    'form_verisi' => $formVerisi,
                ]);

                $request->session()->put('sepet', $sessionSepet->all());
            }

            $toplamTutar = (float) $sessionSepet->sum(fn ($satir) => (float) ($satir['toplam'] ?? 0));

            if ($toplamTutar <= 0) {
                throw ValidationException::withMessages([
                    'genel' => 'Ödeme için sepetinizde en az bir bağış kalemi olmalıdır.',
                ]);
            }

            $sepet->update([
                'durum' => SepetDurumu::OdemeBekleniyor->value,
                'toplam_tutar' => $toplamTutar,
            ]);

            $bagis = Bagis::query()->create([
                'bagis_no' => Bagis::bagisNoUret(),
                'sepet_id' => $sepet->id,
                'uye_id' => $request->user('uye')?->id,
                'durum' => BagisDurumu::Beklemede->value,
                'toplam_tutar' => $toplamTutar,
                'odeme_saglayici' => $this->odemeSaglayicisiniHazirla($veri['odeme_yontemi'] ?? null),
                'makbuz_gonderildi' => false,
                'kurban_aktarildi' => false,
            ]);

            foreach ($sessionSepet as $sepetSatiri) {
                $kalemBagisTuru = BagisTuru::query()->find($sepetSatiri['bagis_turu_id'] ?? 0)
                    ?? BagisTuru::query()->where('slug', $sepetSatiri['slug'] ?? '')->first();

                if (! $kalemBagisTuru) {
                    continue;
                }

                $kalemMaksimumAdet = ($kalemBagisTuru->fiyat_tipi?->value ?? $kalemBagisTuru->fiyat_tipi) === 'sabit'
                    && ($kalemBagisTuru->ozellik?->value ?? $kalemBagisTuru->ozellik) !== 'buyukbas_kurban'
                    ? 30
                    : 7;
                $kalemAdet = min(max((int) ($sepetSatiri['adet'] ?? 1), 1), $kalemMaksimumAdet);
                $kalemBirimFiyat = max((float) ($sepetSatiri['birim_fiyat'] ?? 0), 0);
                $kalemToplam = max((float) ($sepetSatiri['toplam'] ?? ($kalemBirimFiyat * $kalemAdet)), 0);
                $kalemSahipTipi = in_array(($sepetSatiri['sahip_tipi'] ?? 'kendi'), ['kendi', 'baskasi'], true)
                    ? (string) $sepetSatiri['sahip_tipi']
                    : 'kendi';
                $kalemFormVerisi = is_array($sepetSatiri['form_verisi'] ?? null) ? $sepetSatiri['form_verisi'] : [];
                $ozellik = $kalemBagisTuru->ozellik?->value ?? (string) $kalemBagisTuru->ozellik;

                $kalem = $bagis->kalemler()->create([
                    'bagis_id' => $bagis->id,
                    'bagis_turu_id' => $kalemBagisTuru->id,
                    'adet' => $kalemAdet,
                    'birim_fiyat' => $kalemBirimFiyat,
                    'toplam' => $kalemToplam,
                    'sahip_tipi' => $kalemSahipTipi,
                    'vekalet_onay' => $kalemSahipTipi === 'baskasi',
                ]);

                $this->bagisKisileriniOlustur($bagis, $kalem, $kalemFormVerisi, $odeyen, $kalemSahipTipi, $ozellik, $kalemAdet);
            }

            if (! $bagis->kalemler()->exists()) {
                throw ValidationException::withMessages([
                    'genel' => 'Ödeme için işlenebilir bir bağış kalemi bulunamadı.',
                ]);
            }

            Bagis::withoutEvents(function () use ($bagis): void {
                $bagis->update([
                    'durum' => BagisDurumu::Odendi->value,
                    'odeme_referans' => 'TEST-'.strtoupper(Str::random(10)),
                    'odeme_tarihi' => now(),
                ]);
            });

            MakbuzOlusturJob::dispatch($bagis)->onQueue('default');
            KurbanAktarimJob::dispatch($bagis->id)->onQueue('default');
            app(KisiEslestirmeService::class)->bagisEslestir($bagis->fresh(['kisiler', 'uye']));

            $sepetService->sepetiBosalt($sepet);
            $sepet->update([
                'durum' => SepetDurumu::Tamamlandi->value,
            ]);

            return $bagis->fresh(['kalemler.bagisTuru', 'kisiler']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Bağış test ödeme işlemi başarısız.', [
                'hata' => $exception->getMessage(),
                'slug' => $veri['slug'] ?? null,
                'telefon' => $veri['form_verisi']['odeyen_telefon'] ?? null,
                'eposta' => $veri['form_verisi']['odeyen_eposta'] ?? null,
            ]);

            throw $exception;
        }
    }

    private function odemeSaglayicisiniHazirla(?string $odemeYontemi): string
    {
        return in_array($odemeYontemi, [OdemeSaglayici::Albaraka->value, OdemeSaglayici::Paytr->value], true)
            ? $odemeYontemi
            : OdemeSaglayici::Albaraka->value;
    }

    private function kartSenaryosunuBelirle(string $kartNo): array
    {
        $temizKartNo = preg_replace('/\D+/', '', $kartNo) ?: '';

        foreach ($this->testKartlariniGetir() as $kart) {
            $kayitliKartNo = preg_replace('/\D+/', '', (string) ($kart['kart_no'] ?? '')) ?: '';

            if ($kayitliKartNo !== '' && $kayitliKartNo === $temizKartNo) {
                return [
                    'basarili' => ($kart['sonuc'] ?? 'hatali') === 'basarili',
                    'mesaj' => (string) ($kart['mesaj'] ?? ''),
                ];
            }
        }

        return [
            'basarili' => false,
            'mesaj' => 'Bu ekranda yalnızca tanımlı test kartları kullanılabilir.',
        ];
    }

    private function odeyenBilgileriniHazirla(array $formVerisi): array
    {
        $adSoyad = trim((string) ($formVerisi['odeyen_ad_soyad'] ?? ''));
        $eposta = filled($formVerisi['odeyen_eposta'] ?? null) ? mb_strtolower(trim((string) $formVerisi['odeyen_eposta'])) : null;
        $telefon = $this->telefonuTemizle($formVerisi['odeyen_telefon'] ?? null);
        $tcKimlik = $this->sayisalDeger($formVerisi['odeyen_tc'] ?? null, 11);

        if ($adSoyad === '') {
            throw ValidationException::withMessages([
                'form_verisi.odeyen_ad_soyad' => 'Ödeyen ad soyad alanı zorunludur.',
            ]);
        }

        if (! $eposta && ! $telefon) {
            throw ValidationException::withMessages([
                'form_verisi.odeyen_eposta' => 'E-posta veya telefon alanlarından en az biri zorunludur.',
                'form_verisi.odeyen_telefon' => 'E-posta veya telefon alanlarından en az biri zorunludur.',
            ]);
        }

        return [
            'ad_soyad' => $adSoyad,
            'eposta' => $eposta,
            'telefon' => $telefon,
            'tc_kimlik' => $tcKimlik,
        ];
    }

    private function bagisKisileriniOlustur(Bagis $bagis, BagisKalemi $kalem, array $formVerisi, array $odeyen, string $sahipTipi, string $ozellik, int $adet): void
    {
        try {
            if ($ozellik === 'normal' && $sahipTipi === 'kendi') {
                $bagis->kisiler()->create(array_merge($odeyen, [
                    'kalem_id' => $kalem->id,
                    'uye_id' => $bagis->uye_id,
                    'tip' => ['odeyen', 'sahip'],
                ]));

                return;
            }

            $bagis->kisiler()->create(array_merge($odeyen, [
                'kalem_id' => $kalem->id,
                'uye_id' => $bagis->uye_id,
                'tip' => ['odeyen'],
            ]));

            if ($ozellik === 'buyukbas_kurban') {
                foreach ($this->hissedarBilgileriniHazirla($formVerisi, $adet) as $hissedar) {
                    $bagis->kisiler()->create(array_merge($hissedar, [
                        'kalem_id' => $kalem->id,
                        'uye_id' => $bagis->uye_id,
                        'tip' => ['hissedar'],
                    ]));
                }

                return;
            }

            $sahip = match ($ozellik) {
                'kucukbas_kurban' => [
                    'ad_soyad' => trim((string) ($formVerisi['kucukbas_ad_soyad'] ?? '')),
                    'eposta' => filled($formVerisi['kucukbas_eposta'] ?? null) ? mb_strtolower(trim((string) $formVerisi['kucukbas_eposta'])) : null,
                    'telefon' => $this->telefonuTemizle($formVerisi['kucukbas_telefon'] ?? null),
                    'tc_kimlik' => $this->sayisalDeger($formVerisi['kucukbas_tc'] ?? null, 11),
                ],
                default => [
                    'ad_soyad' => trim((string) ($formVerisi['sahip_ad_soyad'] ?? '')),
                    'eposta' => null,
                    'telefon' => $this->telefonuTemizle($formVerisi['sahip_telefon'] ?? null),
                    'tc_kimlik' => null,
                    'vekalet_ad_soyad' => filled($formVerisi['sahip_vekalet_notu'] ?? null) ? trim((string) $formVerisi['sahip_vekalet_notu']) : null,
                ],
            };

            if (! filled($sahip['ad_soyad'] ?? null)) {
                return;
            }

            $bagis->kisiler()->create(array_merge($sahip, [
                'kalem_id' => $kalem->id,
                'uye_id' => $bagis->uye_id,
                'tip' => ['sahip'],
            ]));
        } catch (Throwable $exception) {
            Log::error('Bağış kişi kayıtları oluşturulamadı.', [
                'bagis_id' => $bagis->id,
                'hata' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function hissedarBilgileriniHazirla(array $formVerisi, int $adet): array
    {
        $hissedarlar = [];

        for ($sira = 0; $sira < $adet; $sira++) {
            $adSoyad = trim((string) ($formVerisi["hissedarlar[{$sira}][ad_soyad]"] ?? ''));

            if ($adSoyad === '') {
                continue;
            }

            $hissedarlar[] = [
                'ad_soyad' => $adSoyad,
                'eposta' => filled($formVerisi["hissedarlar[{$sira}][eposta]"] ?? null)
                    ? mb_strtolower(trim((string) $formVerisi["hissedarlar[{$sira}][eposta]"]))
                    : null,
                'telefon' => $this->telefonuTemizle($formVerisi["hissedarlar[{$sira}][telefon]"] ?? null),
                'tc_kimlik' => $this->sayisalDeger($formVerisi["hissedarlar[{$sira}][tc_kimlik]"] ?? null, 11),
                'hisse_no' => $sira + 1,
            ];
        }

        return $hissedarlar;
    }

    private function telefonuTemizle(mixed $telefon): ?string
    {
        $temizTelefon = preg_replace('/\D+/', '', (string) $telefon) ?: '';

        return $temizTelefon !== '' ? $temizTelefon : null;
    }

    private function sayisalDeger(mixed $deger, int $uzunluk): ?string
    {
        $temizDeger = preg_replace('/\D+/', '', (string) $deger) ?: '';

        if ($temizDeger === '') {
            return null;
        }

        return substr($temizDeger, 0, $uzunluk);
    }
}
