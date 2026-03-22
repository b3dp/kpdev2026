<?php

namespace App\Enums;

enum EtkinlikTipi: string
{
    case Fiziksel = 'fiziksel';
    case Online = 'online';
    case Hibrit = 'hibrit';

    public function label(): string
    {
        return match ($this) {
            self::Fiziksel => 'Fiziksel',
            self::Online => 'Online',
            self::Hibrit => 'Hibrit',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tip) => [$tip->value => $tip->label()])
            ->all();
    }
}
