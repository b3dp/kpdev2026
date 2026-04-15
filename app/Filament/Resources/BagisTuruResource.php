<?php

namespace App\Filament\Resources;

use App\Enums\BagisAcilisTipi;
use App\Enums\BagisFiyatTipi;
use App\Enums\BagisOzelligi;
use App\Filament\Resources\BagisTuruResource\Pages;
use App\Models\BagisTuru;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BagisTuruResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = BagisTuru::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Bağış Türleri';

    protected static ?string $modelLabel = 'Bağış Türü';

    protected static ?string $pluralModelLabel = 'Bağış Türleri';

    protected static ?string $navigationGroup = 'Bağış Yönetimi';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return static::izinVarMi('bagis.turleri.listele');
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Temel Bilgiler')
                ->schema([
                    TextInput::make('ad')->label('Ad')->required()->maxLength(255)->live(onBlur: true),
                    TextInput::make('slug')->label('Slug')->maxLength(255)->unique(ignoreRecord: true),
                    Select::make('ozellik')->label('Özellik')->options(BagisOzelligi::secenekler())->required(),
                    Select::make('fiyat_tipi')->label('Fiyat Tipi')->options(BagisFiyatTipi::secenekler())->required()->live(),
                    TextInput::make('fiyat')->label('Fiyat')->numeric()->visible(fn ($get) => $get('fiyat_tipi') === BagisFiyatTipi::Sabit->value),
                    TextInput::make('minimum_tutar')->label('Minimum Tutar')->numeric()->visible(fn ($get) => $get('fiyat_tipi') === BagisFiyatTipi::Serbest->value),
                    TagsInput::make('oneri_tutarlar')->label('Önerilen Tutarlar')->visible(fn ($get) => $get('fiyat_tipi') === BagisFiyatTipi::Serbest->value),
                    Textarea::make('aciklama')->label('Açıklama')->rows(3)->columnSpanFull(),
                    Textarea::make('hadis_ayet')->label('Hadis / Ayet')->rows(3)->columnSpanFull(),
                    Toggle::make('kurban_modulu')->label('Kurban Modülüne Aktar')->default(false),
                ])->columns(2),
            Section::make('Görseller')
                ->schema([
                    TextInput::make('gorsel_kare')->label('Görsel Kare (1:1)'),
                    TextInput::make('gorsel_dikey')->label('Görsel Dikey (9:16)'),
                    TextInput::make('gorsel_yatay')->label('Görsel Yatay (16:9)'),
                    TextInput::make('gorsel_orijinal')->label('Görsel Orijinal'),
                    TextInput::make('video_yol')->label('Video'),
                ])->columns(2),
            Section::make('Açılış / Kapanış')
                ->schema([
                    Select::make('acilis_tipi')->label('Açılış Tipi')->options(BagisAcilisTipi::secenekler())->required()->live(),
                    Select::make('acilis_hicri_ay')
                        ->label('Açılış Hicri Ay')
                        ->options(self::hicriAylar())
                        ->visible(fn ($get) => $get('acilis_tipi') === BagisAcilisTipi::Otomatik->value),
                    Select::make('acilis_hicri_gun')
                        ->label('Açılış Hicri Gün')
                        ->options(array_combine(range(1, 30), range(1, 30)))
                        ->visible(fn ($get) => $get('acilis_tipi') === BagisAcilisTipi::Otomatik->value),
                    Select::make('kapanis_hicri_ay')
                        ->label('Kapanış Hicri Ay')
                        ->options(self::hicriAylar())
                        ->visible(fn ($get) => $get('acilis_tipi') === BagisAcilisTipi::Otomatik->value),
                    Select::make('kapanis_hicri_gun')
                        ->label('Kapanış Hicri Gün')
                        ->options(array_combine(range(1, 30), range(1, 30)))
                        ->visible(fn ($get) => $get('acilis_tipi') === BagisAcilisTipi::Otomatik->value),
                    TimePicker::make('kapanis_saat')->label('Kapanış Saati')->seconds(false),
                    Toggle::make('aktif')->label('Aktif')->default(false),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')->label('Ad')->searchable()->sortable(),
                TextColumn::make('ozellik')->label('Özellik')->badge()->sortable(),
                TextColumn::make('fiyat_tipi')->label('Fiyat Tipi')->badge()->sortable(),
                TextColumn::make('fiyat')->label('Tutar')->money('TRY')->sortable(),
                TextColumn::make('acilis_tipi')->label('Açılış Tipi')->badge()->sortable(),
                IconColumn::make('aktif')->label('Durum')->boolean()->sortable(),
            ])
            ->defaultSort('id', 'asc')
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBagisTurus::route('/'),
            'create' => Pages\CreateBagisTuru::route('/create'),
            'edit' => Pages\EditBagisTuru::route('/{record}/edit'),
        ];
    }

    private static function hicriAylar(): array
    {
        return [
            1 => 'Muharrem',
            2 => 'Safer',
            3 => 'Rebiülevvel',
            4 => 'Rebiülahir',
            5 => 'Cemaziyelevvel',
            6 => 'Cemaziyelahir',
            7 => 'Recep',
            8 => 'Şaban',
            9 => 'Ramazan',
            10 => 'Şevval',
            11 => 'Zilkade',
            12 => 'Zilhicce',
        ];
    }
}
