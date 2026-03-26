<?php

namespace App\Filament\Resources\BagisResource\Pages;

use App\Filament\Resources\BagisResource;
use Filament\Resources\Pages\ListRecords;

class ListBagis extends ListRecords
{
    protected static string $resource = BagisResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            BagisResource::getWidgets()[0],
        ];
    }
}
