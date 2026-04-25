<?php

namespace App\Enums;

enum EtkinlikKatilimDurumu: string
{
    case Katiliyorum = 'katiliyorum';
    case Katilmiyorum = 'katilmiyorum';
    case Belirsiz = 'belirsiz';

    public function label(): string
    {
        return match ($this) {
            self::Katiliyorum => 'Katılıyorum',
            self::Katilmiyorum => 'Katılmıyorum',
            self::Belirsiz => 'Belirsiz',
        };
    }

    public function renk(): string
    {
        return match ($this) {
            self::Katiliyorum => 'success',
            self::Katilmiyorum => 'danger',
            self::Belirsiz => 'warning',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $durum) => [$durum->value => $durum->label()])
            ->all();
    }
}
