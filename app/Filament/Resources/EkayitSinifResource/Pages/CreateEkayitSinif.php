<?php

namespace App\Filament\Resources\EkayitSinifResource\Pages;

use App\Filament\Resources\EkayitSinifResource;
use App\Models\EkayitDonem;
use App\Services\EkayitSinifGorselService;
use App\Services\SinifRenkService;
use Filament\Resources\Pages\CreateRecord;

class CreateEkayitSinif extends CreateRecord
{
    protected static string $resource = EkayitSinifResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Renk seçilmediyse veya default 'blue' ise otomatik ata
        if (blank($data['renk'] ?? null) || $data['renk'] === 'blue') {
            $donemId = (int) ($data['donem_id'] ?? 0);
            if ($donemId > 0) {
                $data['renk'] = app(SinifRenkService::class)->sonrakiRenk($donemId);
            }
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        app(EkayitSinifGorselService::class)->gorselleriIsleVeKaydet($this->record, $this->data);
    }
}
