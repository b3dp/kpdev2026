<?php

namespace App\Filament\Resources\EkayitHazirMesajResource\Pages;

use App\Filament\Resources\EkayitHazirMesajResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEkayitHazirMesaj extends ListRecords
{
    protected static string $resource = EkayitHazirMesajResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
