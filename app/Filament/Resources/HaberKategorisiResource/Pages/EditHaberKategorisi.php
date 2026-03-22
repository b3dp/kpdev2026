<?php

namespace App\Filament\Resources\HaberKategorisiResource\Pages;

use App\Filament\Resources\HaberKategorisiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHaberKategorisi extends EditRecord
{
    protected static string $resource = HaberKategorisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
