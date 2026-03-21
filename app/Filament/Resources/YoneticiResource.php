<?php

namespace App\Filament\Resources;

use App\Filament\Resources\YoneticiResource\Pages;
use App\Models\Yonetici;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;

class YoneticiResource extends Resource
{
    protected static ?string $model = Yonetici::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Yöneticiler';

    protected static ?string $modelLabel = 'Yönetici';

    protected static ?string $pluralModelLabel = 'Yöneticiler';

    protected static ?string $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('ad_soyad')
                ->label('Ad Soyad')
                ->required()
                ->maxLength(255),

            TextInput::make('eposta')
                ->label('E-posta')
                ->email()
                ->required()
                ->unique(table: 'yoneticiler', column: 'eposta', ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('sifre')
                ->label('Şifre')
                ->password()
                ->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->maxLength(255),

            TextInput::make('telefon')
                ->label('Telefon')
                ->tel()
                ->maxLength(20),

            Select::make('roles')
                ->label('Roller')
                ->multiple()
                ->relationship('roles', 'name', fn ($query) => $query->where('guard_name', 'admin'))
                ->preload(),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad_soyad')
                    ->label('Ad Soyad')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('eposta')
                    ->label('E-posta')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('roles.name')
                    ->label('Roller')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('son_giris')
                    ->label('Son Giriş')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Yonetici $record) => $record->id === auth()->id()),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->hidden(fn (Yonetici $record) => $record->id === auth()->id()),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListYoneticis::route('/'),
            'create' => Pages\CreateYonetici::route('/create'),
            'edit'   => Pages\EditYonetici::route('/{record}/edit'),
        ];
    }
}
