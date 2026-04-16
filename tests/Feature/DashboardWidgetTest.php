<?php

namespace Tests\Feature;

use App\Filament\Widgets\EkayitBasvuruDurumWidget;
use App\Filament\Widgets\MezunlarIstatistikWidget;
use App\Models\EkayitDonem;
use App\Models\EkayitKayit;
use App\Models\EkayitSinif;
use App\Models\Kurum;
use App\Models\Yonetici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_mezunlar_widgeti_admin_ve_halkla_iliskiler_icin_gorunur(): void
    {
        $admin = $this->yoneticiOlustur('Admin Kullanici', 'admin-widget@example.com', 'Admin');
        $halkla = $this->yoneticiOlustur('Halkla Kullanici', 'halkla-widget@example.com', 'Halkla İlişkiler');
        $kurs = $this->yoneticiOlustur('Kurs Kullanici', 'kurs-widget@example.com', 'Kurs Yöneticisi');

        $this->actingAs($admin, 'admin');
        $this->assertTrue(MezunlarIstatistikWidget::canView());

        $this->actingAs($halkla, 'admin');
        $this->assertTrue(MezunlarIstatistikWidget::canView());

        $this->actingAs($kurs, 'admin');
        $this->assertFalse(MezunlarIstatistikWidget::canView());
    }

    public function test_ekayit_widgeti_sinif_bazli_sayilari_hesaplar(): void
    {
        $kursYoneticisi = $this->yoneticiOlustur('Kurs Kullanici', 'kurs-ekayit@example.com', 'Kurs Yöneticisi');

        $kurum = Kurum::query()->create([
            'ad' => 'Test Kurumu',
            'slug' => 'test-kurumu-widget',
            'tip' => 'kurs',
            'aktif' => true,
        ]);

        $donem = EkayitDonem::query()->create([
            'ad' => '2026-2027',
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

        foreach (['beklemede', 'beklemede', 'onaylandi', 'reddedildi', 'yedek'] as $durum) {
            EkayitKayit::query()->create([
                'sinif_id' => $sinif->id,
                'durum' => $durum,
            ]);
        }

        $this->actingAs($kursYoneticisi, 'admin');

        $widget = app(EkayitBasvuruDurumWidget::class);
        $siniflar = $widget->getSiniflerWithStats();

        $this->assertTrue(EkayitBasvuruDurumWidget::canView());
        $this->assertCount(1, $siniflar);
        $this->assertSame(5, $siniflar[0]['basvuru']);
        $this->assertSame(2, $siniflar[0]['bekleyen']);
        $this->assertSame(1, $siniflar[0]['onaylanan']);
        $this->assertSame(1, $siniflar[0]['reddedilen']);
        $this->assertSame(1, $siniflar[0]['yedek']);
    }

    private function yoneticiOlustur(string $adSoyad, string $eposta, string $rol): Yonetici
    {
        Role::findOrCreate($rol, 'admin');

        $yonetici = Yonetici::query()->create([
            'ad_soyad' => $adSoyad,
            'eposta' => $eposta,
            'sifre' => 'Test1234!',
            'telefon' => '555' . random_int(1000000, 9999999),
            'aktif' => true,
        ]);

        $yonetici->assignRole($rol);

        return $yonetici;
    }
}