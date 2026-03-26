<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RollerSeeder::class,
            // AdminSeeder burada bilinçli olarak çağrılmıyor.
            // Canlı/var olan admin hesaplarının otomatik etkilenmesini engeller.
            KurumlarSeeder::class,
        ]);
    }
}
