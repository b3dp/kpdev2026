<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EkayitKayitResource;
use App\Models\EkayitDonem;
use App\Models\EkayitSinif;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EkayitBasvuruDurumWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'E-Kayıt Başvuru Durumları';

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

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

    protected function getStats(): array
    {
        return collect($this->getSiniflerWithStats())
            ->map(function (array $item): Stat {
                /** @var EkayitSinif $sinif */
                $sinif = $item['sinif'];

                return Stat::make($sinif->ad, (string) $item['basvuru'])
                    ->description(sprintf(
                        'Bekleyen: %d | Onaylanan: %d | Red: %d | Yedek: %d',
                        $item['bekleyen'],
                        $item['onaylanan'],
                        $item['reddedilen'],
                        $item['yedek'],
                    ))
                    ->color($sinif->renk ?: 'primary')
                    ->chart([
                        $item['bekleyen'],
                        $item['onaylanan'],
                        $item['reddedilen'],
                        $item['yedek'],
                        $item['basvuru'],
                    ])
                    ->url($this->getEkayitListeUrl() . '?tableFilters[sinif_id][values][0]=' . $sinif->id);
            })
            ->all();
    }
}