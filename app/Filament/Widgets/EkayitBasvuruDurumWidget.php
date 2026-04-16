<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EkayitKayitResource;
use App\Models\EkayitDonem;
use App\Models\EkayitSinif;
use Filament\Widgets\Widget;

class EkayitBasvuruDurumWidget extends Widget
{
    protected static string $view = 'filament.widgets.ekayit-basvuru-durum-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Kurs Yöneticisi']);
    }

    public function getDonem(): ?EkayitDonem
    {
        return EkayitDonem::aktifDonem()
            ?? EkayitDonem::query()->orderByDesc('baslangic')->first();
    }

    public function getEkayitListeUrl(): string
    {
        return EkayitKayitResource::getUrl('index');
    }

    public function getSiniflerWithStats(): array
    {
        $donem = $this->getDonem();

        if (! $donem) {
            return [];
        }

        return EkayitSinif::query()
            ->with(['kayitlar', 'kurum'])
            ->where('donem_id', $donem->id)
            ->where('aktif', true)
            ->orderBy('ad')
            ->get()
            ->map(function (EkayitSinif $sinif): array {
                $kayitlar = $sinif->kayitlar;

                return [
                    'sinif' => $sinif,
                    'basvuru' => $kayitlar->count(),
                    'bekleyen' => $kayitlar->where('durum', 'beklemede')->count(),
                    'onaylanan' => $kayitlar->where('durum', 'onaylandi')->count(),
                    'reddedilen' => $kayitlar->where('durum', 'reddedildi')->count(),
                    'yedek' => $kayitlar->where('durum', 'yedek')->count(),
                ];
            })
            ->all();
    }
}