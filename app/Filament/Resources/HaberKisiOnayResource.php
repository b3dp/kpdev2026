<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HaberKisiOnayResource\Pages;
use App\Models\HaberKisi;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HaberKisiOnayResource extends Resource
{
    protected static ?string $model = HaberKisi::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Haber Kişi Önerileri';

    protected static ?string $modelLabel = 'Haber Kişi Önerisi';

    protected static ?string $pluralModelLabel = 'Haber Kişi Önerileri';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 22;

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
                TextColumn::make('kisi.ad')
                    ->label('Kişi')
                    ->formatStateUsing(fn (HaberKisi $record) => $record->kisi?->full_ad)
                    ->searchable(['kisi.ad', 'kisi.soyad'])
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
                    ->action(fn (HaberKisi $record) => $record->update(['onay_durumu' => 'onaylandi'])),
                Action::make('reddet')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (HaberKisi $record) => $record->onay_durumu !== 'reddedildi')
                    ->action(fn (HaberKisi $record) => $record->update(['onay_durumu' => 'reddedildi'])),
            ]);
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
