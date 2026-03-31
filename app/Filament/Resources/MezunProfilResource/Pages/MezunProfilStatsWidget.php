<?php

namespace App\Filament\Resources\MezunProfilResource\Pages;

use App\Models\MezunProfil;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MezunProfilStatsWidget extends BaseWidget
{
    public function getStats(): array
    {
        return [
            Stat::make('Toplam Mezun', MezunProfil::where('durum', 'aktif')->count())
                ->icon('heroicon-o-academic-cap')
                ->color('success'),

            Stat::make('Bekleyen Onay', MezunProfil::where('durum', 'beklemede')->count())
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Hafız Mezun', MezunProfil::where('hafiz', true)->where('durum', 'aktif')->count())
                ->icon('heroicon-o-star')
                ->color('info'),
        ];
    }
}
