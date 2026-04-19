<?php

namespace App\Enums;

enum KurbanBildirimSonucu: string
{
    case Gonderildi = 'gonderildi';
    case Basarisiz = 'basarisiz';

    public function label(): string
    {
        return match ($this) {
            self::Gonderildi => 'Gönderildi',
            self::Basarisiz => 'Başarısız',
        };
    }

    public function renk(): string
    {
        return match ($this) {
            self::Gonderildi => 'success',
            self::Basarisiz => 'danger',
        };
    }
}