<?php

namespace App\Services;

use App\Models\EkayitSinif;

class SinifRenkService
{
    /** Tailwind renk atama sırası */
    private const RENKLER = [
        'blue', 'green', 'orange', 'purple', 'red',
        'amber', 'teal', 'lime', 'pink', 'yellow',
    ];

    /**
     * Verilen dönem için henüz kullanılmamış sıradaki rengi döndürür.
     * Tüm renkler kullanıldıysa başa döner (blue).
     */
    public function sonrakiRenk(int $donemId): string
    {
        $kullanilanlar = EkayitSinif::withTrashed()
            ->where('donem_id', $donemId)
            ->pluck('renk')
            ->toArray();

        foreach (self::RENKLER as $renk) {
            if (! in_array($renk, $kullanilanlar, true)) {
                return $renk;
            }
        }

        return 'blue';
    }
}
