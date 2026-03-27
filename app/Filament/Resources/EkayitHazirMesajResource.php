<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EkayitHazirMesajResource\Pages;
use App\Models\EkayitHazirMesaj;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class EkayitHazirMesajResource extends Resource
{
    protected static ?string $model = EkayitHazirMesaj::class;
    protected static ?string $navigationIcon    = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationLabel   = 'Hazır Mesajlar';
    protected static ?string $modelLabel        = 'Hazır Mesaj';
    protected static ?string $pluralModelLabel  = 'Hazır Mesajlar';
    protected static ?string $navigationGroup   = 'E-Kayıt';
    protected static ?int    $navigationSort    = 60;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('baslik')->label('Başlık')->required()->maxLength(255),
            Select::make('tip')->label('Tip')->required()
                ->options(['onay' => 'Onay', 'red' => 'Red', 'yedek' => 'Yedek', 'genel' => 'Genel']),
            Textarea::make('metin')->label('Metin')->required()->rows(5)
                ->hint('Değişkenler: {AD_SOYAD}, {SINIF}, {KURUM}'),
            Toggle::make('aktif')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('baslik')->label('Başlık')->searchable()->sortable(),
                TextColumn::make('tip')->label('Tip')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'onay'  => 'success',
                        'red'   => 'danger',
                        'yedek' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('metin')->label('Metin')->limit(60),
                ToggleColumn::make('aktif')->label('Aktif'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([\Filament\Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEkayitHazirMesaj::route('/'),
            'create' => Pages\CreateEkayitHazirMesaj::route('/create'),
            'edit'   => Pages\EditEkayitHazirMesaj::route('/{record}/edit'),
        ];
    }
}
