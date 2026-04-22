<?php

namespace App\Support;

class KurumsalStatikSayfalar
{
    public static function tumu(): array
    {
        return [
            [
                'ad' => 'Banka Hesaplarımız',
                'slug' => 'banka-hesaplarimiz',
                'view' => 'pages.kurumsal.statik.banka-hesaplarimiz',
                'aktif' => true,
            ],
            [
                'ad' => 'Bağış Yöntemleri',
                'slug' => 'bagis-yontemleri',
                'view' => null,
                'aktif' => false,
            ],
            [
                'ad' => 'Bağış Koşulları',
                'slug' => 'bagis-kosullari',
                'view' => null,
                'aktif' => false,
            ],
            [
                'ad' => 'Sıkça Sorulan Sorular',
                'slug' => 'bagis-sss',
                'view' => null,
                'aktif' => false,
            ],
            [
                'ad' => 'İletişim Kanalları',
                'slug' => 'iletisim-kanallari',
                'view' => null,
                'aktif' => false,
            ],
            [
                'ad' => 'Destekçi Hakları',
                'slug' => 'destekci-haklari',
                'view' => null,
                'aktif' => false,
            ],
        ];
    }

    public static function slugIle(string $slug): ?array
    {
        foreach (self::tumu() as $sayfa) {
            if (($sayfa['slug'] ?? null) === $slug) {
                return $sayfa;
            }
        }

        return null;
    }
}
