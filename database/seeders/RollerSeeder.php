<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RollerSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        $modulIzinleri = [
            'haberler'           => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil', 'yayinla', 'zamanli_yayinla'],
            'etkinlikler'        => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil', 'yayinla'],
            'kurumsal_sayfalar'  => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil', 'yayinla'],
            'kisiler'            => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil', 'onayla'],
            'kurumlar'           => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil', 'onayla'],
            'dergiler'           => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil'],
            'bagis'              => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil', 'onayla'],
            'kurban'             => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil', 'onayla'],
            'ekayit'             => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil'],
            'pazarlama_sms'      => ['listele', 'goruntule', 'kaydet', 'sil', 'gonder'],
            'pazarlama_eposta'   => ['listele', 'goruntule', 'kaydet', 'sil', 'gonder'],
            'yoneticiler'        => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil'],
            'roller'             => ['listele', 'goruntule', 'duzenle', 'kaydet', 'sil'],
            'loglar'             => ['listele', 'goruntule'],
        ];

        foreach ($modulIzinleri as $modul => $izinler) {
            foreach ($izinler as $izin) {
                Permission::updateOrCreate([
                    'name'       => "$modul.$izin",
                    'guard_name' => $guard,
                ], []);
            }
        }

        // Admin — tüm izinler
        $adminRol = Role::updateOrCreate(['name' => 'Admin', 'guard_name' => $guard], []);
        $adminRol->syncPermissions(Permission::where('guard_name', $guard)->get());

        // Editör
        $editorRol = Role::updateOrCreate(['name' => 'Editör', 'guard_name' => $guard], []);
        $editorRol->syncPermissions([
            'haberler.listele', 'haberler.goruntule', 'haberler.duzenle', 'haberler.kaydet', 'haberler.sil', 'haberler.yayinla', 'haberler.zamanli_yayinla',
            'etkinlikler.listele', 'etkinlikler.goruntule', 'etkinlikler.duzenle', 'etkinlikler.kaydet', 'etkinlikler.sil', 'etkinlikler.yayinla',
            'kurumsal_sayfalar.listele', 'kurumsal_sayfalar.goruntule', 'kurumsal_sayfalar.duzenle', 'kurumsal_sayfalar.kaydet', 'kurumsal_sayfalar.yayinla',
            'kisiler.listele', 'kisiler.goruntule', 'kisiler.duzenle', 'kisiler.kaydet',
            'kurumlar.listele', 'kurumlar.goruntule', 'kurumlar.duzenle', 'kurumlar.kaydet',
            'dergiler.listele', 'dergiler.goruntule', 'dergiler.duzenle', 'dergiler.kaydet', 'dergiler.sil',
        ]);

        // Yazar
        $yazarRol = Role::updateOrCreate(['name' => 'Yazar', 'guard_name' => $guard], []);
        $yazarRol->syncPermissions([
            'haberler.listele', 'haberler.goruntule', 'haberler.duzenle', 'haberler.kaydet',
            'etkinlikler.listele', 'etkinlikler.goruntule',
            'kurumsal_sayfalar.listele', 'kurumsal_sayfalar.goruntule',
        ]);

        // Muhasebe
        $muhasebeRol = Role::updateOrCreate(['name' => 'Muhasebe', 'guard_name' => $guard], []);
        $muhasebeRol->syncPermissions([
            'bagis.listele', 'bagis.goruntule',
            'kurban.listele', 'kurban.goruntule',
        ]);

        // E-Kayıt
        $ekayitRol = Role::updateOrCreate(['name' => 'E-Kayıt', 'guard_name' => $guard], []);
        $ekayitRol->syncPermissions([
            'ekayit.listele', 'ekayit.goruntule', 'ekayit.duzenle', 'ekayit.kaydet',
            'kurumlar.listele', 'kurumlar.goruntule',
        ]);

        // Kurban
        $kurbanRol = Role::updateOrCreate(['name' => 'Kurban', 'guard_name' => $guard], []);
        $kurbanRol->syncPermissions([
            'kurban.listele', 'kurban.goruntule', 'kurban.duzenle', 'kurban.kaydet', 'kurban.onayla',
            'bagis.listele', 'bagis.goruntule',
        ]);

        // Pazarlama
        $pazarlamaRol = Role::updateOrCreate(['name' => 'Pazarlama', 'guard_name' => $guard], []);
        $pazarlamaRol->syncPermissions([
            'pazarlama_sms.listele', 'pazarlama_sms.goruntule', 'pazarlama_sms.gonder',
            'pazarlama_eposta.listele', 'pazarlama_eposta.goruntule', 'pazarlama_eposta.gonder',
        ]);
    }
}
