<?php

namespace App\Enums;

enum YetkiTipi: string
{
    case Listele = 'listele';
    case Goruntule = 'goruntule';
    case Duzenle = 'duzenle';
    case Kaydet = 'kaydet';
    case Sil = 'sil';
    case Yayinla = 'yayinla';
    case ZamanliYayinla = 'zamanli_yayinla';
    case Onayla = 'onayla';
    case Gonder = 'gonder';
}
