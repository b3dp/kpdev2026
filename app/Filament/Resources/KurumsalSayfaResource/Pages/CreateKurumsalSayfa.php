<?php

namespace App\Filament\Resources\KurumsalSayfaResource\Pages;

use App\Filament\Resources\KurumsalSayfaResource;
use App\Jobs\GorselOptimizeJob;
use Filament\Resources\Pages\CreateRecord;

class CreateKurumsalSayfa extends CreateRecord
{
    protected static string $resource = KurumsalSayfaResource::class;

    protected function afterCreate(): void
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

        $galeriGorseller = $this->cokluDosyaYollari((array) data_get($this->data, 'galeri_gorseller', []));
        foreach ($galeriGorseller as $sira => $geciciYol) {
            dispatch_sync(new GorselOptimizeJob($sayfa->id, 'kurumsal_sayfa', 'galeri_gorseli', $geciciYol, $sira + 1));
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
