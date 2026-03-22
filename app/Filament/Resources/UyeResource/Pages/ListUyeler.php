<?php

namespace App\Filament\Resources\UyeResource\Pages;

use App\Filament\Resources\UyeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUyeler extends ListRecords
{
    protected static string $resource = UyeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}