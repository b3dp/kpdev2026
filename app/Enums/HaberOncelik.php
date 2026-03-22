<?php

namespace App\Enums;

enum HaberOncelik: string
{
    case Normal = 'normal';
    case Manset = 'manset';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Manset => 'Manşet',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $oncelik) => [$oncelik->value => $oncelik->label()])
            ->all();
    }
}
