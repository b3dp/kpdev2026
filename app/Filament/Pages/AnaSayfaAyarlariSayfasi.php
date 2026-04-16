<?php

namespace App\Filament\Pages;

use App\Settings\AnaSayfaAyarlari;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AnaSayfaAyarlariSayfasi extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Ana Sayfa Ayarları';

    protected static ?string $title = 'Ana Sayfa Ayarları';

    protected static ?string $slug = 'ana-sayfa-ayarlari';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.ana-sayfa-ayarlari-sayfasi';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
    }

    public function mount(AnaSayfaAyarlari $ayarlar): void
    {
        $this->form->fill([
            'ust_bant_metni' => $ayarlar->ust_bant_metni,
            'baslik_ust' => $ayarlar->baslik_ust,
            'baslik_vurgulu' => $ayarlar->baslik_vurgulu,
            'baslik_alt' => $ayarlar->baslik_alt,
            'alt_metin' => $ayarlar->alt_metin,
            'birinci_buton_metin' => $ayarlar->birinci_buton_metin,
            'birinci_buton_url' => $ayarlar->birinci_buton_url,
            'ikinci_buton_metin' => $ayarlar->ikinci_buton_metin,
            'ikinci_buton_url' => $ayarlar->ikinci_buton_url,
            'istatistik_1_sayi' => $ayarlar->istatistik_1_sayi,
            'istatistik_1_etiket' => $ayarlar->istatistik_1_etiket,
            'istatistik_2_sayi' => $ayarlar->istatistik_2_sayi,
            'istatistik_2_etiket' => $ayarlar->istatistik_2_etiket,
            'istatistik_3_sayi' => $ayarlar->istatistik_3_sayi,
            'istatistik_3_etiket' => $ayarlar->istatistik_3_etiket,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Hero Alanı')
                    ->schema([
                        TextInput::make('ust_bant_metni')
                            ->label('Üst Bant Metni')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('baslik_ust')
                            ->label('Başlık Üst Satır')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('baslik_vurgulu')
                            ->label('Başlık Vurgulu Satır')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('baslik_alt')
                            ->label('Başlık Alt Satır')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('alt_metin')
                            ->label('Alt Metin')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Butonlar')
                    ->schema([
                        TextInput::make('birinci_buton_metin')
                            ->label('1. Buton Metni')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('birinci_buton_url')
                            ->label('1. Buton URL')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('ikinci_buton_metin')
                            ->label('2. Buton Metni')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('ikinci_buton_url')
                            ->label('2. Buton URL')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('İstatistikler')
                    ->schema([
                        TextInput::make('istatistik_1_sayi')
                            ->label('1. İstatistik Sayı')
                            ->required()
                            ->maxLength(30),
                        TextInput::make('istatistik_1_etiket')
                            ->label('1. İstatistik Etiket')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('istatistik_2_sayi')
                            ->label('2. İstatistik Sayı')
                            ->required()
                            ->maxLength(30),
                        TextInput::make('istatistik_2_etiket')
                            ->label('2. İstatistik Etiket')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('istatistik_3_sayi')
                            ->label('3. İstatistik Sayı')
                            ->required()
                            ->maxLength(30),
                        TextInput::make('istatistik_3_etiket')
                            ->label('3. İstatistik Etiket')
                            ->required()
                            ->maxLength(80),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function kaydet(AnaSayfaAyarlari $ayarlar): void
    {
        $veri = $this->form->getState();

        foreach ($veri as $alan => $deger) {
            $ayarlar->$alan = $deger;
        }

        $ayarlar->save();

        Notification::make()
            ->title('Ana sayfa ayarları kaydedildi.')
            ->success()
            ->send();
    }
}