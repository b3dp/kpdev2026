<?php

namespace App\Filament\Resources\HaberKisiOnayResource\Pages;

use App\Filament\Resources\HaberKisiOnayResource;
use Filament\Resources\Pages\ListRecords;

class ListHaberKisiOnays extends ListRecords
{
    protected static string $resource = HaberKisiOnayResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
