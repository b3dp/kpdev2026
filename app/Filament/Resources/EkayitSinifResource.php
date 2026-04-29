<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EkayitSinifResource\Pages;
use App\Models\EkayitDonem;
use App\Models\EkayitSinif;
use App\Models\Kurum;
use App\Services\SinifRenkService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
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

class EkayitSinifResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = EkayitSinif::class;
    protected static ?string $navigationIcon    = 'heroicon-o-building-library';
    protected static ?string $navigationLabel   = 'Sınıflar';
    protected static ?string $modelLabel        = 'Sınıf';
    protected static ?string $pluralModelLabel  = 'Sınıflar';
    protected static ?string $navigationGroup   = 'E-Kayıt';
    protected static ?int    $navigationSort    = 40;

    public static function canViewAny(): bool
    {
        return static::izinVarMi('ekayit.sinif_yonet');
    }

    public static function canCreate(): bool
    {
        return static::izinVarMi('ekayit.sinif_yonet');
    }

    public static function canEdit($record): bool
    {
        return static::izinVarMi('ekayit.sinif_yonet');
    }

    public static function canDelete($record): bool
    {
        return static::izinVarMi('ekayit.sinif_yonet');
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
                    ->default(fn () => EkayitDonem::aktifDonem()?->id)
                    ->searchable(),
                Select::make('kurum_id')->label('Kurum')->required()
                    ->options(fn () => Kurum::whereNotNull('kurumsal_sayfa_id')
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
                Placeholder::make('gorsel_kare_mevcut')
                    ->label('Mevcut Görsel 1:1 (Kare)')
                    ->content(function (?EkayitSinif $record): \Illuminate\Support\HtmlString {
                        $url = $record?->gorselKareUrl();

                        if (! filled($url)) {
                            return new \Illuminate\Support\HtmlString('—');
                        }

                        return new \Illuminate\Support\HtmlString('<a href="' . e($url) . '" target="_blank" rel="noopener"><img src="' . e($url) . '" alt="Görsel 1:1" style="width:100%;max-height:200px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0;"></a>');
                    })
                    ->visible(fn (?EkayitSinif $record): bool => filled($record?->gorselKareUrl())),

                FileUpload::make('gorsel_kare_gecici')
                    ->label('Görsel 1:1 (Kare)')
                    ->disk('local')
                    ->directory('tmp/ekayit/sinif')
                    ->image()
                    ->maxFiles(1)
                    ->preserveFilenames(false)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(20480)
                    ->imagePreviewHeight('160')
                    ->helperText('Yükleme sonrası ori/opt olarak DO Spaces\'a taşınır. Önerilen boyut: 1080x1080.')
                    ->dehydrated(false),

                Placeholder::make('gorsel_dikey_mevcut')
                    ->label('Mevcut Görsel 9:16 (Dikey)')
                    ->content(function (?EkayitSinif $record): \Illuminate\Support\HtmlString {
                        $url = $record?->gorselDikeyUrl();

                        if (! filled($url)) {
                            return new \Illuminate\Support\HtmlString('—');
                        }

                        return new \Illuminate\Support\HtmlString('<a href="' . e($url) . '" target="_blank" rel="noopener"><img src="' . e($url) . '" alt="Görsel 9:16" style="width:100%;max-height:200px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0;"></a>');
                    })
                    ->visible(fn (?EkayitSinif $record): bool => filled($record?->gorselDikeyUrl())),

                FileUpload::make('gorsel_dikey_gecici')
                    ->label('Görsel 9:16 (Dikey)')
                    ->disk('local')
                    ->directory('tmp/ekayit/sinif')
                    ->image()
                    ->maxFiles(1)
                    ->preserveFilenames(false)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(20480)
                    ->imagePreviewHeight('160')
                    ->helperText('Yükleme sonrası ori/opt olarak DO Spaces\'a taşınır. Önerilen boyut: 1080x1920.')
                    ->dehydrated(false),

                Placeholder::make('gorsel_yatay_mevcut')
                    ->label('Mevcut Görsel 16:9 (Yatay)')
                    ->content(function (?EkayitSinif $record): \Illuminate\Support\HtmlString {
                        $url = $record?->gorselYatayUrl();

                        if (! filled($url)) {
                            return new \Illuminate\Support\HtmlString('—');
                        }

                        return new \Illuminate\Support\HtmlString('<a href="' . e($url) . '" target="_blank" rel="noopener"><img src="' . e($url) . '" alt="Görsel 16:9" style="width:100%;max-height:200px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0;"></a>');
                    })
                    ->visible(fn (?EkayitSinif $record): bool => filled($record?->gorselYatayUrl())),

                FileUpload::make('gorsel_yatay_gecici')
                    ->label('Görsel 16:9 (Yatay)')
                    ->disk('local')
                    ->directory('tmp/ekayit/sinif')
                    ->image()
                    ->maxFiles(1)
                    ->preserveFilenames(false)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(20480)
                    ->imagePreviewHeight('160')
                    ->helperText('Yükleme sonrası ori/opt olarak DO Spaces\'a taşınır. Önerilen boyut: 1920x1080.')
                    ->dehydrated(false),

                Placeholder::make('gorsel_orijinal_mevcut')
                    ->label('Mevcut Görsel Orijinal')
                    ->content(function (?EkayitSinif $record): \Illuminate\Support\HtmlString {
                        $url = $record?->gorselOrijinalUrl();

                        if (! filled($url)) {
                            return new \Illuminate\Support\HtmlString('—');
                        }

                        return new \Illuminate\Support\HtmlString('<a href="' . e($url) . '" target="_blank" rel="noopener"><img src="' . e($url) . '" alt="Görsel Orijinal" style="width:100%;max-height:200px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0;"></a>');
                    })
                    ->visible(fn (?EkayitSinif $record): bool => filled($record?->gorselOrijinalUrl())),

                FileUpload::make('gorsel_orijinal_gecici')
                    ->label('Görsel Orijinal')
                    ->disk('local')
                    ->directory('tmp/ekayit/sinif')
                    ->image()
                    ->maxFiles(1)
                    ->preserveFilenames(false)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(20480)
                    ->imagePreviewHeight('160')
                    ->helperText('Kaynak görsel ori klasörüne, optimize kopyası opt klasörüne yüklenir.')
                    ->dehydrated(false),
            ])->columns(2),
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
