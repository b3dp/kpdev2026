<?php

namespace App\Filament\Resources\KurumsalSayfaResource\Pages;

use App\Filament\Resources\KurumsalSayfaResource;
use App\Jobs\GorselOptimizeJob;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditKurumsalSayfa extends EditRecord
{
    protected static string $resource = KurumsalSayfaResource::class;

    public function getTitle(): string
    {
        $ad = trim((string) ($this->record?->ad ?? ''));
        $id = (int) ($this->record?->id ?? 0);

        if ($ad === '') {
            return $id > 0 ? "Sayfa #{$id} Düzenle" : 'Sayfa Düzenle';
        }

        return ($id > 0 ? "#{$id} " : '') . 'Sayfa - ' . \Illuminate\Support\Str::limit($ad, 50);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (): void {
                    if ($this->record->altSayfalar()->exists()) {
                        Notification::make()
                            ->title('Bu sayfanın alt sayfaları var. Önce alt sayfaları taşıyın veya silin.')
                            ->danger()
                            ->send();

                        $this->halt();
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        $sayfa = $this->record;

        $anaGorsel = $this->tekDosyaYolu(data_get($this->data, 'ana_gorsel_gecici'));
        if (filled($anaGorsel)) {
            dispatch_sync(new GorselOptimizeJob($sayfa->id, 'kurumsal_sayfa', 'ana_gorsel', $anaGorsel, 1));
        }

        $bannerMasaustu = $this->tekDosyaYolu(data_get($this->data, 'banner_masaustu_gecici'));
        if (filled($bannerMasaustu)) {
            dispatch_sync(new GorselOptimizeJob($sayfa->id, 'kurumsal_sayfa', 'banner_masaustu', $bannerMasaustu, 1));
        }

        $bannerMobil = $this->tekDosyaYolu(data_get($this->data, 'banner_mobil_gecici'));
        if (filled($bannerMobil)) {
            dispatch_sync(new GorselOptimizeJob($sayfa->id, 'kurumsal_sayfa', 'banner_mobil', $bannerMobil, 1));
        }

        $ogGorsel = $this->tekDosyaYolu(data_get($this->data, 'og_gorsel_gecici'));
        if (filled($ogGorsel)) {
            dispatch_sync(new GorselOptimizeJob($sayfa->id, 'kurumsal_sayfa', 'og_gorsel', $ogGorsel, 1));
        }

        $galeriGorseller = $this->cokluDosyaYollari((array) data_get($this->data, 'galeri_gorseller', []));
        $baslangicSirasi = ((int) $sayfa->gorseller()->max('sira')) + 1;
        foreach ($galeriGorseller as $sira => $geciciYol) {
            dispatch_sync(new GorselOptimizeJob($sayfa->id, 'kurumsal_sayfa', 'galeri_gorseli', $geciciYol, $baslangicSirasi + $sira));
        }
    }

    private function tekDosyaYolu(mixed $deger): ?string
    {
        if (is_string($deger) && filled($deger)) {
            return $deger;
        }

        if (is_array($deger)) {
            foreach ($deger as $oge) {
                if (is_string($oge) && filled($oge)) {
                    return $oge;
                }
            }
        }

        return null;
    }

    private function cokluDosyaYollari(array $degerler): array
    {
        $sonuc = [];

        foreach ($degerler as $deger) {
            if (is_string($deger) && filled($deger)) {
                $sonuc[] = $deger;
                continue;
            }

            if (is_array($deger)) {
                foreach ($deger as $oge) {
                    if (is_string($oge) && filled($oge)) {
                        $sonuc[] = $oge;
                    }
                }
            }
        }

        return array_values($sonuc);
    }
}
