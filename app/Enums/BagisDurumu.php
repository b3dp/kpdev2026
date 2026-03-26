<?php

namespace App\Enums;

enum BagisDurumu: string
{
    case Beklemede = 'beklemede';
    case Odendi = 'odendi';
    case Hatali = 'hatali';
    case Iptal = 'iptal';
    case TerkEdildi = 'terk_edildi';

    public function label(): string
    {
        return match ($this) {
            self::Beklemede => 'Beklemede',
            self::Odendi => 'Ödendi',
            self::Hatali => 'Hatalı',
            self::Iptal => 'İptal',
            self::TerkEdildi => 'Terk Edildi',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $durum) => [$durum->value => $durum->label()])
            ->all();
    }
}
