<?php

namespace Tests\Feature;

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
}
