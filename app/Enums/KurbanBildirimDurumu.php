<?php

namespace App\Enums;

enum KurbanBildirimDurumu: string
{
    case Gonderilmedi = 'gonderilmedi';
    case Kismi = 'kismi';
    case Tamamlandi = 'tamamlandi';

    public function label(): string
    {
        return match ($this) {
            self::Gonderilmedi => 'Gönderilmedi',
            self::Kismi => 'Kısmi',
            self::Tamamlandi => 'Tamamlandı',
        };
    }

    public function renk(): string
    {
        return match ($this) {
            self::Gonderilmedi => 'danger',
            self::Kismi => 'warning',
            self::Tamamlandi => 'success',
        };
    }
}