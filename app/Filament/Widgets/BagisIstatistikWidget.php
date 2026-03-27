<?php

namespace App\Filament\Widgets;

use App\Enums\BagisDurumu;
use App\Models\Bagis;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BagisIstatistikWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Muhasebe']);
    }

    protected function getStats(): array
    {
        $bugun = Cache::remember('bagis_widget_bugun', now()->addMinutes(5), fn (): array => $this->bugunVerisi());
        $buAy = Cache::remember('bagis_widget_bu_ay', now()->addMinutes(15), fn (): array => $this->buAyVerisi());
        $buYil = Cache::remember('bagis_widget_bu_yil', now()->addMinutes(30), fn (): array => $this->buYilVerisi());
        $bekleyen = Cache::remember('bagis_widget_bekleyen', now()->addMinutes(2), fn (): array => $this->bekleyenVerisi());

        $bekleyenUrl = route('filament.yonetim.resources.bagis.index', [
            'tableFilters[durum][values][0]' => BagisDurumu::Beklemede->value,
        ]);

        return [
            Stat::make('Bugün', $this->tutarFormatla($bugun['toplam']))
                ->description($bugun['adet'].' bağış')
                ->color('success')
                ->chart($bugun['chart']),
            Stat::make('Bu Ay', $this->tutarFormatla($buAy['toplam']))
                ->description($buAy['adet'].' bağış | geçen aya göre '.$buAy['fark'])
                ->color('primary')
                ->chart($buAy['chart']),
            Stat::make('Bu Yıl', $this->tutarFormatla($buYil['toplam']))
                ->description($buYil['adet'].' bağış')
                ->color('success')
                ->chart($buYil['chart']),
            Stat::make('Bekleyen', (string) $bekleyen['adet'])
                ->description($this->tutarFormatla($bekleyen['toplam']))
                ->color('warning')
                ->chart($bekleyen['chart'])
                ->url($bekleyenUrl),
        ];
    }

    private function bugunVerisi(): array
    {
        $bugun = Carbon::today();

        $sorgu = Bagis::query()
            ->where('durum', BagisDurumu::Odendi->value)
            ->whereDate('odeme_tarihi', $bugun);

        return [
            'toplam' => (float) $sorgu->sum('toplam_tutar'),
            'adet' => (int) $sorgu->count(),
            'chart' => $this->son14GunOdendiToplamChart(),
        ];
    }

    private function buAyVerisi(): array
    {
        $simdi = now();

        $buAySorgu = Bagis::query()
            ->where('durum', BagisDurumu::Odendi->value)
            ->whereYear('odeme_tarihi', $simdi->year)
            ->whereMonth('odeme_tarihi', $simdi->month);

        $gecenAy = $simdi->copy()->subMonth();
        $gecenAyToplam = (float) Bagis::query()
            ->where('durum', BagisDurumu::Odendi->value)
            ->whereYear('odeme_tarihi', $gecenAy->year)
            ->whereMonth('odeme_tarihi', $gecenAy->month)
            ->sum('toplam_tutar');

        $buAyToplam = (float) $buAySorgu->sum('toplam_tutar');
        $fark = $this->yuzdeFarkMetni($buAyToplam, $gecenAyToplam);

        return [
            'toplam' => $buAyToplam,
            'adet' => (int) $buAySorgu->count(),
            'fark' => $fark,
            'chart' => $this->buAyGunlukToplamChart(),
        ];
    }

    private function buYilVerisi(): array
    {
        $simdi = now();

        $sorgu = Bagis::query()
            ->where('durum', BagisDurumu::Odendi->value)
            ->whereYear('odeme_tarihi', $simdi->year);

        return [
            'toplam' => (float) $sorgu->sum('toplam_tutar'),
            'adet' => (int) $sorgu->count(),
            'chart' => $this->buYilAylikToplamChart(),
        ];
    }

    private function bekleyenVerisi(): array
    {
        $sorgu = Bagis::query()->where('durum', BagisDurumu::Beklemede->value);

        return [
            'adet' => (int) $sorgu->count(),
            'toplam' => (float) $sorgu->sum('toplam_tutar'),
            'chart' => $this->son14GunBekleyenAdetChart(),
        ];
    }

    private function son14GunOdendiToplamChart(): array
    {
        $son = Carbon::today();
        $ilk = $son->copy()->subDays(13);

        $ham = Bagis::query()
            ->selectRaw('DATE(odeme_tarihi) as gun, SUM(toplam_tutar) as toplam')
            ->where('durum', BagisDurumu::Odendi->value)
            ->whereDate('odeme_tarihi', '>=', $ilk)
            ->whereDate('odeme_tarihi', '<=', $son)
            ->groupBy('gun')
            ->pluck('toplam', 'gun');

        $liste = [];
        for ($i = 0; $i < 14; $i++) {
            $gun = $ilk->copy()->addDays($i)->toDateString();
            $liste[] = (float) ($ham[$gun] ?? 0);
        }

        return $liste;
    }

    private function buAyGunlukToplamChart(): array
    {
        $simdi = now();
        $ayBasi = $simdi->copy()->startOfMonth();
        $gunSayisi = (int) $simdi->day;

        $ham = Bagis::query()
            ->selectRaw('DATE(odeme_tarihi) as gun, SUM(toplam_tutar) as toplam')
            ->where('durum', BagisDurumu::Odendi->value)
            ->whereYear('odeme_tarihi', $simdi->year)
            ->whereMonth('odeme_tarihi', $simdi->month)
            ->groupBy('gun')
            ->pluck('toplam', 'gun');

        $liste = [];
        for ($i = 0; $i < $gunSayisi; $i++) {
            $gun = $ayBasi->copy()->addDays($i)->toDateString();
            $liste[] = (float) ($ham[$gun] ?? 0);
        }

        return $liste;
    }

    private function buYilAylikToplamChart(): array
    {
        $yil = (int) now()->year;

        $ham = Bagis::query()
            ->selectRaw('MONTH(odeme_tarihi) as ay, SUM(toplam_tutar) as toplam')
            ->where('durum', BagisDurumu::Odendi->value)
            ->whereYear('odeme_tarihi', $yil)
            ->groupBy('ay')
            ->pluck('toplam', 'ay');

        $liste = [];
        for ($ay = 1; $ay <= 12; $ay++) {
            $liste[] = (float) ($ham[$ay] ?? 0);
        }

        return $liste;
    }

    private function son14GunBekleyenAdetChart(): array
    {
        $son = Carbon::today();
        $ilk = $son->copy()->subDays(13);

        $ham = Bagis::query()
            ->selectRaw('DATE(created_at) as gun, COUNT(*) as adet')
            ->where('durum', BagisDurumu::Beklemede->value)
            ->whereDate('created_at', '>=', $ilk)
            ->whereDate('created_at', '<=', $son)
            ->groupBy('gun')
            ->pluck('adet', 'gun');

        $liste = [];
        for ($i = 0; $i < 14; $i++) {
            $gun = $ilk->copy()->addDays($i)->toDateString();
            $liste[] = (float) ($ham[$gun] ?? 0);
        }

        return $liste;
    }

    private function tutarFormatla(float $tutar): string
    {
        if ($tutar >= 1000000) {
            $milyon = number_format($tutar / 1000000, 1, '.', '');

            return str_replace('.', ',', $milyon).'M ₺';
        }

        return number_format($tutar, 2, ',', '.').' ₺';
    }

    private function yuzdeFarkMetni(float $simdiki, float $onceki): string
    {
        if ($onceki <= 0) {
            return $simdiki > 0 ? '+%100' : '%0';
        }

        $oran = (($simdiki - $onceki) / $onceki) * 100;
        $isaret = $oran >= 0 ? '+' : '';

        return $isaret.number_format($oran, 0, ',', '.').'%';
    }
}
