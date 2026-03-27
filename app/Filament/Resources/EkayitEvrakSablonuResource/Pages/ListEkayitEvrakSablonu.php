<?php

namespace App\Filament\Resources\EkayitEvrakSablonuResource\Pages;

use App\Filament\Resources\EkayitEvrakSablonuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEkayitEvrakSablonu extends ListRecords
{
    protected static string $resource = EkayitEvrakSablonuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
