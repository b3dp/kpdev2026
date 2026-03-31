<?php

namespace App\Filament\Resources\SmsKisiResource\Pages;

use App\Filament\Resources\SmsKisiResource;
use App\Models\SmsKisi;
use Filament\Notifications\Notification;
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

        return $data;
    }

    protected function beforeSave(): void
    {
        $telefon = SmsKisiResource::telefonNormalize((string) ($this->data['telefon'] ?? ''));

        $varMi = SmsKisi::query()
            ->where('telefon', $telefon)
            ->whereKeyNot($this->record->id)
            ->exists();

        if ($varMi) {
            Notification::make()
                ->title('Bu numara zaten kayıtlı. Mevcut kayıt üzerinden listeye ekleyebilirsiniz.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        $this->record->listeler()->sync($this->listeIdler);
    }
}
