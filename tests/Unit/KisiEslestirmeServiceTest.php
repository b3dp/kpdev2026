<?php

namespace Tests\Unit;

use App\Models\Kisi;
use App\Models\Uye;
use App\Services\KisiEslestirmeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KisiEslestirmeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_eslestir_var_olan_kisiyi_bulur_ve_sadece_bos_alanlari_gunceller(): void
    {
        $kisi = Kisi::query()->create([
            'ad' => 'Ahmet',
            'soyad' => 'Yilmaz',
            'telefon' => '5551112233',
            'eposta' => null,
        ]);

        $servis = app(KisiEslestirmeService::class);
        $sonuc = $servis->eslestir('5551112233', 'ahmet@example.com', 'Ahmet Yilmaz');

        $this->assertNotNull($sonuc);
        $this->assertSame($kisi->id, $sonuc->id);

        $kisi->refresh();
        $this->assertSame('5551112233', $kisi->telefon);
        $this->assertSame('ahmet@example.com', $kisi->eposta);
        $this->assertSame('Ahmet', $kisi->ad);
        $this->assertSame('Yilmaz', $kisi->soyad);
    }

    public function test_eslestir_kisi_yoksa_yeni_kisi_olusturur(): void
    {
        $servis = app(KisiEslestirmeService::class);
        $sonuc = $servis->eslestir('5559998877', 'yeni@example.com', 'Yeni Kisi');

        $this->assertNotNull($sonuc);
        $this->assertDatabaseHas('kisiler', [
            'id' => $sonuc->id,
            'telefon' => '5559998877',
            'eposta' => 'yeni@example.com',
            'ad' => 'Yeni',
            'soyad' => 'Kisi',
        ]);
    }

    public function test_rozet_ekle_duplicate_rozet_eklemez(): void
    {
        $uye = Uye::query()->create([
            'ad_soyad' => 'Rozet Test',
            'telefon' => '5553332211',
            'eposta' => 'rozet@example.com',
            'durum' => 'aktif',
            'aktif' => true,
            'sms_abonelik' => true,
            'eposta_abonelik' => true,
        ]);

        $servis = app(KisiEslestirmeService::class);

        $servis->rozetEkle($uye, 'bagisci', 'bagis', 10);
        $servis->rozetEkle($uye, 'bagisci', 'bagis', 10);

        $this->assertDatabaseCount('uye_rozetler', 1);
        $this->assertDatabaseHas('uye_rozetler', [
            'uye_id' => $uye->id,
            'tip' => 'bagisci',
            'kaynak_tip' => 'bagis',
            'kaynak_id' => 10,
        ]);
    }
}
