<?php

namespace App\Filament\Resources;

use App\Data\TurkiyeIlceler;
use App\Data\TurkiyeIller;
use App\Enums\KurumTipi;
use App\Filament\Resources\KurumResource\Pages;
use App\Models\Kurum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KurumResource extends Resource
{
    protected static ?string $model = Kurum::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Kurumlar';

    protected static ?string $modelLabel = 'Kurum';

    protected static ?string $pluralModelLabel = 'Kurumlar';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 11;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('ad')
                ->label('Ad')
                ->required()
                ->maxLength(500),

            Select::make('tip')
                ->label('Tip')
                ->options(KurumTipi::secenekler())
                ->searchable()
                ->createOptionForm([
                    TextInput::make('tip')
                        ->label('Yeni Tip')
                        ->required(),
                ])
                ->createOptionUsing(function (array $data) {
                    return $data['tip'];
                })
                ->nullable(),

            TextInput::make('telefon')
                ->label('Telefon')
                ->tel()
                ->maxLength(20),

            TextInput::make('eposta')
                ->label('E-posta')
                ->email()
                ->maxLength(255),

            Textarea::make('adres')
                ->label('Adres')
                ->rows(3)
                ->columnSpanFull(),

            Select::make('il')
                ->label('İl')
                ->options(TurkiyeIller::secenekler())
                ->searchable()
                ->live(),

            Select::make('ilce')
                ->label('İlçe')
                ->options(fn (callable $get) => TurkiyeIlceler::ilceSecenekleri($get('il')))
                ->searchable()
                ->disabled(fn (callable $get) => blank($get('il'))),

            TextInput::make('web_sitesi')
                ->label('Web Sitesi')
                ->url()
                ->maxLength(255),

            Textarea::make('aciklama')
                ->label('Açıklama')
                ->rows(4)
                ->columnSpanFull(),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')
                    ->label('Ad')
                    ->sortable()
                    ->searchable(['ad', 'il']),

                TextColumn::make('tip')
                    ->label('Tip')
                    ->formatStateUsing(fn (?string $state) => $state ? (KurumTipi::tryFrom($state)?->label() ?? $state) : null)
                    ->badge()
                    ->sortable(),

                TextColumn::make('il')
                    ->label('İl')
                    ->sortable(),

                TextColumn::make('telefon')
                    ->label('Telefon')
                    ->sortable(),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tip')
                    ->label('Tip')
                    ->options(KurumTipi::secenekler()),
                SelectFilter::make('il')
                    ->label('İl')
                    ->options(TurkiyeIller::secenekler()),
                TernaryFilter::make('aktif')
                    ->label('Aktif'),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
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
            'index' => Pages\ListKurums::route('/'),
            'create' => Pages\CreateKurum::route('/create'),
            'edit' => Pages\EditKurum::route('/{record}/edit'),
        ];
    }
}