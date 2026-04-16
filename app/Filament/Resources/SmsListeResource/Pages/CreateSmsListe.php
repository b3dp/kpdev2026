<?php

namespace App\Filament\Resources\SmsListeResource\Pages;

use App\Filament\Resources\SmsListeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSmsListe extends CreateRecord
{
    protected static string $resource = SmsListeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()->hasRole('Admin')) {
            $data['sahip_yonetici_id'] = auth()->id();
        }

        return $data;
    }
}
