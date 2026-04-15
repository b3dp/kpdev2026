<?php

namespace App\Filament\Resources;

use App\Enums\YoneticiRolu;
use App\Filament\Resources\RolResource\Pages;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Roller';

    protected static ?string $modelLabel = 'Rol';

    protected static ?string $pluralModelLabel = 'Roller';

    protected static ?string $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return static::izinVarMi('roller.listele');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Rol Adı')
                ->required()
                ->maxLength(255)
                ->disabled(fn (?Role $record) => $record !== null && in_array($record->name, YoneticiRolu::varsayilanlar())),

            CheckboxList::make('permissions')
                ->label('İzinler')
                ->relationship('permissions', 'name', fn ($query) => $query->where('guard_name', 'admin')->orderBy('name'))
                ->columns(3)
                ->searchable()
                ->bulkToggleable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Rol Adı')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (Role $record) => in_array($record->name, YoneticiRolu::varsayilanlar()) ? 'primary' : 'gray'),

                TextColumn::make('permissions_count')
                    ->label('İzin Sayısı')
                    ->counts('permissions')
                    ->sortable(),

                TextColumn::make('users_count')
                    ->label('Yönetici Sayısı')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Role $record) => in_array($record->name, YoneticiRolu::varsayilanlar())),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('guard_name', 'admin')
            ->withCount([
                'permissions',
                'users as users_count' => fn ($query) => $query->where('model_type', \App\Models\Yonetici::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRols::route('/'),
            'create' => Pages\CreateRol::route('/create'),
            'edit'   => Pages\EditRol::route('/{record}/edit'),
        ];
    }
}
