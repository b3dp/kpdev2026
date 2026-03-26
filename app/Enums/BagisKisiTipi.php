<?php

namespace App\Enums;

enum BagisKisiTipi: string
{
    case Odeyen = 'odeyen';
    case Sahip = 'sahip';
    case Hissedar = 'hissedar';

    public function label(): string
    {
        return match ($this) {
            self::Odeyen => 'Ödeyen',
            self::Sahip => 'Sahip',
            self::Hissedar => 'Hissedar',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tip) => [$tip->value => $tip->label()])
            ->all();
    }
}
