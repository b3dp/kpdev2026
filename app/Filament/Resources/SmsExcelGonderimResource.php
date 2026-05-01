<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsExcelGonderimResource\Pages;
use App\Models\SmsExcelGonderim;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

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
                    ->label(static::cokSatirliBaslik('No'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('durum')
                    ->label(static::cokSatirliBaslik('Durum'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bekliyor' => 'gray',
                        'isleniyor' => 'warning',
                        'tamamlandi' => 'success',
                        'hatali' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(static::cokSatirliBaslik('Kayit'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i:s') : '-')
                    ->sortable(),

                TextColumn::make('rapor_dosya_yolu')
                    ->label(static::cokSatirliBaslik('Excel', 'Rapor'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->formatStateUsing(fn ($state): string => filled($state) ? 'Hazir' : '-')
                    ->badge()
                    ->color(fn ($state): string => filled($state) ? 'success' : 'gray'),

                TextColumn::make('raporu_indir')
                    ->label(static::cokSatirliBaslik('Raporu', 'Indir'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->state(fn (SmsExcelGonderim $record): string => filled($record->rapor_dosya_yolu) ? 'Indir' : '-')
                    ->color(fn (SmsExcelGonderim $record): string => filled($record->rapor_dosya_yolu) ? 'success' : 'gray')
                    ->url(fn (SmsExcelGonderim $record): ?string => $record->rapor_url)
                    ->openUrlInNewTab(),

                TextColumn::make('basarili')
                    ->label(static::cokSatirliBaslik('Toplam Basarili', 'SMS'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('basarisiz')
                    ->label(static::cokSatirliBaslik('Toplam Basarisiz', 'SMS'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('toplam_satir')
                    ->label(static::cokSatirliBaslik('Excel Toplam', 'Satir'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('gecerli_satir')
                    ->label(static::cokSatirliBaslik('Gecerli', 'Numara'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('mukerrer')
                    ->label(static::cokSatirliBaslik('Mukerrer', 'Numara'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('hatali_format')
                    ->label(static::cokSatirliBaslik('Hatali', 'Format'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('hatali_numaralar')
                    ->label(static::cokSatirliBaslik('Hatali', 'Numara'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state) : '0')
                    ->alignCenter(),

                TextColumn::make('bos')
                    ->label(static::cokSatirliBaslik('Bos', 'Satir'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('alici_sayisi')
                    ->label(static::cokSatirliBaslik('Toplam', 'Alici'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('bekleyen')
                    ->label(static::cokSatirliBaslik('Bekleyen', 'SMS'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('yonetici.ad_soyad')
                    ->label(static::cokSatirliBaslik('Gonderen'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->sortable(),

                TextColumn::make('hata_mesaji')
                    ->label(static::cokSatirliBaslik('Hata'))
                    ->extraHeaderAttributes(['class' => 'whitespace-normal'])
                    ->limit(80)
                    ->toggleable(),
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
                Tables\Actions\Action::make('raporu_indir')
                    ->label('Raporu İndir')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (SmsExcelGonderim $record): bool => filled($record->rapor_dosya_yolu))
                    ->url(fn (SmsExcelGonderim $record): ?string => $record->rapor_url, shouldOpenInNewTab: true),

                Tables\Actions\Action::make('kalici_sil')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Kaydı Kalıcı Sil')
                    ->modalDescription('Bu rapor ve ilişkili dosyalar kalıcı olarak silinecek. Bu işlem geri alınamaz.')
                    ->modalSubmitActionLabel('Evet, Sil')
                    ->action(function (SmsExcelGonderim $record): void {
                        try {
                            if (filled($record->dosya)) {
                                Storage::disk('spaces')->delete($record->dosya);
                            }
                            if (filled($record->rapor_dosya_yolu)) {
                                Storage::disk('spaces')->delete($record->rapor_dosya_yolu);
                            }
                            $record->forceDelete();

                            Notification::make()
                                ->title('Kayıt silindi')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Log::error('SmsExcelGonderim kalici sil hatasi', [
                                'id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Silme hatası')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    private static function cokSatirliBaslik(string $ilkSatir, ?string $ikinciSatir = null): HtmlString
    {
        if ($ikinciSatir === null) {
            return new HtmlString(e($ilkSatir));
        }

        return new HtmlString(e($ilkSatir).'<br>'.e($ikinciSatir));
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
