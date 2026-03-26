<?php

namespace App\Filament\Widgets;

use App\Enums\BagisDurumu;
use App\Models\Bagis;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BagisIstatistikWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Muhasebe']);
    }

    protected function getStats(): array
    {
        $bugun = Bagis::query()->where('durum', BagisDurumu::Odendi->value)->whereDate('created_at', today());
        $buAy = Bagis::query()->where('durum', BagisDurumu::Odendi->value)->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month);
        $buYil = Bagis::query()->where('durum', BagisDurumu::Odendi->value)->whereYear('created_at', now()->year);

        return [
            Stat::make('Bugün', number_format((float) $bugun->sum('toplam_tutar'), 2, ',', '.').' TL')
                ->description($bugun->count().' adet')
                ->color('success'),
            Stat::make('Bu Ay', number_format((float) $buAy->sum('toplam_tutar'), 2, ',', '.').' TL')
                ->description($buAy->count().' adet')
                ->color('primary'),
            Stat::make('Bu Yıl', number_format((float) $buYil->sum('toplam_tutar'), 2, ',', '.').' TL')
                ->description($buYil->count().' adet')
                ->color('warning'),
        ];
    }
}
