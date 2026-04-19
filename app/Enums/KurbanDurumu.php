<?php

namespace App\Enums;

enum KurbanDurumu: string
{
    case Bekliyor = 'bekliyor';
    case Kesildi = 'kesildi';

    public function label(): string
    {
        return match ($this) {
            self::Bekliyor => 'Bekliyor',
            self::Kesildi => 'Kesildi',
        };
    }

    public function renk(): string
    {
        return match ($this) {
            self::Bekliyor => 'warning',
            self::Kesildi => 'success',
        };
    }
}