<?php

namespace Database\Seeders;

use App\Models\EkayitDonem;
use App\Models\EkayitHazirMesaj;
use App\Models\EkayitSinif;
use App\Models\Kurum;
use Illuminate\Database\Seeder;

class EkayitSeeder extends Seeder
{
    public function run(): void
    {
        // Dönem
        $donem = EkayitDonem::updateOrCreate(
            ['ogretim_yili' => '2025-2026'],
            [
                'ad'        => '2025-2026 Kayıt Dönemi',
                'baslangic' => '2025-05-01 00:00:00',
                'bitis'     => '2025-06-25 23:59:59',
                'aktif'     => false,
            ]
        );

        // Kurum (örnek — kurumsal_sayfa_id dolu bir kurum gerekiyor)
        $kurum = Kurum::whereNotNull('kurumsal_sayfa_id')->first();

        if ($kurum) {
            // Sınıflar
            EkayitSinif::updateOrCreate(
                ['ad' => 'Hafızlık', 'donem_id' => $donem->id],
                ['ogretim_yili' => '2025-2026', 'kurum_id' => $kurum->id, 'renk' => 'blue', 'aktif' => true]
            );

            EkayitSinif::updateOrCreate(
                ['ad' => 'Yaz Kursu', 'donem_id' => $donem->id],
                ['ogretim_yili' => '2025-2026', 'kurum_id' => $kurum->id, 'renk' => 'green', 'aktif' => true]
            );

            EkayitSinif::updateOrCreate(
                ['ad' => 'Yatılı Program', 'donem_id' => $donem->id],
                ['ogretim_yili' => '2025-2026', 'kurum_id' => $kurum->id, 'renk' => 'orange', 'aktif' => true]
            );
        }

        // Hazır Mesajlar
        $mesajlar = [
            ['baslik' => 'Onay - Standart', 'tip' => 'onay',
             'metin' => 'Sayın {AD_SOYAD}, {SINIF} sınıfına kaydınız onaylanmıştır. Hayırlı olsun dileriz.'],
            ['baslik' => 'Onay - Belge Teslimi', 'tip' => 'onay',
             'metin' => 'Sayın {AD_SOYAD}, {SINIF} sınıfı kaydınız onaylanmıştır. Lütfen belgeleri {KURUM} adresine teslim ediniz.'],
            ['baslik' => 'Red - Kontenjan', 'tip' => 'red',
             'metin' => 'Sayın {AD_SOYAD}, {SINIF} sınıfı başvurunuz kontenjan dolduğundan kabul edilememiştir.'],
            ['baslik' => 'Red - Eksik Belge', 'tip' => 'red',
             'metin' => 'Sayın {AD_SOYAD}, başvurunuz eksik belge nedeniyle reddedilmiştir. Lütfen iletişime geçiniz.'],
            ['baslik' => 'Yedek - Sıra Bekliyor', 'tip' => 'yedek',
             'metin' => 'Sayın {AD_SOYAD}, {SINIF} sınıfı için yedek listesine alındınız. Sıranız geldiğinde bildirilecektir.'],
        ];

        foreach ($mesajlar as $mesaj) {
            EkayitHazirMesaj::updateOrCreate(
                ['baslik' => $mesaj['baslik']],
                ['tip' => $mesaj['tip'], 'metin' => $mesaj['metin'], 'aktif' => true]
            );
        }
    }
}
