<?php

namespace App\Enums;

enum RobotsKurali: string
{
    case Index = 'index';
    case Noindex = 'noindex';
    case NoindexNofollow = 'noindex_nofollow';

    public function label(): string
    {
        return match ($this) {
            self::Index => 'index',
            self::Noindex => 'noindex',
            self::NoindexNofollow => 'noindex,nofollow',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $kural) => [$kural->value => $kural->label()])
            ->all();
    }
}
