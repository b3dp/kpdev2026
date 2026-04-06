<?php

namespace Tests\Feature;

use App\Models\Bagis;
use App\Models\BagisSepet;
use App\Models\BagisTuru;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BagisSepetAkisiTest extends TestCase
{
    use RefreshDatabase;

    public function test_farkli_bagis_turleri_ve_ayni_turden_farkli_sahip_tipleri_sepette_birikebilir(): void
    {
        $zekat = BagisTuru::query()->create([
            'ad' => 'Zekat',
            'slug' => 'zekat',
            'ozellik' => 'normal',
            'fiyat_tipi' => 'serbest',
            'minimum_tutar' => 100,
            'oneri_tutarlar' => [100, 250, 500],
            'aciklama' => 'Zekat açıklaması',
            'acilis_tipi' => 'manuel',
            'kurban_modulu' => false,
            'aktif' => true,
        ]);

        $fitre = BagisTuru::query()->create([
            'ad' => 'Fitre',
            'slug' => 'fitre',
            'ozellik' => 'normal',
            'fiyat_tipi' => 'serbest',
            'minimum_tutar' => 50,
            'oneri_tutarlar' => [50, 100, 150],
            'aciklama' => 'Fitre açıklaması',
            'acilis_tipi' => 'manuel',
            'kurban_modulu' => false,
            'aktif' => true,
        ]);

        $this->postJson(route('bagis.sepete-ekle'), [
            'slug' => $zekat->slug,
            'tutar' => 500,
            'adet' => 1,
            'sahip_tipi' => 'kendi',
            'form_verisi' => ['odeyen_ad_soyad' => 'Test Kullanici'],
        ])->assertOk();

        $this->postJson(route('bagis.sepete-ekle'), [
            'slug' => $fitre->slug,
            'tutar' => 150,
            'adet' => 1,
            'sahip_tipi' => 'kendi',
            'form_verisi' => ['odeyen_ad_soyad' => 'Test Kullanici'],
        ])->assertOk();

        $this->postJson(route('bagis.sepete-ekle'), [
            'slug' => $zekat->slug,
            'tutar' => 250,
            'adet' => 1,
            'sahip_tipi' => 'baskasi',
            'form_verisi' => ['sahip_ad_soyad' => 'Yakini'],
        ])->assertOk();

        $this->assertCount(3, session('sepet', []));
        $this->assertDatabaseCount('bagis_sepet_satirlar', 3);
    }

    public function test_sepet_sayfasi_gorunur_ve_satir_silinebilir(): void
    {
        $bagisTuru = BagisTuru::query()->create([
            'ad' => 'Genel Bağış',
            'slug' => 'genel-bagis-sepet',
            'ozellik' => 'normal',
            'fiyat_tipi' => 'serbest',
            'minimum_tutar' => 100,
            'oneri_tutarlar' => [100, 250, 500],
            'aciklama' => 'Sepet testi',
            'acilis_tipi' => 'manuel',
            'kurban_modulu' => false,
            'aktif' => true,
        ]);

        $this->postJson(route('bagis.sepete-ekle'), [
            'slug' => $bagisTuru->slug,
            'tutar' => 500,
            'adet' => 1,
            'sahip_tipi' => 'kendi',
        ])->assertOk();

        $satirId = BagisSepet::query()->first()?->satirlar()->first()?->id;

        $this->get(route('bagis.sepet'))
            ->assertOk()
            ->assertSee('Sepetim')
            ->assertSee('Genel Bağış');

        $this->postJson(route('bagis.sepetten-cikar', ['satirId' => $satirId]))
            ->assertOk();

        $this->assertCount(0, session('sepet', []));
    }

    public function test_makbuz_durum_endpointi_hazir_linki_doner(): void
    {
        $sepet = BagisSepet::query()->create([
            'session_id' => 'makbuz-test',
            'durum' => 'tamamlandi',
            'toplam_tutar' => 300,
        ]);

        $bagis = Bagis::query()->create([
            'bagis_no' => 'KP-TEST-MAKBUZ',
            'sepet_id' => $sepet->id,
            'durum' => 'odendi',
            'toplam_tutar' => 300,
            'odeme_saglayici' => 'albaraka',
            'makbuz_yol' => 'pdf26/bagis/2026/test-makbuz.pdf',
            'makbuz_gonderildi' => true,
            'odeme_tarihi' => now(),
        ]);

        $this->getJson(route('bagis.makbuz-durum', ['bagisNo' => $bagis->bagis_no]))
            ->assertOk()
            ->assertJson([
                'hazir' => true,
                'bagis_no' => 'KP-TEST-MAKBUZ',
            ]);
    }
}
