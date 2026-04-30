<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsExcelGonderimResource\Pages;
use App\Models\SmsExcelGonderim;
use Carbon\Carbon;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SmsExcelGonderimResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = SmsExcelGonderim::class;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Excel SMS Raporları';

    protected static ?string $modelLabel = 'Excel SMS Raporu';

    protected static ?string $pluralModelLabel = 'Excel SMS Raporları';

    protected static ?int $navigationSort = 45;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    public static function canViewAny(): bool
    {
        return static::izinlerdenBiriVarMi(['pazarlama_sms.listele', 'pazarlama_sms.goruntule', 'pazarlama_sms.gonder']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bekliyor' => 'gray',
                        'isleniyor' => 'warning',
                        'tamamlandi' => 'success',
                        'hatali' => 'danger',
                        default => 'gray',
                    })
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

                TextColumn::make('created_at')
                    ->label('Kayıt')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i:s') : '-')
                    ->sortable(),

                Panel::make([
                    ViewColumn::make('detay_paneli')
                        ->view('filament.sms.excel-rapor-detay-panel'),
                ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->filters([
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options([
                        'bekliyor' => 'Bekliyor',
                        'isleniyor' => 'İşleniyor',
                        'tamamlandi' => 'Tamamlandı',
                        'hatali' => 'Hatalı',
                    ]),
            ])
            ->defaultSort('id', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('yonetici');

        if (auth()->check() && auth()->user()->hasRole('Admin')) {
            return $query;
        }

        return $query->where('yonetici_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsExcelGonderimler::route('/'),
        ];
    }
}
