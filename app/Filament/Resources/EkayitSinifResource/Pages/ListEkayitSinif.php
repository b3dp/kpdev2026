<?php

namespace App\Filament\Resources\EkayitSinifResource\Pages;

use App\Filament\Resources\EkayitSinifResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEkayitSinif extends ListRecords
{
    protected static string $resource = EkayitSinifResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
