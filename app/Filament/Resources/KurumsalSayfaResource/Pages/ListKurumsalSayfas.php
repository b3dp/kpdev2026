<?php

namespace App\Filament\Resources\KurumsalSayfaResource\Pages;

use App\Filament\Resources\KurumsalSayfaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKurumsalSayfas extends ListRecords
{
    protected static string $resource = KurumsalSayfaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
