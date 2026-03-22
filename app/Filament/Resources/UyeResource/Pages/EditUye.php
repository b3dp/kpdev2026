<?php

namespace App\Filament\Resources\UyeResource\Pages;

use App\Filament\Resources\UyeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUye extends EditRecord
{
    protected static string $resource = UyeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
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