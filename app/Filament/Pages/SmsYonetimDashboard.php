<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\SmsGonderim;
use App\Models\SmsKredi;
use App\Models\SmsKisi;

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
        // Bugün gönderilen SMS
        $bugunBasarili = SmsGonderim::whereDate('created_at', today())
            ->where('status', 'success')
            ->sum('sms_sayisi') ?? 0;
            
        $bugunBasarisiz = SmsGonderim::whereDate('created_at', today())
            ->where('status', 'failed')
            ->sum('sms_sayisi') ?? 0;

        // Rehber sayısı
        $rehberSayisi = SmsKisi::count();

        // Kalan kredi
        $kalanKredi = SmsKredi::getKalanKredi();

        return [
            Stat::make('Bugün Başarılı SMS', $bugunBasarili)
                ->description('Gönderilen SMS sayısı')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Bugün Başarısız SMS', $bugunBasarisiz)
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

    public function getDefaultTableSortColumn(): ?string
    {
        return 'created_at';
    }

    public function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return SmsGonderim::query()
            ->with('rehber')
            ->latest('created_at')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('rehber.ad')
                ->label('Rehber/Liste')
                ->searchable()
                ->sortable()
                ->wrap(),

            TextColumn::make('sms_sayisi')
                ->label('SMS Sayısı')
                ->sortable()
                ->alignment('center'),

            BadgeColumn::make('status')
                ->label('Durum')
                ->colors([
                    'success' => 'success',
                    'failed' => 'danger',
                    'pending' => 'warning',
                ])
                ->formatStateUsing(fn ($state) => match ($state) {
                    'success' => '✓ Başarılı',
                    'failed' => '✗ Başarısız',
                    'pending' => '⏳ Beklemede',
                    default => $state,
                }),

            TextColumn::make('created_at')
                ->label('Gönderme Tarihi')
                ->dateTime('d.m.Y H:i')
                ->sortable(),
        ];
    }
}
