<?php

namespace App\Enums;

enum YoneticiRolu: string
{
    case Admin = 'Admin';
    case Editor = 'Editör';
    case Yazar = 'Yazar';
    case HalklaIliskiler = 'Halkla İlişkiler';
    case Muhasebe = 'Muhasebe';
    case EKayit = 'E-Kayıt';
    case Kurban = 'Kurban';
    case Pazarlama = 'Pazarlama';

    public function label(): string
    {
        return $this->value;
    }

    public static function varsayilanlar(): array
    {
        return array_column(self::cases(), 'value');
    }
}
