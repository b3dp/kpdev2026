<?php

namespace App\Filament\Resources\MezunProfilResource\Pages;

use App\Filament\Resources\MezunProfilResource;
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
}
