<?php

namespace App\Filament\Resources\SmsListeResource\RelationManagers;

use App\Models\SmsKisi;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KisilerRelationManager extends RelationManager
{
    protected static string $relationship = 'kisiler';

    protected static ?string $title = 'Kayıtlı Numaralar';

    protected static ?string $label = 'Kişi';

    protected static ?string $pluralLabel = 'Kişiler';

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function shouldSkipAuthorization(): bool
    {
        return true;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('telefon')
            ->defaultSort('sms_kisiler.id', 'desc')
            ->columns([
                TextColumn::make('telefon')
                    ->label('Telefon')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telefon_2')
                    ->label('Telefon 2')
                    ->formatStateUsing(fn (?string $state): string => $state ?: '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ad_soyad')
                    ->label('Ad Soyad')
                    ->formatStateUsing(fn (?string $state): string => $state ?: '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Eklenme')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i') : '-')
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }
}
