<?php

namespace App\Filament\Resources\EkayitDonemResource\Pages;

use App\Filament\Resources\EkayitDonemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEkayitDonem extends ListRecords
{
    protected static string $resource = EkayitDonemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
