<?php

namespace App\Filament\Resources\SmsKisiResource\Pages;

use App\Filament\Resources\SmsKisiResource;
use App\Models\SmsKisi;
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
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function beforeCreate(): void
    {
        $telefon = SmsKisiResource::telefonNormalize((string) ($this->data['telefon'] ?? ''));

        if (SmsKisi::query()->where('telefon', $telefon)->exists()) {
            Notification::make()
                ->title('Bu numara zaten kayıtlı. Mevcut kayıt üzerinden listeye ekleyebilirsiniz.')
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
