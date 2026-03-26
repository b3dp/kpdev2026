<?php

namespace Database\Seeders;

use App\Enums\BagisAcilisTipi;
use App\Enums\BagisFiyatTipi;
use App\Enums\BagisOzelligi;
use App\Models\BagisTuru;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BagisTurleriSeeder extends Seeder
{
    public function run(): void
    {
        $turler = [
            [
                'ad' => 'Zekat',
                'ozellik' => BagisOzelligi::Normal->value,
                'fiyat_tipi' => BagisFiyatTipi::Serbest->value,
                'minimum_tutar' => 100,
                'aktif' => true,
            ],
            [
                'ad' => 'Fitre',
                'ozellik' => BagisOzelligi::Normal->value,
                'fiyat_tipi' => BagisFiyatTipi::Sabit->value,
                'fiyat' => 120,
                'acilis_tipi' => BagisAcilisTipi::Otomatik->value,
                'acilis_hicri_ay' => 8,
                'acilis_hicri_gun' => 28,
                'kapanis_hicri_ay' => 9,
                'kapanis_hicri_gun' => 30,
                'kapanis_saat' => '20:00:00',
            ],
            [
                'ad' => 'Fidye',
                'ozellik' => BagisOzelligi::Normal->value,
                'fiyat_tipi' => BagisFiyatTipi::Sabit->value,
                'fiyat' => 120,
                'acilis_tipi' => BagisAcilisTipi::Otomatik->value,
                'acilis_hicri_ay' => 8,
                'acilis_hicri_gun' => 28,
                'kapanis_hicri_ay' => 9,
                'kapanis_hicri_gun' => 30,
                'kapanis_saat' => '20:00:00',
            ],
            [
                'ad' => 'Küçükbaş Kurban',
                'ozellik' => BagisOzelligi::KucukbasKurban->value,
                'fiyat_tipi' => BagisFiyatTipi::Sabit->value,
                'fiyat' => 8500,
                'acilis_tipi' => BagisAcilisTipi::Manuel->value,
            ],
            [
                'ad' => 'Büyükbaş Kurban Hissesi',
                'ozellik' => BagisOzelligi::BuyukbasKurban->value,
                'fiyat_tipi' => BagisFiyatTipi::Sabit->value,
                'fiyat' => 7000,
                'acilis_tipi' => BagisAcilisTipi::Manuel->value,
            ],
            [
                'ad' => 'Genel Bağış',
                'ozellik' => BagisOzelligi::Normal->value,
                'fiyat_tipi' => BagisFiyatTipi::Serbest->value,
                'minimum_tutar' => 50,
                'aktif' => true,
            ],
        ];

        foreach ($turler as $tur) {
            BagisTuru::query()->updateOrCreate(
                ['slug' => Str::slug($tur['ad'])],
                array_merge([
                    'ad' => $tur['ad'],
                    'slug' => Str::slug($tur['ad']),
                    'ozellik' => BagisOzelligi::Normal->value,
                    'fiyat_tipi' => BagisFiyatTipi::Sabit->value,
                    'acilis_tipi' => BagisAcilisTipi::Manuel->value,
                    'kurban_modulu' => false,
                    'aktif' => false,
                ], $tur)
            );
        }
    }
}
