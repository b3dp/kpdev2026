<?php

namespace App\Enums;

enum KurbanBildirimKanali: string
{
    case Sms = 'sms';
    case Eposta = 'eposta';

    public function label(): string
    {
        return match ($this) {
            self::Sms => 'SMS',
            self::Eposta => 'E-posta',
        };
    }
}