<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class KursYoneticisiRolSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        $rol = Role::query()->firstOrCreate([
            'name' => 'Kurs Yöneticisi',
            'guard_name' => $guard,
        ]);

        $izinler = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', [
                'pazarlama_sms.listele',
                'pazarlama_sms.goruntule',
                'pazarlama_sms.kaydet',
                'pazarlama_sms.gonder',
            ])
            ->pluck('name')
            ->all();

        $rol->syncPermissions($izinler);
    }
}
