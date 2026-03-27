<?php

namespace App\Enums;

enum EkayitDurumu: string
{
    case Beklemede  = 'beklemede';
    case Onaylandi  = 'onaylandi';
    case Reddedildi = 'reddedildi';
    case Yedek      = 'yedek';

    public function label(): string
    {
        return match ($this) {
            self::Beklemede  => 'Beklemede',
            self::Onaylandi  => 'Onaylandı',
            self::Reddedildi => 'Reddedildi',
            self::Yedek      => 'Yedek',
        };
    }

    public function renk(): string
    {
        return match ($this) {
            self::Beklemede  => 'warning',
            self::Onaylandi  => 'success',
            self::Reddedildi => 'danger',
            self::Yedek      => 'info',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $d) => [$d->value => $d->label()])
            ->all();
    }
}
