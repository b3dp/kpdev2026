<?php

namespace App\Filament\Resources\BagisTuruResource\Pages;

use App\Filament\Resources\BagisTuruResource;
use Filament\Resources\Pages\EditRecord;

class EditBagisTuru extends EditRecord
{
    protected static string $resource = BagisTuruResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
