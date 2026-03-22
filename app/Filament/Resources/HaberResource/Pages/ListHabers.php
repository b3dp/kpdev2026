<?php

namespace App\Filament\Resources\HaberResource\Pages;

use App\Filament\Resources\HaberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHabers extends ListRecords
{
    protected static string $resource = HaberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
