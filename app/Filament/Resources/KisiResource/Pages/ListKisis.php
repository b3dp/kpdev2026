<?php

namespace App\Filament\Resources\KisiResource\Pages;

use App\Filament\Resources\KisiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKisis extends ListRecords
{
    protected static string $resource = KisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}