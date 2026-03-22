<?php

namespace App\Filament\Resources\EtkinlikResource\Pages;

use App\Filament\Resources\EtkinlikResource;
use App\Jobs\GorselOptimizeJob;
use Filament\Resources\Pages\CreateRecord;

class CreateEtkinlik extends CreateRecord
{
    protected static string $resource = EtkinlikResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['yonetici_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $etkinlik = $this->record;

        $anaGorsel = $this->tekDosyaYolu(data_get($this->data, 'ana_gorsel_gecici'));
        if (filled($anaGorsel)) {
            dispatch_sync(new GorselOptimizeJob($etkinlik->id, 'etkinlik', 'ana_gorsel', $anaGorsel, 1));
        }

        $galeriGorseller = $this->cokluDosyaYollari((array) data_get($this->data, 'galeri_gorseller', []));
        foreach ($galeriGorseller as $sira => $geciciYol) {
            dispatch_sync(new GorselOptimizeJob($etkinlik->id, 'etkinlik', 'galeri_gorseli', $geciciYol, $sira + 1));
        }
    }

    private function tekDosyaYolu(mixed $deger): ?string
    {
        if (is_string($deger) && filled($deger)) {
            return $deger;
        }

        if (is_array($deger)) {
            foreach ($deger as $oge) {
                if (is_string($oge) && filled($oge)) {
                    return $oge;
                }
            }
        }

        return null;
    }

    private function cokluDosyaYollari(array $degerler): array
    {
        $sonuc = [];

        foreach ($degerler as $deger) {
            if (is_string($deger) && filled($deger)) {
                $sonuc[] = $deger;
                continue;
            }

            if (is_array($deger)) {
                foreach ($deger as $oge) {
                    if (is_string($oge) && filled($oge)) {
                        $sonuc[] = $oge;
                    }
                }
            }
        }

        return array_values($sonuc);
    }
}
