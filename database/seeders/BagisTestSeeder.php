<?php

namespace Database\Seeders;

use App\Enums\BagisDurumu;
use App\Enums\OdemeSaglayici;
use App\Enums\SepetDurumu;
use App\Models\Bagis;
use App\Models\BagisKalemi;
use App\Models\BagisKisi;
use App\Models\BagisSepet;
use App\Models\BagisSepetSatir;
use App\Models\BagisTuru;
use App\Models\OdemeHatasi;
use App\Services\BagisNoService;
use Illuminate\Database\Seeder;
use RuntimeException;

class BagisTestSeeder extends Seeder
{
    public function run(): void
    {
        $turler = BagisTuru::query()
            ->whereIn('ad', ['Zekat', 'Fitre', 'Fidye', 'Küçükbaş Kurban', 'Büyükbaş Kurban Hissesi', 'Genel Bağış'])
            ->pluck('id', 'ad');

        $beklenen = ['Zekat', 'Fitre', 'Fidye', 'Küçükbaş Kurban', 'Büyükbaş Kurban Hissesi', 'Genel Bağış'];
        foreach ($beklenen as $ad) {
            if (! isset($turler[$ad])) {
                throw new RuntimeException("Bağış türü bulunamadı: {$ad}");
            }
        }

        $zaman = now()->setTime(10, 0, 0);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(1),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000001',
            'kalemler' => [
                ['tur_id' => $turler['Zekat'], 'adet' => 1, 'birim_fiyat' => 500, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'Ahmet Yılmaz', 'tc_kimlik' => '12345678901', 'telefon' => '05321234567', 'eposta' => 'ahmet@example.com'],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(2),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000002',
            'kalemler' => [
                ['tur_id' => $turler['Fitre'], 'adet' => 1, 'birim_fiyat' => 120, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'Fatma Kaya', 'tc_kimlik' => '23456789012', 'telefon' => '05332345678'],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(3),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000003',
            'kalemler' => [
                ['tur_id' => $turler['Fidye'], 'adet' => 1, 'birim_fiyat' => 120, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'Mehmet Demir', 'tc_kimlik' => '34567890123', 'telefon' => '05343456789'],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(4),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000004',
            'kalemler' => [
                ['tur_id' => $turler['Zekat'], 'adet' => 1, 'birim_fiyat' => 1000, 'sahip_tipi' => 'baskasi', 'vekalet_onay' => true],
            ],
            'kisiler' => [
                ['tip' => ['odeyen'], 'ad_soyad' => 'Ayşe Çelik', 'tc_kimlik' => '45678901234', 'telefon' => '05354567890', 'eposta' => 'ayse@example.com'],
                [
                    'tip' => ['sahip'],
                    'kalem_index' => 0,
                    'ad_soyad' => 'Hasan Çelik',
                    'tc_kimlik' => '45678901235',
                    'telefon' => '05354567891',
                    'vekalet_ad_soyad' => 'Ayşe Çelik',
                    'vekalet_tc' => '45678901234',
                ],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(5),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000005',
            'kurban_aktarildi' => true,
            'kalemler' => [
                ['tur_id' => $turler['Küçükbaş Kurban'], 'adet' => 1, 'birim_fiyat' => 8500, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'Ali Şahin', 'tc_kimlik' => '56789012345', 'telefon' => '05365678901', 'eposta' => 'ali@example.com'],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(6),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000006',
            'kalemler' => [
                ['tur_id' => $turler['Küçükbaş Kurban'], 'adet' => 1, 'birim_fiyat' => 8500, 'sahip_tipi' => 'baskasi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen'], 'ad_soyad' => 'Zeynep Arslan', 'tc_kimlik' => '67890123456', 'telefon' => '05376789012'],
                ['tip' => ['sahip'], 'kalem_index' => 0, 'ad_soyad' => 'Mustafa Arslan', 'tc_kimlik' => '67890123457'],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(7),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000007',
            'kurban_aktarildi' => true,
            'kalemler' => [
                ['tur_id' => $turler['Büyükbaş Kurban Hissesi'], 'adet' => 5, 'birim_fiyat' => 7000, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen'], 'ad_soyad' => 'Hüseyin Koç', 'tc_kimlik' => '78901234567', 'telefon' => '05387890123', 'eposta' => 'huseyin@example.com'],
                ['tip' => ['hissedar'], 'kalem_index' => 0, 'ad_soyad' => 'Hüseyin Koç', 'tc_kimlik' => '78901234567', 'telefon' => '05387890123', 'hisse_no' => 1],
                ['tip' => ['hissedar'], 'kalem_index' => 0, 'ad_soyad' => 'Hatice Koç', 'tc_kimlik' => '78901234568', 'telefon' => '05387890124', 'hisse_no' => 2],
                ['tip' => ['hissedar'], 'kalem_index' => 0, 'ad_soyad' => 'İbrahim Koç', 'tc_kimlik' => '78901234569', 'telefon' => '05387890125', 'hisse_no' => 3],
                ['tip' => ['hissedar'], 'kalem_index' => 0, 'ad_soyad' => 'Emine Koç', 'tc_kimlik' => '78901234570', 'telefon' => '05387890126', 'hisse_no' => 4],
                ['tip' => ['hissedar'], 'kalem_index' => 0, 'ad_soyad' => 'Yusuf Koç', 'tc_kimlik' => '78901234571', 'telefon' => '05387890127', 'hisse_no' => 5],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(8),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000008',
            'kalemler' => [
                ['tur_id' => $turler['Zekat'], 'adet' => 1, 'birim_fiyat' => 750, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
                ['tur_id' => $turler['Fitre'], 'adet' => 1, 'birim_fiyat' => 120, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
                ['tur_id' => $turler['Fidye'], 'adet' => 1, 'birim_fiyat' => 120, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'Ramazan Yıldız', 'tc_kimlik' => '89012345678', 'telefon' => '05398901234', 'eposta' => 'ramazan@example.com'],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(9),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000009',
            'kurban_aktarildi' => true,
            'kalemler' => [
                ['tur_id' => $turler['Zekat'], 'adet' => 1, 'birim_fiyat' => 500, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
                ['tur_id' => $turler['Küçükbaş Kurban'], 'adet' => 1, 'birim_fiyat' => 8500, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'Sultan Aydın', 'tc_kimlik' => '90123456789', 'telefon' => '05309012345'],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(10),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Paytr->value,
            'odeme_referans' => 'PTR-2026-000001',
            'kalemler' => [
                ['tur_id' => $turler['Genel Bağış'], 'adet' => 1, 'birim_fiyat' => 250, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'Kemal Özkan', 'tc_kimlik' => '11223344556', 'telefon' => '05311223344', 'eposta' => 'kemal@example.com'],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(11),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000010',
            'kalemler' => [
                ['tur_id' => $turler['Fitre'], 'adet' => 1, 'birim_fiyat' => 120, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
                ['tur_id' => $turler['Genel Bağış'], 'adet' => 1, 'birim_fiyat' => 300, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'Merve Güneş', 'tc_kimlik' => '22334455667', 'telefon' => '05322334455'],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(12),
            'durum' => BagisDurumu::Hatali->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'kalemler' => [
                ['tur_id' => $turler['Zekat'], 'adet' => 1, 'birim_fiyat' => 1000, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'Serkan Polat', 'tc_kimlik' => '33445566778', 'telefon' => '05333445566'],
            ],
            'odeme_hatasi' => [
                'hata_kodu' => '51',
                'hata_mesaji' => 'Yetersiz bakiye',
                'kart_son_haneler' => '4242',
                'banka_adi' => 'Garanti BBVA',
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(13),
            'durum' => BagisDurumu::Beklemede->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'kalemler' => [
                ['tur_id' => $turler['Fidye'], 'adet' => 1, 'birim_fiyat' => 120, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'Neslihan Çetin', 'tc_kimlik' => '44556677889', 'telefon' => '05344556677'],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(14),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000011',
            'kurban_aktarildi' => true,
            'kalemler' => [
                ['tur_id' => $turler['Büyükbaş Kurban Hissesi'], 'adet' => 3, 'birim_fiyat' => 7000, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
                ['tur_id' => $turler['Zekat'], 'adet' => 1, 'birim_fiyat' => 2000, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen'], 'ad_soyad' => 'Recep Doğan', 'tc_kimlik' => '55667788990', 'telefon' => '05355667788', 'eposta' => 'recep@example.com'],
                ['tip' => ['hissedar'], 'kalem_index' => 0, 'ad_soyad' => 'Recep Doğan', 'tc_kimlik' => '55667788990', 'hisse_no' => 1],
                ['tip' => ['hissedar'], 'kalem_index' => 0, 'ad_soyad' => 'Fatma Doğan', 'tc_kimlik' => '55667788991', 'hisse_no' => 2],
                ['tip' => ['hissedar'], 'kalem_index' => 0, 'ad_soyad' => 'Ahmet Doğan', 'tc_kimlik' => '55667788992', 'hisse_no' => 3],
            ],
        ]);

        $this->bagisOlustur([
            'created_at' => $zaman->copy()->addMinute(15),
            'durum' => BagisDurumu::Odendi->value,
            'odeme_saglayici' => OdemeSaglayici::Albaraka->value,
            'odeme_referans' => 'ALB-2026-000012',
            'kalemler' => [
                ['tur_id' => $turler['Genel Bağış'], 'adet' => 1, 'birim_fiyat' => 5000, 'sahip_tipi' => 'kendi', 'vekalet_onay' => false],
            ],
            'kisiler' => [
                ['tip' => ['odeyen', 'sahip'], 'ad_soyad' => 'İbrahim Kara', 'tc_kimlik' => '66778899001', 'telefon' => '05366778899', 'eposta' => 'ibrahim@example.com'],
            ],
        ]);
    }

    private function bagisOlustur(array $veri): Bagis
    {
        $zaman = $veri['created_at'] ?? now();
        $durum = $veri['durum'];
        $kalemler = $veri['kalemler'];
        $kisiler = $veri['kisiler'] ?? [];

        $sepetToplam = collect($kalemler)
            ->sum(fn (array $kalem) => ((int) $kalem['adet']) * ((float) $kalem['birim_fiyat']));

        $sepet = BagisSepet::query()->create([
            'uye_id' => null,
            'session_id' => 'test-sepet-'.uniqid('', true),
            'durum' => $durum === BagisDurumu::Beklemede->value ? SepetDurumu::OdemeBekleniyor->value : SepetDurumu::Tamamlandi->value,
            'toplam_tutar' => $sepetToplam,
            'created_at' => $zaman,
            'updated_at' => $zaman,
        ]);

        foreach ($kalemler as $kalem) {
            BagisSepetSatir::query()->create([
                'sepet_id' => $sepet->id,
                'bagis_turu_id' => $kalem['tur_id'],
                'adet' => $kalem['adet'],
                'birim_fiyat' => $kalem['birim_fiyat'],
                'toplam' => ((int) $kalem['adet']) * ((float) $kalem['birim_fiyat']),
                'sahip_tipi' => $kalem['sahip_tipi'],
                'vekalet_onay' => $kalem['vekalet_onay'] ?? false,
                'created_at' => $zaman,
            ]);
        }

        $bagisNo = app(BagisNoService::class)->uret();

        $bagis = Bagis::query()->create([
            'bagis_no' => $bagisNo,
            'sepet_id' => $sepet->id,
            'uye_id' => null,
            'durum' => $durum,
            'toplam_tutar' => $sepetToplam,
            'odeme_saglayici' => $veri['odeme_saglayici'] ?? OdemeSaglayici::Albaraka->value,
            'odeme_referans' => $veri['odeme_referans'] ?? null,
            'makbuz_yol' => null,
            'makbuz_gonderildi' => false,
            'kurban_aktarildi' => $veri['kurban_aktarildi'] ?? false,
            'odeme_tarihi' => $durum === BagisDurumu::Odendi->value ? $zaman : null,
            'created_at' => $zaman,
            'updated_at' => $zaman,
        ]);

        $olusanKalemler = [];
        foreach ($kalemler as $kalem) {
            $olusanKalemler[] = BagisKalemi::query()->create([
                'bagis_id' => $bagis->id,
                'bagis_turu_id' => $kalem['tur_id'],
                'adet' => $kalem['adet'],
                'birim_fiyat' => $kalem['birim_fiyat'],
                'toplam' => ((int) $kalem['adet']) * ((float) $kalem['birim_fiyat']),
                'sahip_tipi' => $kalem['sahip_tipi'],
                'vekalet_onay' => $kalem['vekalet_onay'] ?? false,
                'kurban_id' => null,
            ]);
        }

        foreach ($kisiler as $kisi) {
            $kalemId = null;
            if (array_key_exists('kalem_index', $kisi)) {
                $kalemId = $olusanKalemler[(int) $kisi['kalem_index']]->id ?? null;
            }

            BagisKisi::query()->create([
                'bagis_id' => $bagis->id,
                'kalem_id' => $kalemId,
                'uye_id' => null,
                'tip' => $kisi['tip'],
                'ad_soyad' => $kisi['ad_soyad'],
                'tc_kimlik' => $kisi['tc_kimlik'] ?? null,
                'telefon' => $kisi['telefon'] ?? null,
                'eposta' => $kisi['eposta'] ?? null,
                'hisse_no' => $kisi['hisse_no'] ?? null,
                'vekalet_ad_soyad' => $kisi['vekalet_ad_soyad'] ?? null,
                'vekalet_tc' => $kisi['vekalet_tc'] ?? null,
                'vekalet_telefon' => $kisi['vekalet_telefon'] ?? null,
            ]);
        }

        if (isset($veri['odeme_hatasi'])) {
            OdemeHatasi::query()->create([
                'bagis_id' => $bagis->id,
                'saglayici' => $veri['odeme_saglayici'] ?? OdemeSaglayici::Albaraka->value,
                'hata_kodu' => $veri['odeme_hatasi']['hata_kodu'] ?? null,
                'hata_mesaji' => $veri['odeme_hatasi']['hata_mesaji'] ?? null,
                'kart_son_haneler' => $veri['odeme_hatasi']['kart_son_haneler'] ?? null,
                'banka_adi' => $veri['odeme_hatasi']['banka_adi'] ?? null,
                'tutar' => $sepetToplam,
                'created_at' => $zaman,
            ]);
        }

        return $bagis;
    }
}
