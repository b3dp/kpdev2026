<?php

namespace App\Filament\Resources\SmsKisiResource\Pages;

use App\Filament\Resources\SmsKisiResource;
use Filament\Resources\Pages\EditRecord;

class EditSmsKisi extends EditRecord
{
    protected static string $resource = SmsKisiResource::class;

    protected array $listeIdler = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['liste_idler'] = $this->record->listeler()->pluck('sms_listeler.id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->listeIdler = array_values(array_filter($data['liste_idler'] ?? []));

        unset($data['liste_idler']);

        $data['telefon'] = SmsKisiResource::telefonNormalize((string) ($data['telefon'] ?? ''));
        $data['telefon_2'] = SmsKisiResource::telefonNormalize((string) ($data['telefon_2'] ?? ''));
        if ($data['telefon_2'] === '') {
            $data['telefon_2'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->listeler()->sync($this->listeIdler);
    }
}
