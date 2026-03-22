<?php

namespace App\Enums;

enum KisiCinsiyet: string
{
    case Erkek = 'erkek';
    case Kadin = 'kadin';
    case Belirtilmemis = 'belirtilmemis';

    public function label(): string
    {
        return match ($this) {
            self::Erkek => 'Erkek',
            self::Kadin => 'Kadın',
            self::Belirtilmemis => 'Belirtilmemiş',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $cinsiyet) => [$cinsiyet->value => $cinsiyet->label()])
            ->all();
    }
}