<?php

namespace App\Enums;

enum EtkinlikDurumu: string
{
    case Taslak = 'taslak';
    case Yayinda = 'yayinda';
    case Tamamlandi = 'tamamlandi';
    case Iptal = 'iptal';

    public function label(): string
    {
        return match ($this) {
            self::Taslak => 'Taslak',
            self::Yayinda => 'Yayında',
            self::Tamamlandi => 'Tamamlandı',
            self::Iptal => 'İptal',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $durum) => [$durum->value => $durum->label()])
            ->all();
    }
}
