<?php

namespace App\Filament\Widgets;

use App\Models\SmsGonderim;
use App\Models\SmsKredi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SmsIstatistikWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $kullanici = auth()->user();

        return $kullanici->can('pazarlama_sms.listele')
            || $kullanici->can('pazarlama_sms.goruntule')
            || $kullanici->can('pazarlama_sms.gonder');
    }

    protected function getStats(): array
    {
        $bugunBasarili = (int) (SmsGonderim::query()->whereDate('created_at', today())->sum('basarili') ?? 0);
        $bugunBasarisiz = (int) (SmsGonderim::query()->whereDate('created_at', today())->sum('basarisiz') ?? 0);
        $kalanKredi = SmsKredi::getKalanKredi();

        return [
            Stat::make('Bugün Başarılı SMS', number_format($bugunBasarili, 0, ',', '.'))
                ->description('Bugün başarılı teslim edilen SMS')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Bugün Başarısız SMS', number_format($bugunBasarisiz, 0, ',', '.'))
                ->description('Bugün başarısız olan SMS')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Kalan SMS Kredi', number_format($kalanKredi, 0, ',', '.'))
                ->description('Sistemdeki kullanılabilir kredi')
                ->icon('heroicon-o-banknotes')
                ->color($kalanKredi < 1000 ? 'warning' : 'primary'),
        ];
    }
}
