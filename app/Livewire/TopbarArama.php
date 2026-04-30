<?php

namespace App\Livewire;

use App\Filament\Resources\EkayitKayitResource;
use App\Filament\Resources\EtkinlikResource;
use App\Filament\Resources\HaberResource;
use App\Filament\Resources\KisiResource;
use App\Filament\Resources\KurumResource;
use App\Filament\Resources\KurumsalSayfaResource;
use App\Filament\Resources\MezunProfilResource;
use App\Filament\Resources\SmsKisiResource;
use App\Filament\Resources\UyeResource;
use App\Models\EkayitKayit;
use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\Kisi;
use App\Models\Kurum;
use App\Models\KurumsalSayfa;
use App\Models\MezunProfil;
use App\Models\SmsKisi;
use App\Models\Uye;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class TopbarArama extends Component
{
    public string $arama = '';

    public int $toplamSonuc = 0;

    public string $aktifFiltre = 'tum';

    public array $sonuclar = [
        'haberler' => [],
        'etkinlikler' => [],
        'kurumsal_sayfalar' => [],
        'kisiler' => [],
        'kurumlar' => [],
        'rehber' => [],
        'uyeler' => [],
        'mezunlar' => [],
        'kayitlar' => [],
    ];

    public function setFiltre(string $filtre): void
    {
        $gecerliFiltreler = [
            'tum',
            'haberler',
            'etkinlikler',
            'kurumsal_sayfalar',
            'kisiler',
            'kurumlar',
            'rehber',
            'uyeler',
            'mezunlar',
            'kayitlar',
        ];

        if (in_array($filtre, $gecerliFiltreler, true)) {
            $this->aktifFiltre = $filtre;
        }
    }

    public function updatedArama(): void
    {
        $kelime = trim($this->arama);

        if (mb_strlen($kelime, 'UTF-8') < 2) {
            $this->sonuclar = $this->bosSonuclar();
            $this->toplamSonuc = 0;

            return;
        }

        $sonuclar = $this->bosSonuclar();

        if ($this->kaynakGorunurMu(HaberResource::class)) {
            $haberler = Haber::search($kelime)->take(5)->get();
            $sonuclar['haberler'] = $haberler->map(fn (Haber $haber) => [
                'id' => $haber->id,
                'baslik' => $haber->baslik,
                'ozet' => $haber->ozet,
                'link' => HaberResource::getUrl('edit', ['record' => $haber]),
            ])->all();
        }

        if ($this->kaynakGorunurMu(EtkinlikResource::class)) {
            $etkinlikler = Etkinlik::search($kelime)->take(5)->get();
            $sonuclar['etkinlikler'] = $etkinlikler->map(fn (Etkinlik $etkinlik) => [
                'id' => $etkinlik->id,
                'baslik' => $etkinlik->baslik,
                'ozet' => $etkinlik->ozet,
                'link' => EtkinlikResource::getUrl('edit', ['record' => $etkinlik]),
            ])->all();
        }

        if ($this->kaynakGorunurMu(KurumsalSayfaResource::class)) {
            $kurumsalSayfalar = KurumsalSayfa::search($kelime)->take(5)->get();
            $sonuclar['kurumsal_sayfalar'] = $kurumsalSayfalar->map(fn (KurumsalSayfa $sayfa) => [
                'id' => $sayfa->id,
                'baslik' => $sayfa->ad,
                'ozet' => $sayfa->ozet,
                'link' => KurumsalSayfaResource::getUrl('edit', ['record' => $sayfa]),
            ])->all();
        }

        if ($this->kaynakGorunurMu(KisiResource::class)) {
            $kisiler = Kisi::search($kelime)->take(5)->get();
            $sonuclar['kisiler'] = $kisiler->map(fn (Kisi $kisi) => [
                'id' => $kisi->id,
                'baslik' => $kisi->full_ad,
                'ozet' => $kisi->meslek,
                'link' => KisiResource::getUrl('edit', ['record' => $kisi]),
            ])->all();
        }

        if ($this->kaynakGorunurMu(KurumResource::class)) {
            $kurumlar = Kurum::search($kelime)->take(5)->get();
            $sonuclar['kurumlar'] = $kurumlar->map(fn (Kurum $kurum) => [
                'id' => $kurum->id,
                'baslik' => $kurum->ad,
                'ozet' => $kurum->il,
                'link' => KurumResource::getUrl('edit', ['record' => $kurum]),
            ])->all();
        }

        if ($this->kaynakGorunurMu(SmsKisiResource::class)) {
            $rehberSorgu = SmsKisi::query()
                ->where(function (Builder $query) use ($kelime): void {
                    $query->where('ad_soyad', 'like', "%{$kelime}%")
                        ->orWhere('telefon', 'like', "%{$kelime}%")
                        ->orWhere('telefon_2', 'like', "%{$kelime}%");
                });

            // Rehber resource davranışıyla uyumlu: Admin dışı kullanıcı sadece kendi kayıtlarını görsün.
            if (! auth()->user()?->hasRole('Admin')) {
                $rehberSorgu->where('created_by', auth()->id());
            }

            $rehberKayitlari = $rehberSorgu
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            $sonuclar['rehber'] = $rehberKayitlari->map(fn (SmsKisi $kisi) => [
                'id' => $kisi->id,
                'baslik' => $kisi->ad_soyad ?: $kisi->telefon,
                'ozet' => collect([$kisi->telefon, $kisi->telefon_2])->filter()->implode(' / '),
                'link' => SmsKisiResource::getUrl('edit', ['record' => $kisi]),
            ])->all();
        }

        if ($this->kaynakGorunurMu(UyeResource::class)) {
            $uyeler = Uye::query()
                ->where(function (Builder $query) use ($kelime): void {
                    $query->where('ad_soyad', 'like', "%{$kelime}%")
                        ->orWhere('telefon', 'like', "%{$kelime}%")
                        ->orWhere('eposta', 'like', "%{$kelime}%");
                })
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            $sonuclar['uyeler'] = $uyeler->map(fn (Uye $uye) => [
                'id' => $uye->id,
                'baslik' => $uye->ad_soyad ?: $uye->telefon,
                'ozet' => collect([$uye->telefon, $uye->eposta])->filter()->implode(' / '),
                'link' => UyeResource::getUrl('edit', ['record' => $uye]),
            ])->all();
        }

        if ($this->kaynakGorunurMu(MezunProfilResource::class)) {
            $mezunlar = MezunProfil::query()
                ->with(['uye:id,ad_soyad,telefon', 'kurum:id,ad'])
                ->where(function (Builder $query) use ($kelime): void {
                    $query->where('meslek', 'like', "%{$kelime}%")
                        ->orWhere('kurum_manuel', 'like', "%{$kelime}%")
                        ->orWhereHas('uye', function (Builder $uyeQuery) use ($kelime): void {
                            $uyeQuery->where('ad_soyad', 'like', "%{$kelime}%")
                                ->orWhere('telefon', 'like', "%{$kelime}%");
                        });
                })
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            $sonuclar['mezunlar'] = $mezunlar->map(fn (MezunProfil $mezun) => [
                'id' => $mezun->id,
                'baslik' => $mezun->uye?->ad_soyad ?: ('Mezun #' . $mezun->id),
                'ozet' => collect([$mezun->meslek, $mezun->kurum?->ad, $mezun->kurum_manuel])->filter()->implode(' / '),
                'link' => MezunProfilResource::getUrl('edit', ['record' => $mezun]),
            ])->all();
        }

        if ($this->kaynakGorunurMu(EkayitKayitResource::class)) {
            $kayitlar = EkayitKayit::query()
                ->with(['ogrenciBilgisi:id,kayit_id,ad_soyad', 'veliBilgisi:id,kayit_id,telefon_1', 'sinif:id,ad'])
                ->where(function (Builder $query) use ($kelime): void {
                    $query->whereHas('ogrenciBilgisi', function (Builder $ogrenciQuery) use ($kelime): void {
                        $ogrenciQuery->where('ad_soyad', 'like', "%{$kelime}%")
                            ->orWhere('tc_kimlik', 'like', "%{$kelime}%");
                    })->orWhereHas('veliBilgisi', function (Builder $veliQuery) use ($kelime): void {
                        $veliQuery->where('ad_soyad', 'like', "%{$kelime}%")
                            ->orWhere('telefon_1', 'like', "%{$kelime}%");
                    });
                })
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            $sonuclar['kayitlar'] = $kayitlar->map(fn (EkayitKayit $kayit) => [
                'id' => $kayit->id,
                'baslik' => $kayit->ogrenciBilgisi?->ad_soyad ?: ('Kayıt #' . $kayit->id),
                'ozet' => collect([$kayit->sinif?->ad, $kayit->veliBilgisi?->telefon_1])->filter()->implode(' / '),
                'link' => EkayitKayitResource::getUrl('view', ['record' => $kayit]),
            ])->all();
        }

        $this->sonuclar = $sonuclar;

        $this->toplamSonuc = collect($this->sonuclar)->sum(fn (array $grup): int => count($grup));
    }

    private function bosSonuclar(): array
    {
        return [
            'haberler' => [],
            'etkinlikler' => [],
            'kurumsal_sayfalar' => [],
            'kisiler' => [],
            'kurumlar' => [],
            'rehber' => [],
            'uyeler' => [],
            'mezunlar' => [],
            'kayitlar' => [],
        ];
    }

    private function kaynakGorunurMu(string $resourceSinifi): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (! class_exists($resourceSinifi) || ! method_exists($resourceSinifi, 'canViewAny')) {
            return true;
        }

        return (bool) $resourceSinifi::canViewAny();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.topbar-arama');
    }
}
