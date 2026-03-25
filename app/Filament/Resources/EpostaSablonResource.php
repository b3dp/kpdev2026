<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EpostaSablonResource\Pages;
use App\Models\EpostaSablon;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EpostaSablonResource extends Resource
{
    protected static ?string $model = EpostaSablon::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'E-posta Şablonları';

    protected static ?string $modelLabel = 'E-posta Şablonu';

    protected static ?string $pluralModelLabel = 'E-posta Şablonları';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 50;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Şablon Bilgileri')->schema([
                TextInput::make('kod')
                    ->label('Şablon Kodu')
                    ->disabled()
                    ->helperText('Şablon kodu değiştirilemez.'),

                TextInput::make('ad')
                    ->label('Ad')
                    ->required()
                    ->maxLength(255),

                TextInput::make('konu')
                    ->label('Konu Satırı')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Değişkenler: {AD_SOYAD}, {KOD}, {TUTAR} vb.'),

                Select::make('tip')
                    ->label('Tip')
                    ->disabled()
                    ->options([
                        'otp'     => 'OTP',
                        'bildirim' => 'Bildirim',
                        'makbuz'  => 'Makbuz',
                        'onay'    => 'Onay',
                        'sistem'  => 'Sistem',
                    ]),

                Toggle::make('aktif')
                    ->label('Aktif')
                    ->default(true),

                Placeholder::make('blade_yolu')
                    ->label('Blade Dosyası')
                    ->content(fn (EpostaSablon $record): string => 'resources/views/emails/' . $record->kod . '.blade.php'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kod')
                    ->label('Kod')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('ad')
                    ->label('Ad')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tip')
                    ->label('Tip')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'otp'      => 'info',
                        'bildirim' => 'warning',
                        'makbuz'   => 'success',
                        'onay'     => 'primary',
                        'sistem'   => 'danger',
                        default    => 'gray',
                    }),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Güncelleme')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEpostaSablonlar::route('/'),
            'edit'  => Pages\EditEpostaSablon::route('/{record}/edit'),
        ];
    }
}
