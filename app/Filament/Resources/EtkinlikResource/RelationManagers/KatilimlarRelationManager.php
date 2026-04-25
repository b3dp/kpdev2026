<?php

namespace App\Filament\Resources\EtkinlikResource\RelationManagers;

use App\Enums\EtkinlikKatilimDurumu;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KatilimlarRelationManager extends RelationManager
{
    protected static string $relationship = 'katilimlar';

    protected static ?string $title = 'Katılım Listesi';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('uye.ad_soyad')
                    ->label('Üye')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('uye.email')
                    ->label('E-posta')
                    ->searchable(),
                TextColumn::make('uye.telefon')
                    ->label('Telefon')
                    ->searchable(),
                TextColumn::make('durum')
                    ->label('Durum')
                    ->formatStateUsing(fn (?EtkinlikKatilimDurumu $state): string => $state?->label() ?? '-')
                    ->badge()
                    ->color(fn (?EtkinlikKatilimDurumu $state): string => $state?->renk() ?? 'gray'),
                TextColumn::make('updated_at')
                    ->label('Güncellenme')
                    ->formatStateUsing(fn ($state) => $state
                        ? \Carbon\Carbon::parse($state)->format('d.m.Y H:i') : '—')
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc');
    }
}
