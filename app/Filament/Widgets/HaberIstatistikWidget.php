<?php

namespace App\Filament\Widgets;

use App\Enums\HaberDurumu;
use App\Models\Haber;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HaberIstatistikWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Yazar', 'Halkla İlişkiler']);
    }

    protected function getStats(): array
    {
        $bugunYayinlanan = Haber::query()
            ->where('durum', HaberDurumu::Yayinda->value)
            ->whereDate('yayin_tarihi', today())
            ->count();

        $buAyYayinlanan = Haber::query()
            ->where('durum', HaberDurumu::Yayinda->value)
            ->whereYear('yayin_tarihi', now()->year)
            ->whereMonth('yayin_tarihi', now()->month)
            ->count();

        $onayBekleyen = Haber::query()
            ->where('durum', HaberDurumu::Incelemede->value)
            ->count();

        return [
            Stat::make('Bugün Yayınlanan', (string) $bugunYayinlanan)
                ->description('Bugün yayına alınan haber sayısı')
                ->color('success'),
            Stat::make('Bu Ay', (string) $buAyYayinlanan)
                ->description('Bu ay yayımlanan toplam haber')
                ->color('primary'),
            Stat::make('Onay Bekleyen', (string) $onayBekleyen)
                ->description('İncelemede bekleyen haberler')
                ->color('warning'),
        ];
    }
}
