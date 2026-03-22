<?php

namespace Tests\Feature;

use App\Enums\KisiCinsiyet;
use App\Enums\KurumTipi;
use App\Models\Kisi;
use App\Models\Kurum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KisiKurumIliskiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kisi_ve_kurum_arasinda_iliski_kurulabilir(): void
    {
        $kisi = Kisi::query()->create([
            'ad' => 'Ahmet',
            'soyad' => 'Yilmaz',
            'cinsiyet' => KisiCinsiyet::Erkek,
            'telefon' => '05550000000',
            'eposta' => 'ahmet@example.com',
            'ai_onaylandi' => true,
            'ai_skoru' => 98,
        ]);

        $kurum = Kurum::query()->create([
            'ad' => 'Kestanepazari Imam Hatip Lisesi',
            'tip' => KurumTipi::ImamHatip,
            'il' => 'Izmir',
            'aktif' => true,
        ]);

        $kisi->kurumlar()->attach($kurum->id, [
            'gorev' => 'Ogretmen',
            'aktif' => true,
        ]);

        $this->assertSame('Ahmet Yilmaz', $kisi->fresh()->full_ad);
        $this->assertCount(1, $kisi->fresh()->kurumlar);
        $this->assertSame('Ogretmen', $kisi->fresh()->kurumlar->first()->pivot->gorev);
    }
}