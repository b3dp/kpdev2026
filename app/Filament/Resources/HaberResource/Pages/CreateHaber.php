<?php

namespace App\Filament\Resources\HaberResource\Pages;

use App\Filament\Resources\HaberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHaber extends CreateRecord
{
    protected static string $resource = HaberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['yonetici_id'] = auth()->id();

        return $data;
    }
}
