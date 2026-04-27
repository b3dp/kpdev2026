<?php

namespace Tests\Feature;

use App\Enums\BagisDurumu;
use App\Jobs\MakbuzOlusturJob;
use App\Models\Bagis;
use App\Models\BagisTuru;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BagisMockOdemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bu testler test (mock) ödeme modunu doğrular; Albaraka entegrasyonu kapalı
        config([
            'services.albaraka.aktif' => false,
            'services.bagis.test_mode' => true,
        ]);
    }

    public function test_mock_bagis_odeme_basarili_olarak_tamamlanir(): void
    {
        Queue::fake();

        $bagisTuru = BagisTuru::query()->create([
            'ad' => 'Genel Bağış',
            'slug' => 'genel-bagis',
            'ozellik' => 'normal',
            'fiyat_tipi' => 'serbest',
            'minimum_tutar' => 100,
            'oneri_tutarlar' => [100, 250, 500],
            'aciklama' => 'Test amaçlı genel bağış',
            'acilis_tipi' => 'manuel',
            'kurban_modulu' => false,
            'aktif' => true,
        ]);

        $response = $this->postJson(route('bagis.odeme'), [
            'slug' => $bagisTuru->slug,
            'tutar' => 500,
            'adet' => 1,
            'sahip_tipi' => 'kendi',
            'odeme_yontemi' => 'albaraka',
            'kart_no' => '4111 1111 1111 1111',
            'kart_sahibi' => 'Test Bağışçı',
            'son_kullanma_ay' => '12',
            'son_kullanma_yil' => (string) (now()->year + 1),
            'cvv' => '123',
            'form_verisi' => [
                'odeyen_ad_soyad' => 'Test Bağışçı',
                'odeyen_eposta' => 'bagisci@example.com',
                'odeyen_telefon' => '05551234567',
                'odeyen_tc' => '12345678901',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'test_modu' => true,
            ]);

        $bagis = Bagis::query()->with(['kalemler', 'kisiler'])->first();

        $this->assertNotNull($bagis);
        $this->assertSame(BagisDurumu::Odendi, $bagis->durum);
        $this->assertSame('500.00', (string) $bagis->toplam_tutar);
        $this->assertNotNull($bagis->odeme_tarihi);
        $this->assertStringStartsWith('TEST-', (string) $bagis->odeme_referans);
        $this->assertCount(1, $bagis->kalemler);
        $this->assertCount(1, $bagis->kisiler);
        $this->assertSame('Test Bağışçı', $bagis->odeyenKisi()?->ad_soyad);

        Queue::assertPushed(MakbuzOlusturJob::class);
    }

    public function test_mock_bagis_odeme_hatali_test_kartinda_hata_doner(): void
    {
        $bagisTuru = BagisTuru::query()->create([
            'ad' => 'Zekat',
            'slug' => 'zekat',
            'ozellik' => 'normal',
            'fiyat_tipi' => 'serbest',
            'minimum_tutar' => 100,
            'oneri_tutarlar' => [100, 250, 500],
            'aciklama' => 'Test amaçlı zekat',
            'acilis_tipi' => 'manuel',
            'kurban_modulu' => false,
            'aktif' => true,
        ]);

        $response = $this->postJson(route('bagis.odeme'), [
            'slug' => $bagisTuru->slug,
            'tutar' => 250,
            'adet' => 1,
            'sahip_tipi' => 'kendi',
            'odeme_yontemi' => 'albaraka',
            'kart_no' => '4000 0000 0000 0002',
            'kart_sahibi' => 'Hatalı Kart',
            'son_kullanma_ay' => '12',
            'son_kullanma_yil' => (string) (now()->year + 1),
            'cvv' => '123',
            'form_verisi' => [
                'odeyen_ad_soyad' => 'Hatalı Kart',
                'odeyen_eposta' => 'hatali@example.com',
                'odeyen_telefon' => '05550000011',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Test kartı yetersiz bakiye senaryosuna düştü.',
            ]);

        $this->assertDatabaseCount('bagislar', 0);
    }
}
