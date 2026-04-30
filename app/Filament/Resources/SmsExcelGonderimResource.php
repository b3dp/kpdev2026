<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsExcelGonderimResource\Pages;
use App\Models\SmsExcelGonderim;
use Carbon\Carbon;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('id')
                    ->label('No')
                    ->sortable(),

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

                TextColumn::make('toplam_satir')
                    ->label('Toplam Satır')
                    ->sortable(),

                TextColumn::make('gecerli_satir')
                    ->label('Geçerli')
                    ->sortable(),

                TextColumn::make('mukerrer')
                    ->label('Mükerrer')
                    ->sortable(),

                TextColumn::make('hatali_format')
                    ->label('Hatalı Format')
                    ->sortable(),

                TextColumn::make('hatali_numaralar')
                    ->label('Hatalı No')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state) : '0')
                    ->alignCenter(),

                TextColumn::make('gonderilen_numaralar')
                    ->label('Gönderilen No')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state) : '0')
                    ->alignCenter(),

                TextColumn::make('bos')
                    ->label('Boş')
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

                TextColumn::make('bekleyen')
                    ->label('Bekleyen')
                    ->sortable(),

                TextColumn::make('yonetici.ad_soyad')
                    ->label('Yönetici')
                    ->sortable(),

                TextColumn::make('hata_mesaji')
                    ->label('Hata')
                    ->limit(80)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Kayıt')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i:s') : '-')
                    ->sortable(),
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
            ->actions([
                Tables\Actions\Action::make('hatali_numaralar')
                    ->label('Hatalı Numaralar')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn (SmsExcelGonderim $record): bool => filled($record->hatali_numaralar))
                    ->modalHeading('Hatalı Numaralar')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat')
                    ->modalContent(fn (SmsExcelGonderim $record) => view('filament.sms.hatali-numaralar-modal', [
                        'numaralar' => $record->hatali_numaralar ?? [],
                    ])),
                Tables\Actions\Action::make('gonderilen_numaralar')
                    ->label('Gönderilen Numaralar')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (SmsExcelGonderim $record): bool => filled($record->gonderilen_numaralar))
                    ->modalHeading('Gönderilen Numaralar')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat')
                    ->modalContent(fn (SmsExcelGonderim $record) => view('filament.sms.gonderilen-numaralar-modal', [
                        'numaralar' => $record->gonderilen_numaralar ?? [],
                    ])),
            ])
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
