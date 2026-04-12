<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HaberKurumOnayResource\Pages;
use App\Models\HaberKurum;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HaberKurumOnayResource extends Resource
{
    protected static ?string $model = HaberKurum::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Haber Kurum Önerileri';

    protected static ?string $modelLabel = 'Haber Kurum Önerisi';

    protected static ?string $pluralModelLabel = 'Haber Kurum Önerileri';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 23;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
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
                TextColumn::make('kurum.ad')
                    ->label('Kurum')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('kurum', function (Builder $kurumSorgusu) use ($search): Builder {
                            return $kurumSorgusu
                                ->withTrashed()
                                ->where('ad', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
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
                    ->visible(fn (HaberKurum $record) => $record->onay_durumu !== 'onaylandi')
                    ->action(fn (HaberKurum $record) => $record->update(['onay_durumu' => 'onaylandi'])),
                Action::make('reddet')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (HaberKurum $record) => $record->onay_durumu !== 'reddedildi')
                    ->action(fn (HaberKurum $record) => $record->update(['onay_durumu' => 'reddedildi'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('toplu_onayla')
                        ->label('Secilenleri Onayla')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['onay_durumu' => 'onaylandi']))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('toplu_reddet')
                        ->label('Secilenleri Reddet')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['onay_durumu' => 'reddedildi']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['haber:id,baslik', 'kurum:id,ad'])
            ->whereNotNull('kurum_id')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHaberKurumOnays::route('/'),
        ];
    }
}
