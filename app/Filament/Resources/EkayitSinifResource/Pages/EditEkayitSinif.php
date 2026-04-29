<?php

namespace App\Filament\Resources\EkayitSinifResource\Pages;

use App\Filament\Resources\EkayitSinifResource;
use App\Services\EkayitSinifGorselService;
use Filament\Resources\Pages\EditRecord;

class EditEkayitSinif extends EditRecord
{
    protected static string $resource = EkayitSinifResource::class;

    protected function afterSave(): void
    {
        app(EkayitSinifGorselService::class)->gorselleriIsleVeKaydet($this->record, $this->data);
    }
}
