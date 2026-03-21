<?php

namespace Database\Seeders;

use App\Models\Yonetici;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $yonetici = Yonetici::firstOrCreate(
            ['eposta' => 'baris@b3dp.com'],
            [
                'ad_soyad' => 'Barış Yılmaz',
                'sifre'    => 'Admin1234',
                'aktif'    => true,
            ]
        );

        $yonetici->syncRoles(['Admin']);
    }
}
