<?php

namespace App\Enums;

enum KurumsalSablonu: string
{
    case Standart = 'standart';
    case Iletisim = 'iletisim';
    case Kurum = 'kurum';
    case Atolye = 'atolye';

    public function label(): string
    {
        return match ($this) {
            self::Standart => 'Standart',
            self::Iletisim => 'Iletisim',
            self::Kurum => 'Kurum',
            self::Atolye => 'Atolye',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $sablon) => [$sablon->value => $sablon->label()])
            ->all();
    }
}
