<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EkayitEvrakSablonuResource\Pages;
use App\Models\EkayitEvrakSablonu;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class EkayitEvrakSablonuResource extends Resource
{
    protected static ?string $model = EkayitEvrakSablonu::class;
    protected static ?string $navigationIcon    = 'heroicon-o-document-text';
    protected static ?string $navigationLabel   = 'Evrak Şablonları';
    protected static ?string $modelLabel        = 'Evrak Şablonu';
    protected static ?string $pluralModelLabel  = 'Evrak Şablonları';
    protected static ?string $navigationGroup   = 'E-Kayıt';
    protected static ?int    $navigationSort    = 70;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin']);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin']);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('ad')->label('Evrak Adı')->required()->maxLength(255),
                TextInput::make('dosya_adi')->label('Dosya Adı (slug)')->required()->maxLength(255)
                    ->placeholder('veli-muvafakatnamesi'),
                TextInput::make('sablon_yol')->label('Şablon Yolu (DO Spaces)')
                    ->required()->maxLength(500)
                    ->placeholder('img26/pdf26/ekayit/sablonlar/dosya-adi.pdf'),
                TagsInput::make('degiskenler')->label('Şablon Değişkenleri')
                    ->placeholder('AD_SOYAD, TC_KIMLIK, SINIF...')->nullable(),
                Toggle::make('sadece_onayliya')->label('Sadece Onaylananlar İçin')->default(true),
                TextInput::make('sira')->label('Sıra')->numeric()->default(0),
                Toggle::make('aktif')->label('Aktif')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')->label('Ad')->searchable()->sortable(),
                TextColumn::make('dosya_adi')->label('Dosya Adı'),
                ToggleColumn::make('sadece_onayliya')->label('Sadece Onaylıya'),
                TextColumn::make('sira')->label('Sıra')->sortable(),
                ToggleColumn::make('aktif')->label('Aktif'),
            ])
            ->defaultSort('sira', 'asc')
            ->actions([\Filament\Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEkayitEvrakSablonu::route('/'),
            'create' => Pages\CreateEkayitEvrakSablonu::route('/create'),
            'edit'   => Pages\EditEkayitEvrakSablonu::route('/{record}/edit'),
        ];
    }
}
