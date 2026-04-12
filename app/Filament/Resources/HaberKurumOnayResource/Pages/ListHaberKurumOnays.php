<?php

namespace App\Filament\Resources\HaberKurumOnayResource\Pages;

use App\Filament\Resources\HaberKurumOnayResource;
use Filament\Resources\Pages\ListRecords;

class ListHaberKurumOnays extends ListRecords
{
    protected static string $resource = HaberKurumOnayResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
