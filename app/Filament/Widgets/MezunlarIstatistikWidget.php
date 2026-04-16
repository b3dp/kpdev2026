<?php

namespace App\Filament\Widgets;

use App\Models\MezunProfil;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MezunlarIstatistikWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Halkla İlişkiler']);
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Onay Bekleyen', (string) MezunProfil::query()->where('durum', 'beklemede')->count())
                ->description('Onay bekleyen mezun başvuruları')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Toplam Mezun', (string) MezunProfil::query()->count())
                ->description('Sistemdeki toplam mezun profili')
                ->icon('heroicon-o-academic-cap')
                ->color('success'),

            Stat::make('Hafız', (string) MezunProfil::query()->where('hafiz', true)->count())
                ->description('Hafız olarak işaretlenen mezunlar')
                ->icon('heroicon-o-star')
                ->color('info'),
        ];
    }
}