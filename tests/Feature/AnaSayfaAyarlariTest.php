<?php

namespace Tests\Feature;

use App\Models\Yonetici;
use App\Settings\AnaSayfaAyarlari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnaSayfaAyarlariTest extends TestCase
{
    use RefreshDatabase;

    public function test_ana_sayfa_icerikleri_settings_ten_gelir(): void
    {
        $ayarlar = app(AnaSayfaAyarlari::class);
        $ayarlar->ust_bant_metni = 'Test Ust Bant';
        $ayarlar->baslik_ust = 'Test Baslik Ust';
        $ayarlar->baslik_vurgulu = 'Test Vurgulu';
        $ayarlar->baslik_alt = 'Test Baslik Alt';
        $ayarlar->alt_metin = 'Test alt metin icerigi';
        $ayarlar->birinci_buton_metin = 'Test Buton 1';
        $ayarlar->ikinci_buton_metin = 'Test Buton 2';
        $ayarlar->istatistik_1_sayi = '99';
        $ayarlar->istatistik_1_etiket = 'Test Etiket';
        $ayarlar->save();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Test Ust Bant');
        $response->assertSee('Test Baslik Ust', false);
        $response->assertSee('Test Vurgulu', false);
        $response->assertSee('Test Baslik Alt', false);
        $response->assertSee('Test alt metin icerigi');
        $response->assertSee('Test Buton 1');
        $response->assertSee('Test Buton 2');
        $response->assertSee('99');
        $response->assertSee('Test Etiket');
    }

    public function test_admin_ana_sayfa_ayarlari_panelini_gorebilir(): void
    {
        Role::findOrCreate('Admin', 'admin');

        $yonetici = Yonetici::query()->create([
            'ad_soyad' => 'Admin Kullanici',
            'eposta' => 'ana-sayfa-ayar-admin@example.com',
            'sifre' => Hash::make('password'),
            'telefon' => '05000000000',
            'aktif' => true,
        ]);

        $yonetici->assignRole('Admin');

        $response = $this->actingAs($yonetici, 'admin')->get('/yonetim/ana-sayfa-ayarlari');

        $response->assertOk();
        $response->assertSee('Ana Sayfa Ayarları');
    }
}