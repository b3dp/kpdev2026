<?php

namespace App\Enums;

enum RaporPeriyot: string
{
    case Gunluk = 'gunluk';
    case Haftalik = 'haftalik';
    case Aylik = 'aylik';

    public function label(): string
    {
        return match ($this) {
            self::Gunluk => 'Günlük',
            self::Haftalik => 'Haftalık',
            self::Aylik => 'Aylık',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $periyot) => [$periyot->value => $periyot->label()])
            ->all();
    }
}
