<?php

namespace Tests\Feature;

use App\Models\Bagis;
use App\Models\BagisKalemi;
use App\Models\BagisSepet;
use App\Models\BagisTuru;
use App\Models\EkayitDonem;
use App\Models\EkayitKayit;
use App\Models\EkayitOgrenciBilgisi;
use App\Models\EkayitSinif;
use App\Models\EkayitVeliBilgisi;
use App\Models\Kurum;
use App\Models\MezunProfil;
use App\Models\Uye;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UyeProfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_giris_yapan_uye_profil_sayfasini_gorebilir(): void
    {
        $uye = Uye::query()->create([
            'ad_soyad' => 'Ahmet Yilmaz',
            'telefon' => '05550000000',
            'eposta' => 'ahmet@example.com',
            'sifre' => 'EskiSifre123!',
            'durum' => 'aktif',
            'aktif' => true,
        ]);

        $response = $this->actingAs($uye, 'uye')->get(route('uye.profil.index'));

        $response->assertOk();
        $response->assertSee('Profilim');
        $response->assertSee('Ahmet Yilmaz');
    }

    public function test_uye_profil_bilgilerini_ve_tercihlerini_guncelleyebilir(): void
    {
        $uye = Uye::query()->create([
            'ad_soyad' => 'Ahmet Yilmaz',
            'telefon' => '05550000001',
            'eposta' => 'ahmet-ilk@example.com',
            'sifre' => 'EskiSifre123!',
            'durum' => 'aktif',
            'aktif' => true,
        ]);

        $response = $this->actingAs($uye, 'uye')->postJson(route('uye.profil.guncelle'), [
            'ad_soyad' => 'Mehmet Demir',
            'eposta' => 'mehmet@example.com',
            'mezuniyet_yili' => 2005,
            'meslek' => 'Yazilim Muhendisi',
            'ikamet_il' => 'Istanbul',
            'ikamet_ilce' => 'Uskudar',
            'acik_adres' => 'Cumhuriyet Mahallesi Test Sokak No: 10',
            'aciklama' => 'Dernek mezun listesi icin not',
            'nsosyal' => 'nsosyal.com/mehmetdemir',
            'facebook' => 'facebook.com/mehmetdemir',
            'youtube' => 'youtube.com/@mehmetdemir',
            'eposta_abonelik' => 1,
            'sms_abonelik' => 1,
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
        ]);

        $uye->refresh();

        $this->assertSame('Mehmet Demir', $uye->ad_soyad);
        $this->assertSame('mehmet@example.com', $uye->eposta);
        $this->assertTrue($uye->eposta_abonelik);
        $this->assertTrue($uye->sms_abonelik);

        $mezunProfil = MezunProfil::query()->where('uye_id', $uye->id)->first();

        $this->assertNotNull($mezunProfil);
        $this->assertSame(2005, $mezunProfil->mezuniyet_yili);
        $this->assertSame('Yazilim Muhendisi', $mezunProfil->meslek);
        $this->assertSame('Istanbul', $mezunProfil->ikamet_il);
        $this->assertSame('Uskudar', $mezunProfil->ikamet_ilce);
        $this->assertSame('Cumhuriyet Mahallesi Test Sokak No: 10', $mezunProfil->acik_adres);
        $this->assertSame('Dernek mezun listesi icin not', $mezunProfil->aciklama);
        $this->assertSame('nsosyal.com/mehmetdemir', $mezunProfil->nsosyal);
        $this->assertSame('facebook.com/mehmetdemir', $mezunProfil->facebook);
        $this->assertSame('youtube.com/@mehmetdemir', $mezunProfil->youtube);
    }

    public function test_mezun_basvurusu_acik_adres_ve_aciklama_ile_kaydedilir(): void
    {
        $response = $this->post(route('mezunlar.store'), [
            'ad_soyad' => 'Ali Veli',
            'telefon' => '05349838042',
            'mezuniyet_yili' => 2010,
            'meslek' => 'Ogretmen',
            'acik_adres' => 'Konak / Izmir',
            'aciklama' => 'Excel listesinden gelen aciklama',
            'kvkk' => 1,
        ]);

        $response->assertRedirect(route('mezunlar.index'));

        $uye = Uye::query()->where('telefon', '05349838042')->first();

        $this->assertNotNull($uye);

        $mezunProfil = MezunProfil::query()->where('uye_id', $uye->id)->first();

        $this->assertNotNull($mezunProfil);
        $this->assertSame('Konak / Izmir', $mezunProfil->acik_adres);
        $this->assertSame('Excel listesinden gelen aciklama', $mezunProfil->aciklama);
    }

    public function test_uye_profili_otp_tabanli_bilgilendirme_gosterir(): void
    {
        $uye = Uye::query()->create([
            'ad_soyad' => 'Ayse Kaya',
            'telefon' => '05550000002',
            'eposta' => 'ayse@example.com',
            'durum' => 'aktif',
            'aktif' => true,
        ]);

        $response = $this->actingAs($uye, 'uye')->get(route('uye.profil.index'));

        $response->assertOk();
        $response->assertDontSee('Şifre & Güvenlik');
        $response->assertSee('OTP ile yapılır');
    }

    public function test_uye_bagis_gecmisini_iletisim_bilgisiyle_gorebilir(): void
    {
        $uye = Uye::query()->create([
            'ad_soyad' => 'Bağışçı Kullanıcı',
            'telefon' => '05550000004',
            'eposta' => 'bagisci@example.com',
            'durum' => 'aktif',
            'aktif' => true,
        ]);

        $sepet = BagisSepet::query()->create([
            'session_id' => 'bagis-test-session',
            'durum' => 'tamamlandi',
            'toplam_tutar' => 750,
        ]);

        $bagisTuru = BagisTuru::query()->create([
            'ad' => 'Genel Bağış',
            'slug' => 'genel-bagis-profil',
            'ozellik' => 'normal',
            'fiyat_tipi' => 'serbest',
            'minimum_tutar' => 100,
            'oneri_tutarlar' => [100, 250, 500],
            'aciklama' => 'Profil testi için bağış',
            'acilis_tipi' => 'manuel',
            'kurban_modulu' => false,
            'aktif' => true,
        ]);

        $bagis = Bagis::query()->create([
            'bagis_no' => 'KP-TEST-0001',
            'sepet_id' => $sepet->id,
            'durum' => 'odendi',
            'toplam_tutar' => 750,
            'odeme_saglayici' => 'albaraka',
            'odeme_referans' => 'TEST-REF-1',
            'odeme_tarihi' => now(),
        ]);

        BagisKalemi::query()->create([
            'bagis_id' => $bagis->id,
            'bagis_turu_id' => $bagisTuru->id,
            'adet' => 1,
            'birim_fiyat' => 750,
            'toplam' => 750,
            'sahip_tipi' => 'kendi',
            'vekalet_onay' => false,
        ]);

        $bagis->kisiler()->create([
            'tip' => ['odeyen'],
            'ad_soyad' => 'Bağışçı Kullanıcı',
            'telefon' => '05550000004',
            'eposta' => 'bagisci@example.com',
        ]);

        $response = $this->actingAs($uye, 'uye')->get(route('uye.profil.index'));

        $response->assertOk();
        $response->assertSee('Bağış Geçmişim');
        $response->assertSee('KP-TEST-0001');
        $response->assertSee('750,00');
    }

    public function test_veli_ekayit_durumunu_profilinde_gorebilir(): void
    {
        $uye = Uye::query()->create([
            'ad_soyad' => 'Veli Deneme',
            'telefon' => '05550000003',
            'eposta' => 'veli@example.com',
            'durum' => 'aktif',
            'aktif' => true,
        ]);

        $kurum = Kurum::query()->create([
            'ad' => 'Test Kurumu',
            'slug' => 'test-kurumu',
            'tip' => 'kurs',
            'aktif' => true,
        ]);

        $donem = EkayitDonem::query()->create([
            'ad' => '2026-2027 Kayıt Dönemi',
            'ogretim_yili' => '2026-2027',
            'baslangic' => now()->subDay(),
            'bitis' => now()->addMonth(),
            'aktif' => true,
        ]);

        $sinif = EkayitSinif::query()->create([
            'ad' => '8. Sınıf Hafızlık',
            'ogretim_yili' => '2026-2027',
            'kurum_id' => $kurum->id,
            'donem_id' => $donem->id,
            'renk' => 'blue',
            'aktif' => true,
        ]);

        $kayit = EkayitKayit::query()->create([
            'sinif_id' => $sinif->id,
            'uye_id' => $uye->id,
            'durum' => 'reddedildi',
            'durum_notu' => 'Sayın {AD_SOYAD}, {SINIF} sınıfı başvurunuz kontenjan dolduğundan kabul edilememiştir.',
            'durum_tarihi' => now(),
        ]);

        EkayitOgrenciBilgisi::query()->create([
            'kayit_id' => $kayit->id,
            'ad_soyad' => 'Ogrenci Deneme',
            'tc_kimlik' => '12345678901',
            'dogum_tarihi' => '2012-05-20',
        ]);

        EkayitVeliBilgisi::query()->create([
            'kayit_id' => $kayit->id,
            'ad_soyad' => 'Veli Deneme',
            'eposta' => 'veli@example.com',
            'telefon_1' => '05550000003',
        ]);

        $response = $this->actingAs($uye, 'uye')->get(route('uye.profil.index'));

        $response->assertOk();
        $response->assertSee('E-Kayıt Takibi');
        $response->assertSee('Ogrenci Deneme');
        $response->assertSee('Reddedildi');
        $response->assertSee('Sayın Ogrenci Deneme, 8. Sınıf Hafızlık sınıfı başvurunuz kontenjan dolduğundan kabul edilememiştir.');
        $response->assertDontSee('{AD_SOYAD}');
        $response->assertDontSee('{SINIF}');
    }
}
