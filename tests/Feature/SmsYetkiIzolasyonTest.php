<?php

namespace Tests\Feature;

use App\Filament\Resources\SmsKisiResource;
use App\Filament\Resources\SmsListeResource;
use App\Models\SmsKisi;
use App\Models\SmsListe;
use App\Models\Yonetici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmsYetkiIzolasyonTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_tum_sms_rehber_ve_listelerini_gorebilir(): void
    {
        $admin = $this->yoneticiOlustur('Admin Kullanici', 'admin@example.com', 'Admin');
        $kursYoneticisi = $this->yoneticiOlustur('Kurs Yonetici', 'kurs@example.com', 'Kurs Yöneticisi');
        $halklaIliskiler = $this->yoneticiOlustur('Halkla Iliskiler', 'halkla@example.com', 'Halkla İlişkiler');

        $kursListesi = SmsListe::query()->create([
            'ad' => 'Kurs Listesi',
            'sahip_yonetici_id' => $kursYoneticisi->id,
        ]);

        $halklaListesi = SmsListe::query()->create([
            'ad' => 'Halkla Listesi',
            'sahip_yonetici_id' => $halklaIliskiler->id,
        ]);

        $adminListesi = SmsListe::query()->create([
            'ad' => 'Admin Listesi',
            'sahip_yonetici_id' => $admin->id,
        ]);

        SmsKisi::query()->create([
            'telefon' => '5551111111',
            'ad_soyad' => 'Kurs Kisisi',
            'created_by' => $kursYoneticisi->id,
        ])->listeler()->attach($kursListesi->id);

        SmsKisi::query()->create([
            'telefon' => '5552222222',
            'ad_soyad' => 'Halkla Kisisi',
            'created_by' => $halklaIliskiler->id,
        ])->listeler()->attach($halklaListesi->id);

        SmsKisi::query()->create([
            'telefon' => '5553333333',
            'ad_soyad' => 'Admin Kisisi',
            'created_by' => $admin->id,
        ])->listeler()->attach($adminListesi->id);

        $this->actingAs($admin, 'admin');

        $this->assertCount(3, SmsListeResource::getEloquentQuery()->get());
        $this->assertCount(3, SmsKisiResource::getEloquentQuery()->get());
        $this->assertCount(3, SmsKisiResource::erisebilirListeSecenekleri());
    }

    public function test_sms_yetkili_kullanici_sadece_kendi_rehber_ve_listelerini_gorur(): void
    {
        $kursYoneticisi = $this->yoneticiOlustur('Kurs Yonetici', 'kurs@example.com', 'Kurs Yöneticisi');
        $halklaIliskiler = $this->yoneticiOlustur('Halkla Iliskiler', 'halkla@example.com', 'Halkla İlişkiler');

        $kursListesi = SmsListe::query()->create([
            'ad' => 'Kurs Listesi',
            'sahip_yonetici_id' => $kursYoneticisi->id,
        ]);

        $halklaListesi = SmsListe::query()->create([
            'ad' => 'Halkla Listesi',
            'sahip_yonetici_id' => $halklaIliskiler->id,
        ]);

        $kursKisisi = SmsKisi::query()->create([
            'telefon' => '5554444444',
            'ad_soyad' => 'Kurs Kisisi',
            'created_by' => $kursYoneticisi->id,
        ]);
        $kursKisisi->listeler()->attach($kursListesi->id);

        $halklaKisisi = SmsKisi::query()->create([
            'telefon' => '5555555555',
            'ad_soyad' => 'Halkla Kisisi',
            'created_by' => $halklaIliskiler->id,
        ]);
        $halklaKisisi->listeler()->attach([$halklaListesi->id, $kursListesi->id]);

        $this->actingAs($kursYoneticisi, 'admin');

        $gorunenListeIdleri = SmsListeResource::getEloquentQuery()->pluck('id')->all();
        $gorunenKisiIdleri = SmsKisiResource::getEloquentQuery()->pluck('id')->all();
        $erisebilirListeSecenekleri = SmsKisiResource::erisebilirListeSecenekleri();

        $this->assertSame([$kursListesi->id], $gorunenListeIdleri);
        $this->assertSame([$kursKisisi->id], $gorunenKisiIdleri);
        $this->assertSame([$kursListesi->id => 'Kurs Listesi'], $erisebilirListeSecenekleri);
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