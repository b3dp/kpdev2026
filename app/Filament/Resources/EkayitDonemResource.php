<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EkayitDonemResource\Pages;
use App\Models\EkayitDonem;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class EkayitDonemResource extends Resource
{
    protected static ?string $model = EkayitDonem::class;
    protected static ?string $navigationIcon    = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel   = 'Dönemler';
    protected static ?string $modelLabel        = 'Dönem';
    protected static ?string $pluralModelLabel  = 'Dönemler';
    protected static ?string $navigationGroup   = 'E-Kayıt';
    protected static ?int    $navigationSort    = 50;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('ad')->label('Dönem Adı')->required()->maxLength(255),
            TextInput::make('ogretim_yili')->label('Öğretim Yılı')->required()
                ->maxLength(20)->placeholder('2025-2026'),
            DateTimePicker::make('baslangic')->label('Başlangıç')->required()->seconds(false),
            DateTimePicker::make('bitis')->label('Bitiş')->required()->seconds(false),
            Toggle::make('aktif')->label('Aktif')->helperText('Scheduler otomatik yönetir; manuel override için.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')->label('Ad')->searchable()->sortable(),
                TextColumn::make('ogretim_yili')->label('Öğretim Yılı')->sortable(),
                TextColumn::make('baslangic')->label('Başlangıç')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('bitis')->label('Bitiş')->dateTime('d.m.Y H:i')->sortable(),
                ToggleColumn::make('aktif')->label('Aktif'),
            ])
            ->defaultSort('baslangic', 'desc')
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEkayitDonem::route('/'),
            'create' => Pages\CreateEkayitDonem::route('/create'),
            'edit'   => Pages\EditEkayitDonem::route('/{record}/edit'),
        ];
    }
}
