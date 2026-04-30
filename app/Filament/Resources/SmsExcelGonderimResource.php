<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsExcelGonderimResource\Pages;
use App\Models\SmsExcelGonderim;
use Carbon\Carbon;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
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
                Tables\Columns\Layout\Split::make([
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
                        ->grow(false),

                    TextColumn::make('alici_sayisi')
                        ->label('Alıcı')
                        ->grow(false),

                    TextColumn::make('basarili')
                        ->label('Başarılı')
                        ->color('success')
                        ->grow(false),

                    TextColumn::make('basarisiz')
                        ->label('Başarısız')
                        ->color('danger')
                        ->grow(false),

                    TextColumn::make('created_at')
                        ->label('Kayıt')
                        ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i:s') : '-')
                        ->grow(false),
                ]),

                Panel::make([
                    Tables\Columns\Layout\Grid::make(5)
                        ->schema([
                            TextColumn::make('toplam_satir')
                                ->label('Toplam Satır'),
                            TextColumn::make('gecerli_satir')
                                ->label('Geçerli'),
                            TextColumn::make('mukerrer')
                                ->label('Mükerrer'),
                            TextColumn::make('hatali_format')
                                ->label('Hatalı Format'),
                            TextColumn::make('bos')
                                ->label('Boş'),
                        ]),
                    TextColumn::make('hata_mesaji')
                        ->label('Hata Mesajı')
                        ->color('danger')
                        ->hidden(fn ($record) => empty($record?->hata_mesaji)),
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
            ->actions([
                Tables\Actions\Action::make('hatali_numaralar')
                    ->label('Hatalı Numaralar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->modalContent(fn ($record) => view('filament.sms.hatali-numaralar-modal', compact('record')))
                    ->modalHeading('Hatalı Numaralar')
                    ->modalSubmitAction(false)
                    ->visible(fn ($record) => !empty($record->hatali_numaralar)),

                Tables\Actions\Action::make('gonderilen_numaralar')
                    ->label('Gönderilen Numaralar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->modalContent(fn ($record) => view('filament.sms.gonderilen-numaralar-modal', compact('record')))
                    ->modalHeading('Gönderilen Numaralar')
                    ->modalSubmitAction(false)
                    ->visible(fn ($record) => !empty($record->gonderilen_numaralar)),
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
