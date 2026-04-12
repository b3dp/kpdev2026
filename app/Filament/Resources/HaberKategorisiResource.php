<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HaberKategorisiResource\Pages;
use App\Models\HaberKategorisi;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HaberKategorisiResource extends Resource
{
    protected static ?string $model = HaberKategorisi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Haber Kategorileri';

    protected static ?string $modelLabel = 'Haber Kategorisi';

    protected static ?string $pluralModelLabel = 'Haber Kategorileri';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 20;

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
        return self::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return self::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('ad')
                ->label('Ad')
                ->required()
                ->maxLength(150)
                ->columnSpanFull(),

            TextInput::make('seo_baslik')
                ->label('SEO Başlığı')
                ->nullable()
                ->maxLength(100)
                ->helperText(fn (?string $state) => mb_strlen((string) ($state ?? ''), 'UTF-8') . '/100 karakter'),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(180)
                ->unique(table: 'haber_kategorileri', column: 'slug', ignoreRecord: true),

            Textarea::make('meta_description')
                ->label('Meta Description')
                ->nullable()
                ->maxLength(200)
                ->rows(2)
                ->helperText(fn (?string $state) => mb_strlen((string) ($state ?? ''), 'UTF-8') . '/200 karakter')
                ->columnSpanFull(),

            RichEditor::make('aciklama')
                ->label('Kategori Açıklaması')
                ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'h2', 'h3', 'link', 'undo', 'redo'])
                ->nullable()
                ->columnSpanFull(),

            TextInput::make('gorsel')
                ->label('Görsel URL')
                ->nullable()
                ->maxLength(500)
                ->url()
                ->columnSpanFull(),

            TextInput::make('ikon')
                ->label('İkon')
                ->nullable()
                ->maxLength(100)
                ->placeholder('heroicon-o-academic-cap'),

            ColorPicker::make('renk')
                ->label('Renk')
                ->default('#2563eb'),

            TextInput::make('sira')
                ->label('Sıra')
                ->numeric()
                ->required()
                ->default(0),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('ikon')
                    ->label('İkon')
                    ->icon(fn (?string $state): string => match ($state) {
                        'fa-solid fa-book-open' => 'heroicon-o-book-open',
                        'fa-solid fa-calendar-days' => 'heroicon-o-calendar-days',
                        'fa-solid fa-award' => 'heroicon-o-trophy',
                        'fa-solid fa-microphone-lines' => 'heroicon-o-microphone',
                        'fa-solid fa-volleyball' => 'heroicon-o-sparkles',
                        'fa-solid fa-bus' => 'heroicon-o-truck',
                        'fa-solid fa-quran' => 'heroicon-o-book-open',
                        'fa-solid fa-handshake' => 'heroicon-o-hand-raised',
                        'fa-solid fa-user-graduate' => 'heroicon-o-academic-cap',
                        'fa-solid fa-hand-holding-heart' => 'heroicon-o-heart',
                        'fa-solid fa-moon' => 'heroicon-o-moon',
                        'fa-solid fa-crown' => 'heroicon-o-trophy',
                        'fa-solid fa-building' => 'heroicon-o-building-office-2',
                        'fa-solid fa-newspaper' => 'heroicon-o-newspaper',
                        'fa-solid fa-bullhorn' => 'heroicon-o-megaphone',
                        default => 'heroicon-o-squares-2x2',
                    })
                    ->color('gray')
                    ->sortable(false),

                TextColumn::make('ad')
                    ->label('Ad')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->sortable()
                    ->searchable(),

                ColorColumn::make('renk')
                    ->label('Renk')
                    ->sortable(),

                TextColumn::make('sira')
                    ->label('Sıra')
                    ->sortable(),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('aktif')->label('Aktif'),
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
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHaberKategorisis::route('/'),
            'create' => Pages\CreateHaberKategorisi::route('/create'),
            'edit' => Pages\EditHaberKategorisi::route('/{record}/edit'),
        ];
    }
}
