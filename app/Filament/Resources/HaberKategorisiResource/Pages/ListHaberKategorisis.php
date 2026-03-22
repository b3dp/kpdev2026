<?php

namespace App\Filament\Resources\HaberKategorisiResource\Pages;

use App\Filament\Resources\HaberKategorisiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHaberKategorisis extends ListRecords
{
    protected static string $resource = HaberKategorisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
