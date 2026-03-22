<?php

namespace Tests\Unit;

use App\Services\LevenshteinService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LevenshteinServiceTest extends TestCase
{
    public function test_turkce_karakterleri_normalize_ederek_benzerlik_skoru_hesaplar(): void
    {
        $servis = new LevenshteinService();

        $skor = $servis->benzerlikSkoru('Ahmet Yılmaz', 'Ahmet Ylmaz');

        $this->assertSame(96, $skor);
    }

    public function test_esik_uzerindeki_benzerleri_dondurur(): void
    {
        $servis = new LevenshteinService();
        $liste = new Collection(['Ahmet Yılmaz', 'Mehmet Kaya', 'Ahmet Yilmaz']);

        $sonuclar = $servis->benzerBul('Ahmet Yılmaz', $liste, 90);

        $this->assertCount(2, $sonuclar);
        $this->assertSame('Ahmet Yılmaz', $sonuclar->first()['kayit']);
    }
}