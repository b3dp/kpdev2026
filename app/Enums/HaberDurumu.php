<?php

namespace App\Enums;

enum HaberDurumu: string
{
    case Taslak = 'taslak';
    case Incelemede = 'incelemede';
    case Yayinda = 'yayinda';
    case Reddedildi = 'reddedildi';
    case Arsivde = 'arsivde';

    public function label(): string
    {
        return match ($this) {
            self::Taslak => 'Taslak',
            self::Incelemede => 'İncelemede',
            self::Yayinda => 'Yayında',
            self::Reddedildi => 'Reddedildi',
            self::Arsivde => 'Arşivde',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $durum) => [$durum->value => $durum->label()])
            ->all();
    }
}
