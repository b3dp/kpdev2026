<?php

namespace App\Enums;

enum KurumTipi: string
{
    case Muftuluk = 'muftuluk';
    case Ilce_Muftulugu = 'ilce_muftulugu';
    case Bakanlik = 'bakanlik';
    case KuranKursu = 'kuran_kursu';
    case ImamHatip = 'imam_hatip';
    case Vakif = 'vakif';
    case Dernek = 'dernek';
    case Cami = 'cami';
    case Ilkokul = 'ilkokul';
    case Ortaokul = 'ortaokul';
    case Lise = 'lise';
    case Universite = 'universite';
    case Diger = 'diger';

    public function label(): string
    {
        return match ($this) {
            self::Muftuluk => 'Müftülük',
            self::Ilce_Muftulugu => 'İlçe Müftülüğü',
            self::Bakanlik => 'Bakanlık',
            self::KuranKursu => 'Kur\'an Kursu',
            self::ImamHatip => 'İmam Hatip',
            self::Vakif => 'Vakıf',
            self::Dernek => 'Dernek',
            self::Cami => 'Cami',
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