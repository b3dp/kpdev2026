<?php

namespace Database\Seeders;

use App\Enums\KurumTipi;
use App\Models\Kurum;
use Illuminate\Database\Seeder;

class KurumlarSeeder extends Seeder
{
    public function run(): void
    {
        $kurumlar = [
            [
                'ad' => 'Kestanepazari Haci Ahmet Dayhan Kur\'an Kursu',
                'tip' => KurumTipi::KuranKursu,
                'telefon' => '02324890001',
                'eposta' => 'iletisim@kestanepazari.org.tr',
                'il' => 'Izmir',
                'ilce' => 'Konak',
                'web_sitesi' => 'https://kestanepazari.org.tr',
                'aktif' => true,
            ],
            [
                'ad' => 'Kestanepazari Imam Hatip Lisesi',
                'tip' => KurumTipi::ImamHatip,
                'telefon' => '02324890002',
                'eposta' => 'okul@kestanepazari.org.tr',
                'il' => 'Izmir',
                'ilce' => 'Konak',
                'web_sitesi' => 'https://okul.kestanepazari.org.tr',
                'aktif' => true,
            ],
            [
                'ad' => 'Kestanepazari Egitim Merkezi',
                'tip' => KurumTipi::Diger,
                'telefon' => '02324890003',
                'eposta' => 'egitim@kestanepazari.org.tr',
                'il' => 'Izmir',
                'ilce' => 'Karabaglar',
                'web_sitesi' => 'https://egitim.kestanepazari.org.tr',
                'aktif' => true,
            ],
            [
                'ad' => 'Izmir Ilahiyat Destek Vakfi',
                'tip' => KurumTipi::Universite,
                'telefon' => '02324890004',
                'eposta' => 'vakif@example.org',
                'il' => 'Izmir',
                'ilce' => 'Bornova',
                'web_sitesi' => 'https://vakif.example.org',
                'aktif' => false,
            ],
            [
                'ad' => 'Anadolu Hafizlik Hazirlik Ortaokulu',
                'tip' => KurumTipi::Ortaokul,
                'telefon' => '02324890005',
                'eposta' => 'ortaokul@example.org',
                'il' => 'Manisa',
                'ilce' => 'Yunusemre',
                'web_sitesi' => 'https://ortaokul.example.org',
                'aktif' => true,
            ],
        ];

        foreach ($kurumlar as $kurum) {
            Kurum::updateOrCreate(
                ['ad' => $kurum['ad']],
                $kurum,
            );
        }
    }
}