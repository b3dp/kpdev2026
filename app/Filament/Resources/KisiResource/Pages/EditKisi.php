<?php

namespace App\Filament\Resources\KisiResource\Pages;

use App\Filament\Resources\KisiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKisi extends EditRecord
{
    protected static string $resource = KisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}