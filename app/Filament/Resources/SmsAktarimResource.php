<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsAktarimResource\Pages;
use App\Models\SmsAktarim;
use Carbon\Carbon;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SmsAktarimResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = SmsAktarim::class;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Aktarım Geçmişi';

    protected static ?string $modelLabel = 'Aktarım';

    protected static ?string $pluralModelLabel = 'Aktarımlar';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    public static function canViewAny(): bool
    {
        return static::izinlerdenBiriVarMi(['pazarlama_sms.listele', 'pazarlama_sms.goruntule']);
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

                TextColumn::make('liste.ad')
                    ->label('Hedef Liste')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => $state ?: '-'),

                TextColumn::make('toplam')
                    ->label('Toplam')
                    ->sortable(),

                TextColumn::make('eklenen')
                    ->label('Eklenen')
                    ->sortable(),

                TextColumn::make('mukerrer_db')
                    ->label('DB Mükerrer')
                    ->sortable(),

                TextColumn::make('mukerrer_excel')
                    ->label('Excel Mükerrer')
                    ->sortable(),

                TextColumn::make('hatali_format')
                    ->label('Hatalı Format')
                    ->sortable(),

                TextColumn::make('bos')
                    ->label('Boş')
                    ->sortable(),

                TextColumn::make('hata_mesaji')
                    ->label('Hata')
                    ->limit(80)
                    ->toggleable(),

                TextColumn::make('yonetici.ad_soyad')
                    ->label('Yönetici')
                    ->sortable(),

                TextColumn::make('basladi_at')
                    ->label('Başladı')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i:s') : '-')
                    ->sortable(),

                TextColumn::make('tamamlandi_at')
                    ->label('Tamamlandı')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i:s') : '-')
                    ->sortable(),

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

                SelectFilter::make('liste_id')
                    ->label('Liste')
                    ->relationship('liste', 'ad'),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Action::make('hatali_detay')
                    ->label('Hatalı/Mükerrer')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->modalHeading('Hatalı ve Mükerrer Numaralar')
                    ->modalContent(function (SmsAktarim $record): \Illuminate\Contracts\View\View {
                        $numaralar = $record->hatali_numaralar ?? [];
                        $hatali = array_filter($numaralar, fn ($n) => $n['sebep'] === 'hatali_format');
                        $mukerrerExcel = array_filter($numaralar, fn ($n) => $n['sebep'] === 'mukerrer_excel');
                        $mukerrerDb = array_filter($numaralar, fn ($n) => $n['sebep'] === 'mukerrer_db');

                        return view('filament.modals.sms-aktarim-hatali-numaralar', compact('hatali', 'mukerrerExcel', 'mukerrerDb'));
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat')
                    ->visible(fn (SmsAktarim $record): bool => ! empty($record->hatali_numaralar)),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['yonetici', 'liste']);

        if (! auth()->check() || auth()->user()->hasAnyRole(['Admin', 'Halkla İlişkiler'])) {
            return $query;
        }

        return $query->where('yonetici_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsAktarimlar::route('/'),
        ];
    }
}
