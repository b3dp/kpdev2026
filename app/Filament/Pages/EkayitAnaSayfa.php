<?php

namespace App\Filament\Pages;

use App\Models\EkayitDonem;
use App\Models\EkayitSinif;
use Filament\Pages\Page;

class EkayitAnaSayfa extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Genel Bakış';
    protected static ?string $title           = 'E-Kayıt Genel Bakış';
    protected static ?string $slug            = 'ekayit';
    protected static ?string $navigationGroup = 'E-Kayıt';
    protected static ?int    $navigationSort  = 10;
    protected static string  $view            = 'filament.pages.ekayit-ana-sayfa';

    public ?int $donemId = null;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör', 'E-Kayıt']);
    }

    public function mount(): void
    {
        $aktif = EkayitDonem::aktifDonem();
        $this->donemId = $aktif?->id
            ?? EkayitDonem::orderByDesc('baslangic')->first()?->id;
    }

    public function updatedDonemId(): void
    {
        // Livewire reactive — blade view yenilenir
    }

    public function getDonemler(): \Illuminate\Database\Eloquent\Collection
    {
        return EkayitDonem::orderByDesc('baslangic')->get();
    }

    public function getSiniflerWithStats(): array
    {
        if (! $this->donemId) {
            return [];
        }

        return EkayitSinif::with(['kayitlar', 'kurum'])
            ->where('donem_id', $this->donemId)
            ->where('aktif', true)
            ->orderBy('ad')
            ->get()
            ->map(function (EkayitSinif $sinif): array {
                $kayitlar = $sinif->kayitlar;
                return [
                    'sinif'      => $sinif,
                    'bekleyen'   => $kayitlar->where('durum', 'beklemede')->count(),
                    'onaylanan'  => $kayitlar->where('durum', 'onaylandi')->count(),
                    'reddedilen' => $kayitlar->where('durum', 'reddedildi')->count(),
                    'yedek'      => $kayitlar->where('durum', 'yedek')->count(),
                    'toplam'     => $kayitlar->count(),
                ];
            })->all();
    }
}
