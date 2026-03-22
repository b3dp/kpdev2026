<?php

namespace App\Enums;

enum UyeDurumu: string
{
    case Aktif = 'aktif';
    case Pasif = 'pasif';
    case Beklemede = 'beklemede';
    case Yasakli = 'yasakli';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Pasif => 'Pasif',
            self::Beklemede => 'Beklemede',
            self::Yasakli => 'Yasaklı',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $durum) => [$durum->value => $durum->label()])
            ->all();
    }
}