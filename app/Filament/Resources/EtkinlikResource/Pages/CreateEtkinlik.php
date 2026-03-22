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

        $anaGorsel = data_get($this->data, 'ana_gorsel_gecici');
        if (filled($anaGorsel)) {
            dispatch_sync(new GorselOptimizeJob($etkinlik->id, 'etkinlik', 'ana_gorsel', $anaGorsel, 1));
        }

        $galeriGorseller = array_values(array_filter((array) data_get($this->data, 'galeri_gorseller', [])));
        foreach ($galeriGorseller as $sira => $geciciYol) {
            dispatch_sync(new GorselOptimizeJob($etkinlik->id, 'etkinlik', 'galeri_gorseli', $geciciYol, $sira + 1));
        }
    }
}
