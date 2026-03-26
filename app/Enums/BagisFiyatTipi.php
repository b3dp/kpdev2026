<?php

namespace App\Enums;

enum BagisFiyatTipi: string
{
    case Sabit = 'sabit';
    case Serbest = 'serbest';

    public function label(): string
    {
        return match ($this) {
            self::Sabit => 'Sabit',
            self::Serbest => 'Serbest',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tip) => [$tip->value => $tip->label()])
            ->all();
    }
}
