<?php

namespace App\Enums;

enum OdemeSaglayici: string
{
    case Albaraka = 'albaraka';
    case Paytr = 'paytr';

    public function label(): string
    {
        return match ($this) {
            self::Albaraka => 'Albaraka',
            self::Paytr => 'PayTR',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $saglayici) => [$saglayici->value => $saglayici->label()])
            ->all();
    }
}
