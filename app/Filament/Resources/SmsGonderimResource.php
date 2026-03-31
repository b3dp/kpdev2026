<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsGonderimResource\Pages;
use App\Models\SmsGonderim;
use App\Services\HermesService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SmsGonderimResource extends Resource
{
    protected static ?string $model = SmsGonderim::class;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Gönderim Geçmişi';

    protected static ?string $modelLabel = 'Gönderim';

    protected static ?string $pluralModelLabel = 'Gönderimler';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Kurs Yöneticisi']);
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
                TextColumn::make('tip')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hizli' => 'Hızlı',
                        'toplu' => 'Toplu',
                        'bildirim_ekayit' => 'E-Kayıt',
                        'bildirim_bagis' => 'Bağış',
                        'bildirim_uyelik' => 'Üyelik',
                        'bildirim_etkinlik' => 'Etkinlik',
                        'bildirim_veli' => 'Veli',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'hizli' => 'info',
                        'toplu' => 'primary',
                        'bildirim_ekayit' => 'success',
                        'bildirim_bagis' => 'warning',
                        'bildirim_uyelik' => 'gray',
                        'bildirim_etkinlik' => 'purple',
                        'bildirim_veli' => 'orange',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('mesaj')
                    ->label('Mesaj')
                    ->limit(50)
                    ->searchable()
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
                        'gonderiliyor' => 'warning',
                        'tamamlandi' => 'success',
                        'basarisiz' => 'danger',
                        'beklemede', 'iptal' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('yonetici.ad_soyad')
                    ->label('Yönetici')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kayıt')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i') : '-')
                    ->sortable(),

                TextColumn::make('planli_tarih')
                    ->label('Planlı Tarih')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i') : 'Anlık')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tip')
                    ->label('Tip')
                    ->options([
                        'hizli' => 'Hızlı',
                        'toplu' => 'Toplu',
                        'bildirim_ekayit' => 'Bildirim - E-Kayıt',
                        'bildirim_bagis' => 'Bildirim - Bağış',
                        'bildirim_uyelik' => 'Bildirim - Üyelik',
                        'bildirim_etkinlik' => 'Bildirim - Etkinlik',
                        'bildirim_veli' => 'Bildirim - Veli/Öğrenci',
                    ]),
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options([
                        'beklemede' => 'Beklemede',
                        'gonderiliyor' => 'Gönderiliyor',
                        'tamamlandi' => 'Tamamlandı',
                        'basarisiz' => 'Başarısız',
                        'iptal' => 'İptal',
                    ]),
                SelectFilter::make('yonetici_id')
                    ->label('Yönetici')
                    ->relationship('yonetici', 'ad_soyad'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('detay')
                    ->label('Detay')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading('Alıcı Detayları')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat')
                    ->modalContent(fn (SmsGonderim $record) => view('filament.sms.gonderim-detay', [
                        'kayit' => $record->load('alicilar'),
                    ])),

                Action::make('tekrar_dene')
                    ->label('Tekrar Dene')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (SmsGonderim $record): bool => $record->durum === 'basarisiz' && filled($record->hermes_transaction_id))
                    ->action(function (SmsGonderim $record): void {
                        $sonuc = app(HermesService::class)->retryFailed((int) $record->hermes_transaction_id);

                        if (($sonuc['basarili'] ?? false) === true) {
                            $record->update([
                                'durum' => 'gonderiliyor',
                                'hermes_transaction_id' => $sonuc['yeni_transaction_id'] ?? $record->hermes_transaction_id,
                            ]);

                            Notification::make()
                                ->title('Başarısız alıcılar için yeniden gönderim başlatıldı.')
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Yeniden gönderim başlatılamadı.')
                            ->danger()
                            ->send();
                    }),

                Action::make('iptal')
                    ->label('İptal')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SmsGonderim $record): bool => filled($record->planli_tarih) && $record->durum === 'beklemede')
                    ->action(function (SmsGonderim $record): void {
                        if (! filled($record->hermes_transaction_id)) {
                            Notification::make()
                                ->title('İptal için Hermes transaction bilgisi bulunamadı.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $iptal = app(HermesService::class)->cancelScheduled((int) $record->hermes_transaction_id);

                        if ($iptal) {
                            $record->update(['durum' => 'iptal']);

                            Notification::make()
                                ->title('Zamanlanmış gönderim iptal edildi.')
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Gönderim iptal edilemedi.')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['yonetici', 'alicilar']);

        if (! auth()->check() || auth()->user()->hasRole('Admin')) {
            return $query;
        }

        return $query->where('yonetici_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsGonderimler::route('/'),
        ];
    }
}
