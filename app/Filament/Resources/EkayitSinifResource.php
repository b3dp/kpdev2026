<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EkayitSinifResource\Pages;
use App\Models\EkayitDonem;
use App\Models\EkayitSinif;
use App\Models\Kurum;
use App\Services\SinifRenkService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EkayitSinifResource extends Resource
{
    protected static ?string $model = EkayitSinif::class;
    protected static ?string $navigationIcon    = 'heroicon-o-building-library';
    protected static ?string $navigationLabel   = 'Sınıflar';
    protected static ?string $modelLabel        = 'Sınıf';
    protected static ?string $pluralModelLabel  = 'Sınıflar';
    protected static ?string $navigationGroup   = 'E-Kayıt';
    protected static ?int    $navigationSort    = 40;

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

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Temel Bilgiler')->schema([
                TextInput::make('ad')->label('Sınıf Adı')->required()->maxLength(255),
                TextInput::make('ogretim_yili')->label('Öğretim Yılı')->required()
                    ->maxLength(20)->placeholder('2025-2026'),
                Select::make('donem_id')->label('Dönem')->required()
                    ->options(fn () => EkayitDonem::orderByDesc('baslangic')->pluck('ad', 'id')->all())
                    ->searchable(),
                Select::make('kurum_id')->label('Kurum')->required()
                    ->options(fn () => Kurum::query()
                        ->orderBy('ad')->pluck('ad', 'id')->all())
                    ->searchable(),
                Select::make('renk')->label('Renk')->required()
                    ->options([
                        'blue'   => 'Mavi',   'green'  => 'Yeşil',
                        'orange' => 'Turuncu','purple' => 'Mor',
                        'red'    => 'Kırmızı','amber'  => 'Kehribar',
                        'teal'   => 'Camgöbeği','lime' => 'Limon',
                        'pink'   => 'Pembe',  'yellow' => 'Sarı',
                    ])->default('blue')
                    ->helperText('Yeni sınıfta boş bırakılırsa otomatik atanır.'),
                Toggle::make('aktif')->label('Aktif')->default(true),
            ])->columns(2),

            Section::make('İçerik')->schema([
                RichEditor::make('kurallar')->label('Kayıt Kabul Kuralları')->nullable(),
                RichEditor::make('aciklama')->label('Açıklama')->nullable(),
                Textarea::make('notlar')->label('İç Notlar (sadece panelde)')->rows(3)->nullable(),
            ]),

            Section::make('Görseller')->schema([
                FileUpload::make('tmp_gorsel_kare')
                    ->label('Görsel 1:1 (Kare) - Yeni')
                    ->disk('local')
                    ->directory('tmp/ekayit-gorseller')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(64 * 1024)
                    ->helperText('JPG, PNG, WebP - Max 64MB')
                    ->dehydrated(false),
                
                FileUpload::make('tmp_gorsel_dikey')
                    ->label('Görsel 9:16 (Dikey) - Yeni')
                    ->disk('local')
                    ->directory('tmp/ekayit-gorseller')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(64 * 1024)
                    ->helperText('JPG, PNG, WebP - Max 64MB')
                    ->dehydrated(false),
                
                FileUpload::make('tmp_gorsel_yatay')
                    ->label('Görsel 16:9 (Yatay) - Yeni')
                    ->disk('local')
                    ->directory('tmp/ekayit-gorseller')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(64 * 1024)
                    ->helperText('JPG, PNG, WebP - Max 64MB')
                    ->dehydrated(false),
            ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')->label('Sınıf Adı')
                    ->description(fn (EkayitSinif $record) => $record->renk)
                    ->sortable()->searchable()
                    ->color(fn (EkayitSinif $record) => $record->renk),
                TextColumn::make('ogretim_yili')->label('Öğretim Yılı')->sortable(),
                TextColumn::make('kurum.ad')->label('Kurum')->sortable(),
                TextColumn::make('donem.ad')->label('Dönem')->sortable(),
                ToggleColumn::make('aktif')->label('Aktif'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEkayitSinif::route('/'),
            'create' => Pages\CreateEkayitSinif::route('/create'),
            'edit'   => Pages\EditEkayitSinif::route('/{record}/edit'),
        ];
    }
}
