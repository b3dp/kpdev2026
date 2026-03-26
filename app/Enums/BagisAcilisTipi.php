<?php

namespace App\Enums;

enum BagisAcilisTipi: string
{
    case Manuel = 'manuel';
    case Otomatik = 'otomatik';

    public function label(): string
    {
        return match ($this) {
            self::Manuel => 'Manuel',
            self::Otomatik => 'Otomatik',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tip) => [$tip->value => $tip->label()])
            ->all();
    }
}
