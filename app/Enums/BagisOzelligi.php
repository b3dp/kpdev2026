<?php

namespace App\Enums;

enum BagisOzelligi: string
{
    case Normal = 'normal';
    case KucukbasKurban = 'kucukbas_kurban';
    case BuyukbasKurban = 'buyukbas_kurban';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::KucukbasKurban => 'Küçükbaş Kurban',
            self::BuyukbasKurban => 'Büyükbaş Kurban',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $ozellik) => [$ozellik->value => $ozellik->label()])
            ->all();
    }
}
