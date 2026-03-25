<?php

namespace Database\Seeders;

use App\Models\EpostaSablon;
use Illuminate\Database\Seeder;

class EpostaSablonlarSeeder extends Seeder
{
    public function run(): void
    {
        $sablonlar = [
            [
                'kod'   => 'otp_giris',
                'ad'    => 'Giriş OTP Kodu',
                'konu'  => 'Giriş Doğrulama Kodu',
                'tip'   => 'otp',
            ],
            [
                'kod'   => 'otp_kayit',
                'ad'    => 'Kayıt OTP Kodu',
                'konu'  => 'Kayıt Doğrulama Kodu',
                'tip'   => 'otp',
            ],
            [
                'kod'   => 'sifre_sifirlama',
                'ad'    => 'Şifre Sıfırlama',
                'konu'  => 'Şifre Sıfırlama Talebi',
                'tip'   => 'bildirim',
            ],
            [
                'kod'   => 'uye_kayit_onay',
                'ad'    => 'Üye Kayıt Onayı',
                'konu'  => 'Hesabınız Oluşturuldu',
                'tip'   => 'onay',
            ],
            [
                'kod'   => 'bagis_makbuz',
                'ad'    => 'Bağış Makbuzu',
                'konu'  => 'Bağışınız İçin Teşekkürler',
                'tip'   => 'makbuz',
            ],
            [
                'kod'   => 'bagis_hatasi',
                'ad'    => 'Ödeme Hatası',
                'konu'  => 'Ödeme İşlemi Başarısız',
                'tip'   => 'bildirim',
            ],
            [
                'kod'   => 'kurban_kesildi',
                'ad'    => 'Kurban Kesim Bildirimi',
                'konu'  => 'Kurban Kesim Bildirimi',
                'tip'   => 'bildirim',
            ],
            [
                'kod'   => 'haber_onay',
                'ad'    => 'Haber Onay Talebi',
                'konu'  => 'Haber Onay Bekliyor',
                'tip'   => 'onay',
            ],
            [
                'kod'   => 'mezun_onaylandi',
                'ad'    => 'Mezun Kaydı Onaylandı',
                'konu'  => 'Mezun Kaydınız Onaylandı',
                'tip'   => 'onay',
            ],
            [
                'kod'   => 'mezun_reddedildi',
                'ad'    => 'Mezun Kaydı Reddedildi',
                'konu'  => 'Mezun Kaydınız Hakkında Bilgilendirme',
                'tip'   => 'bildirim',
            ],
            [
                'kod'   => 'ekayit_onay',
                'ad'    => 'E-Kayıt Onayı',
                'konu'  => 'E-Kayıt Başvurunuz Onaylandı',
                'tip'   => 'onay',
            ],
            [
                'kod'   => 'yonetici_alert',
                'ad'    => 'Yönetici Sistem Uyarısı',
                'konu'  => 'Sistem Bildirimi',
                'tip'   => 'sistem',
            ],
        ];

        foreach ($sablonlar as $sablon) {
            EpostaSablon::updateOrCreate(
                ['kod' => $sablon['kod']],
                array_merge($sablon, ['aktif' => true])
            );
        }
    }
}
