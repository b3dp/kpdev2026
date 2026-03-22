<?php

namespace App\Filament\Resources\EtkinlikResource\Pages;

use App\Filament\Resources\EtkinlikResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEtkinliks extends ListRecords
{
    protected static string $resource = EtkinlikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
