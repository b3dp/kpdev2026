<?php

namespace App\Filament\Resources\SmsListeResource\Pages;

use App\Filament\Resources\SmsListeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmsListeler extends ListRecords
{
    protected static string $resource = SmsListeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
