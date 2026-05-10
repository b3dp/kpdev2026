<?php

namespace App\Filament\Pages;

use App\Models\SmsGonderim;
use App\Models\SmsKisi;
use App\Models\SmsKredi;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class SmsYonetimDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'SMS Yönetimi';

    protected static ?string $title = 'SMS Yönetim Paneli';

    protected static ?int $navigationSort = 999;

    protected static string $view = 'filament.pages.sms-yonetim-dashboard';

    public function getWidgets(): array
    {
        return [
            SmsStatsWidget::class,
            SmsGonderimListWidget::class,
        ];
    }
}

class SmsStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $bugunBasarili = SmsGonderim::whereDate('created_at', today())->sum('basarili') ?? 0;
        $bugunBasarisiz = SmsGonderim::whereDate('created_at', today())->sum('basarisiz') ?? 0;
        $rehberSayisi = SmsKisi::count();
        $kalanKredi = SmsKredi::getKalanKredi();

        return [
            Stat::make('Bugün Başarılı SMS', number_format($bugunBasarili, 0, ',', '.'))
                ->description('Gönderilen SMS sayısı')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Bugün Başarısız SMS', number_format($bugunBasarisiz, 0, ',', '.'))
                ->description('Hatalı SMS sayısı')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Rehber Sayısı', number_format($rehberSayisi, 0, ',', '.'))
                ->description('Toplam kişi')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->icon('heroicon-o-users'),

            Stat::make('Kalan Kredi', number_format($kalanKredi, 0, ',', '.'))
                ->description('Kullanılabilir SMS')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($kalanKredi < 1000 ? 'danger' : 'success')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}

class SmsGonderimListWidget extends TableWidget
{
    protected static ?string $heading = 'Son 10 SMS Gönderimi';

    public static function canView(): bool
    {
        return true;
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return SmsGonderim::query()->latest('created_at')->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('tip')
                ->label('Tip')
                ->formatStateUsing(fn (string $state): string => ucfirst($state))
                ->sortable(),

            TextColumn::make('alici_sayisi')
                ->label('Alıcı')
                ->sortable(),

            TextColumn::make('basarili')
                ->label('Başarılı')
                ->sortable(),

            TextColumn::make('basarisiz')
                ->label('Başarısız')
                ->sortable(),

            TextColumn::make('durum')
                ->label('Durum')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'tamamlandi' => 'success',
                    'basarisiz' => 'danger',
                    'gonderiliyor' => 'warning',
                    default => 'gray',
                }),

            TextColumn::make('created_at')
                ->label('Gönderme Tarihi')
                ->formatStateUsing(fn ($state) => $state
                    ? \Carbon\Carbon::parse($state)->format('d.m.Y H:i') : '—')
                ->sortable(),
        ];
    }
}
