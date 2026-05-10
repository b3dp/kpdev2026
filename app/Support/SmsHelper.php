<?php

namespace App\Support;

class SmsHelper
{
    /**
     * SMS mesaj için karakter sayısını hesapla
     * - Türkçe özel karakterler (ş,Ş,ı,İ,ğ,Ğ,ü,Ü,ç,Ç,ö,Ö) = 2 karakter
     * - Diğer karakterler = 1 karakter
     * - 155 karakterlik bir SMS
     *
     * @param string $mesaj
     * @return int SMS sayısı
     */
    public static function smsAdediHesapla(string $mesaj): int
    {
        if (empty($mesaj)) {
            return 0;
        }

        $turkceKarakterler = ['ş', 'Ş', 'ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ç', 'Ç', 'ö', 'Ö'];
        $karakterSayisi = 0;

        // Her karakteri kontrol et
        for ($i = 0; $i < mb_strlen($mesaj, 'UTF-8'); $i++) {
            $karakter = mb_substr($mesaj, $i, 1, 'UTF-8');
            if (in_array($karakter, $turkceKarakterler, true)) {
                $karakterSayisi += 2;
            } else {
                $karakterSayisi += 1;
            }
        }

        // 155 karakterlik bir SMS
        return (int) ceil($karakterSayisi / 155);
    }
}
