<?php

namespace App\Filament\Resources\SmsListeResource\Pages;

use App\Filament\Resources\SmsListeResource;
use Filament\Resources\Pages\EditRecord;

class EditSmsListe extends EditRecord
{
    protected static string $resource = SmsListeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! auth()->user()->hasRole('Admin')) {
            $data['sahip_yonetici_id'] = auth()->id();
        }

        return $data;
    }
}
