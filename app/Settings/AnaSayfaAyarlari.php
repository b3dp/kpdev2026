<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AnaSayfaAyarlari extends Settings
{
    public string $ust_bant_metni;

    public string $baslik_ust;

    public string $baslik_vurgulu;

    public string $baslik_alt;

    public string $alt_metin;

    public string $birinci_buton_metin;

    public string $birinci_buton_url;

    public string $ikinci_buton_metin;

    public string $ikinci_buton_url;

    public string $istatistik_1_sayi;

    public string $istatistik_1_etiket;

    public string $istatistik_2_sayi;

    public string $istatistik_2_etiket;

    public string $istatistik_3_sayi;

    public string $istatistik_3_etiket;

    public static function group(): string
    {
        return 'ana_sayfa';
    }
}