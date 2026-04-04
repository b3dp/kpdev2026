<?php

namespace Tests\Unit;

use App\Services\AramaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AramaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_kaydet_arama_sayisini_artirir(): void
    {
        $servis = app(AramaService::class);

        $servis->kaydetArama('Kurban');
        $servis->kaydetArama('Kurban');

        $this->assertDatabaseHas('arama_kayitlari', [
            'aranan_ifade' => 'Kurban',
            'arama_sayisi' => 2,
        ]);
    }

    public function test_getir_populer_aramalar_dinamik_ve_varsayilan_listeleri_birlestirir(): void
    {
        $servis = app(AramaService::class);

        $servis->kaydetArama('bağışçı bursu');
        $servis->kaydetArama('bağışçı bursu');
        $servis->kaydetArama('mezun buluşması');

        $sonuc = $servis->getirPopulerAramalar(8);

        $this->assertContains('bağışçı bursu', $sonuc);
        $this->assertContains('mezun buluşması', $sonuc);
        $this->assertContains('burs', $sonuc);
        $this->assertSame(count($sonuc), count(array_unique(array_map('mb_strtolower', $sonuc))));
    }
}
