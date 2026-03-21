<?php

namespace App\Filament\Resources\YoneticiResource\Pages;

use App\Filament\Resources\YoneticiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Models\Yonetici;

class EditYonetici extends EditRecord
{
    protected static string $resource = YoneticiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (Yonetici $record) => $record->id === auth()->id()),
        ];
    }
}
