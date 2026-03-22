<?php

namespace App\Filament\Resources\UyeResource\Pages;

use App\Filament\Resources\UyeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUye extends CreateRecord
{
    protected static string $resource = UyeResource::class;

    protected function afterCreate(): void
    {
        $this->eslesenKisiyiGuncelle();
    }

    protected function eslesenKisiyiGuncelle(): void
    {
        if (! $this->record->kisi) {
            return;
        }

        $this->record->kisi->forceFill([
            'telefon' => $this->record->telefon,
            'eposta' => $this->record->eposta,
        ])->save();
    }
}