<?php

namespace App\Filament\Resources\BagisTuruResource\Pages;

use App\Filament\Resources\BagisTuruResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBagisTurus extends ListRecords
{
    protected static string $resource = BagisTuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
