<?php

namespace App\Enums;

enum KurumTipi: string
{
    case KuranKursu = 'kuran_kursu';
    case ImamHatip = 'imam_hatip';
    case Ilkokul = 'ilkokul';
    case Ortaokul = 'ortaokul';
    case Lise = 'lise';
    case Universite = 'universite';
    case Diger = 'diger';

    public function label(): string
    {
        return match ($this) {
            self::KuranKursu => 'Kur\'an Kursu',
            self::ImamHatip => 'İmam Hatip',
            self::Ilkokul => 'İlkokul',
            self::Ortaokul => 'Ortaokul',
            self::Lise => 'Lise',
            self::Universite => 'Üniversite',
            self::Diger => 'Diğer',
        };
    }

    public static function secenekler(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tip) => [$tip->value => $tip->label()])
            ->all();
    }
}