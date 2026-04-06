<?php

namespace Tests\Feature;

use App\Models\Uye;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UyeAuthSayfalariTest extends TestCase
{
    use RefreshDatabase;

    public function test_uye_kayit_form_route_tanimlidir(): void
    {
        $this->assertSame('/uye-kayit', route('uye.kayit.form', absolute: false));
    }

    public function test_giris_sayfasi_hataya_dusmeden_acilir(): void
    {
        $response = $this->get(route('uye.giris.form'));

        $response->assertOk();
        $response->assertSee('Giriş Yap');
        $response->assertSee('OTP Gönder');
        $response->assertSee('Kayıt Ol');
    }

    public function test_uye_icin_sifre_guncelleme_routeu_yoktur(): void
    {
        $this->assertFalse(Route::has('uye.sifre.guncelle'));
    }

    public function test_standart_login_ve_register_aliaslari_tanimlidir(): void
    {
        $this->assertTrue(Route::has('login'));
        $this->assertTrue(Route::has('register'));
        $this->assertSame('/login', route('login', absolute: false));
        $this->assertSame('/register', route('register', absolute: false));
    }

    public function test_misafir_kullanici_profilim_sayfasindan_giris_ekranina_yonlendirilir(): void
    {
        $response = $this->get(route('uye.profil.index'));

        $response->assertRedirect(route('uye.giris.form'));
    }

    public function test_mevcut_uye_sifre_olsa_bile_otp_ile_giris_yapabilir(): void
    {
        Uye::query()->create([
            'ad_soyad' => 'Deneme Mezun',
            'telefon' => '05550000009',
            'eposta' => null,
            'sifre' => 'DenemeSifre123!',
            'durum' => 'aktif',
            'aktif' => true,
        ]);

        $response = $this->postJson(route('uye.giris.giris'), [
            'telefon' => '05550000009',
        ]);

        $response->assertOk()->assertJson([
            'step' => 'otp',
        ]);
    }
}
