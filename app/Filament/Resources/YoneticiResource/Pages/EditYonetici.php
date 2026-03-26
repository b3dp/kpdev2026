<?php

namespace App\Filament\Resources\YoneticiResource\Pages;

use App\Filament\Resources\YoneticiResource;
use Filament\Resources\Pages\EditRecord;

class EditYonetici extends EditRecord
{
    protected static string $resource = YoneticiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
