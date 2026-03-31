<?php

namespace App\Filament\Resources\MezunProfilResource\Pages;

use App\Filament\Resources\MezunProfilResource;
use App\Models\MezunProfil;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\StatsOverviewWidget as StatsOverviewWidgetAlias;

class ListMezunProfiller extends ListRecords
{
    protected static string $resource = MezunProfilResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MezunProfilStatsWidget::class,
        ];
    }
}
