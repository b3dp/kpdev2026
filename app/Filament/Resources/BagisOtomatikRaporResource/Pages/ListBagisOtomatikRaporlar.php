<?php

namespace App\Filament\Resources\BagisOtomatikRaporResource\Pages;

use App\Filament\Resources\BagisOtomatikRaporResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBagisOtomatikRaporlar extends ListRecords
{
    protected static string $resource = BagisOtomatikRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}