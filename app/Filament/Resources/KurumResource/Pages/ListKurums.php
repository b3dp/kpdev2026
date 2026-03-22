<?php

namespace App\Filament\Resources\KurumResource\Pages;

use App\Filament\Resources\KurumResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKurums extends ListRecords
{
    protected static string $resource = KurumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}