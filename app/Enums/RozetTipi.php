<?php

namespace App\Enums;

enum RozetTipi: string
{
    case Bagisci = 'bagisci';
    case Veli = 'veli';
    case Mezun = 'mezun';

    public function label(): string
    {
        return match ($this) {
            self::Bagisci => 'Bağışçı',
            self::Veli => 'Veli',
            self::Mezun => 'Mezun',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tip) => [$tip->value => $tip->label()])
            ->all();
    }
}