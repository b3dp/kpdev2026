<?php

namespace App\Filament\Resources\YoneticiResource\Pages;

use App\Filament\Resources\YoneticiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListYoneticis extends ListRecords
{
    protected static string $resource = YoneticiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
