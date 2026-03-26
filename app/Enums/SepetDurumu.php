<?php

namespace App\Enums;

enum SepetDurumu: string
{
    case Aktif = 'aktif';
    case OdemeBekleniyor = 'odeme_bekleniyor';
    case Tamamlandi = 'tamamlandi';
    case TerkEdildi = 'terk_edildi';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::OdemeBekleniyor => 'Ödeme Bekleniyor',
            self::Tamamlandi => 'Tamamlandı',
            self::TerkEdildi => 'Terk Edildi',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $durum) => [$durum->value => $durum->label()])
            ->all();
    }
}
