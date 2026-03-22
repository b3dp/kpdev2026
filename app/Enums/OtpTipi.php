<?php

namespace App\Enums;

enum OtpTipi: string
{
    case Giris = 'giris';
    case SifreSifirlama = 'sifre_sifirlama';
    case EpostaDogrulama = 'eposta_dogrulama';
    case TelefonDogrulama = 'telefon_dogrulama';

    public function label(): string
    {
        return match ($this) {
            self::Giris => 'Giriş',
            self::SifreSifirlama => 'Şifre Sıfırlama',
            self::EpostaDogrulama => 'E-posta Doğrulama',
            self::TelefonDogrulama => 'Telefon Doğrulama',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tip) => [$tip->value => $tip->label()])
            ->all();
    }
}