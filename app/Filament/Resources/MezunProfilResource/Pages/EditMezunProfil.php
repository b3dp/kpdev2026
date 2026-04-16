<?php

namespace App\Filament\Resources\MezunProfilResource\Pages;

use App\Enums\RozetTipi;
use App\Filament\Resources\MezunProfilResource;
use App\Models\UyeRozet;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMezunProfil extends EditRecord
{
    protected static string $resource = MezunProfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->loadMissing('uye');

        if (! $this->record->uye) {
            return;
        }

        if ($this->record->durum === 'aktif') {
            app(\App\Services\KisiEslestirmeService::class)->mezunEslestir($this->record);

            return;
        }

        UyeRozet::query()
            ->where('uye_id', $this->record->uye_id)
            ->where('tip', RozetTipi::Mezun->value)
            ->where('kaynak_tip', 'mezun_profil')
            ->delete();
    }
}
