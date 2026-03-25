<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EtkinlikResource;
use App\Filament\Resources\HaberResource;
use App\Filament\Resources\KisiResource;
use App\Filament\Resources\KurumResource;
use App\Filament\Resources\KurumsalSayfaResource;
use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\Kisi;
use App\Models\Kurum;
use App\Models\KurumsalSayfa;
use Filament\Pages\Page;

class GlobalArama extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $title = 'Global Arama';

    protected static ?string $slug = 'arama';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.global-arama';

    public string $arama = '';

    public int $toplamSonuc = 0;

    public array $sonuclar = [
        'haberler' => [],
        'etkinlikler' => [],
        'kurumsal_sayfalar' => [],
        'kisiler' => [],
        'kurumlar' => [],
    ];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function updatedArama(): void
    {
        $kelime = trim($this->arama);

        if (mb_strlen($kelime, 'UTF-8') < 2) {
            $this->sonuclar = [
                'haberler' => [],
                'etkinlikler' => [],
                'kurumsal_sayfalar' => [],
                'kisiler' => [],
                'kurumlar' => [],
            ];
            $this->toplamSonuc = 0;
            return;
        }

        $haberler = Haber::search($kelime)->take(5)->get();
        $etkinlikler = Etkinlik::search($kelime)->take(5)->get();
        $kurumsalSayfalar = KurumsalSayfa::search($kelime)->take(5)->get();
        $kisiler = Kisi::search($kelime)->take(5)->get();
        $kurumlar = Kurum::search($kelime)->take(5)->get();

        $this->sonuclar = [
            'haberler' => $haberler->map(fn (Haber $haber) => [
                'id' => $haber->id,
                'baslik' => $haber->baslik,
                'ozet' => $haber->ozet,
                'link' => HaberResource::getUrl('edit', ['record' => $haber]),
            ])->all(),
            'etkinlikler' => $etkinlikler->map(fn (Etkinlik $etkinlik) => [
                'id' => $etkinlik->id,
                'baslik' => $etkinlik->baslik,
                'ozet' => $etkinlik->ozet,
                'link' => EtkinlikResource::getUrl('edit', ['record' => $etkinlik]),
            ])->all(),
            'kurumsal_sayfalar' => $kurumsalSayfalar->map(fn (KurumsalSayfa $sayfa) => [
                'id' => $sayfa->id,
                'baslik' => $sayfa->ad,
                'ozet' => $sayfa->ozet,
                'link' => KurumsalSayfaResource::getUrl('edit', ['record' => $sayfa]),
            ])->all(),
            'kisiler' => $kisiler->map(fn (Kisi $kisi) => [
                'id' => $kisi->id,
                'baslik' => $kisi->full_ad,
                'ozet' => $kisi->meslek,
                'link' => KisiResource::getUrl('edit', ['record' => $kisi]),
            ])->all(),
            'kurumlar' => $kurumlar->map(fn (Kurum $kurum) => [
                'id' => $kurum->id,
                'baslik' => $kurum->ad,
                'ozet' => $kurum->il,
                'link' => KurumResource::getUrl('edit', ['record' => $kurum]),
            ])->all(),
        ];

        $this->toplamSonuc = count($this->sonuclar['haberler'])
            + count($this->sonuclar['etkinlikler'])
            + count($this->sonuclar['kurumsal_sayfalar'])
            + count($this->sonuclar['kisiler'])
            + count($this->sonuclar['kurumlar']);
    }
}
