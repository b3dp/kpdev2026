<?php

namespace App\Filament\Resources\BagisTuruResource\Pages;

use App\Filament\Resources\BagisTuruResource;
use App\Services\BagisTuruGorselService;
use Filament\Resources\Pages\CreateRecord;

class CreateBagisTuru extends CreateRecord
{
    protected static string $resource = BagisTuruResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['gorsel_kare'] = null;
        $data['gorsel_dikey'] = null;
        $data['gorsel_orijinal'] = null;

        return $data;
    }

    protected function afterCreate(): void
    {
        app(BagisTuruGorselService::class)->gorselYatayiniIsleVeKaydet($this->record, $this->data);
    }
}
