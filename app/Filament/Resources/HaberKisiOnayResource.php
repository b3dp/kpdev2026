<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HaberKisiOnayResource\Pages;
use App\Models\HaberKisi;
use App\Models\Kisi;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HaberKisiOnayResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = HaberKisi::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Haber Kişi Önerileri';

    protected static ?string $modelLabel = 'Haber Kişi Önerisi';

    protected static ?string $pluralModelLabel = 'Haber Kişi Önerileri';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 22;

    public static function canViewAny(): bool
    {
        return static::izinVarMi('kisiler.onayla');
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

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('haber.baslik')
                    ->label('Haber')
                    ->searchable()
                    ->limit(80)
                    ->sortable(),
                TextColumn::make('kisi.ad')
                    ->label('Kişi')
                    ->formatStateUsing(fn (HaberKisi $record) => $record->kisi?->full_ad)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('kisi', function (Builder $kisiSorgusu) use ($search): Builder {
                            return $kisiSorgusu
                                ->withTrashed()
                                ->where(function (Builder $altSorgu) use ($search): Builder {
                                    return $altSorgu
                                        ->where('ad', 'like', "%{$search}%")
                                        ->orWhere('soyad', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->sortable(),
                TextColumn::make('rol')
                    ->label('Rol')
                    ->toggleable(),
                TextColumn::make('onay_durumu')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'onaylandi' => 'success',
                        'reddedildi' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('onay_durumu')
                    ->label('Durum')
                    ->options([
                        'beklemede' => 'Beklemede',
                        'onaylandi' => 'Onaylandı',
                        'reddedildi' => 'Reddedildi',
                    ])
                    ->default('beklemede'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('onayla')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (HaberKisi $record) => $record->onay_durumu !== 'onaylandi')
                    ->action(fn (HaberKisi $record) => static::kisiyiTopluOnayMantigiylaOnayla(collect([$record]))),
                Action::make('reddet')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (HaberKisi $record) => $record->onay_durumu !== 'reddedildi')
                    ->action(fn (HaberKisi $record) => $record->update(['onay_durumu' => 'reddedildi'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('toplu_onayla')
                        ->label('Secilenleri Onayla')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => static::kisiyiTopluOnayMantigiylaOnayla($records))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('toplu_reddet')
                        ->label('Secilenleri Reddet')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['onay_durumu' => 'reddedildi']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    private static function kisiyiTopluOnayMantigiylaOnayla(Collection $records): void
    {
        $kisiIdleri = $records
            ->pluck('kisi_id')
            ->filter()
            ->unique()
            ->values();

        if ($kisiIdleri->isEmpty()) {
            return;
        }

        Kisi::query()
            ->withTrashed()
            ->whereIn('id', $kisiIdleri)
            ->update(['ai_onaylandi' => true, 'deleted_at' => null]);

        HaberKisi::query()
            ->whereIn('kisi_id', $kisiIdleri)
            ->where('onay_durumu', 'beklemede')
            ->update(['onay_durumu' => 'onaylandi']);

        HaberKisi::query()
            ->whereIn('id', $records->pluck('id')->all())
            ->update(['onay_durumu' => 'onaylandi']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['haber:id,baslik', 'kisi:id,ad,soyad'])
            ->whereNotNull('kisi_id')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHaberKisiOnays::route('/'),
        ];
    }
}
