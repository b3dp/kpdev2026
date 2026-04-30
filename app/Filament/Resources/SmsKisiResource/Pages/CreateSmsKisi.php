<?php

namespace App\Filament\Resources\SmsKisiResource\Pages;

use App\Filament\Resources\SmsKisiResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSmsKisi extends CreateRecord
{
    protected static string $resource = SmsKisiResource::class;

    protected array $listeIdler = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->listeIdler = array_values(array_filter($data['liste_idler'] ?? []));

        unset($data['liste_idler']);

        $data['telefon'] = SmsKisiResource::telefonNormalize((string) ($data['telefon'] ?? ''));
        $data['telefon_2'] = SmsKisiResource::telefonNormalize((string) ($data['telefon_2'] ?? ''));
        if ($data['telefon_2'] === '') {
            $data['telefon_2'] = null;
        }
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function beforeCreate(): void
    {
        $telefon = SmsKisiResource::telefonNormalize((string) ($this->data['telefon'] ?? ''));
        $telefon2 = SmsKisiResource::telefonNormalize((string) ($this->data['telefon_2'] ?? ''));

        if (SmsKisiResource::telefonKaydiVarMi($telefon)) {
            Notification::make()
                ->title('Bu numara zaten kayıtlı. Mevcut kayıt üzerinden listeye ekleyebilirsiniz.')
                ->danger()
                ->send();

            $this->halt();
        }

        if ($telefon2 !== '' && SmsKisiResource::telefonKaydiVarMi($telefon2)) {
            Notification::make()
                ->title('Telefon 2 numarası zaten kayıtlı. Mevcut kayıt üzerinden listeye ekleyebilirsiniz.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $this->record->listeler()->sync($this->listeIdler);
    }
}
