<?php

namespace App\Services;

use App\Enums\RozetTipi;
use App\Enums\UyeDurumu;
use App\Models\Bagis;
use App\Models\Uye;

class UyeEslestirService
{
    public function __construct(private readonly RozetService $rozetService)
    {
    }

    public function bagisKisileriniEslestir(Bagis $bagis): void
    {
        $bagis->load('kisiler');

        foreach ($bagis->kisiler as $kisi) {
            $uye = Uye::query()
                ->when($kisi->telefon, fn ($s) => $s->where('telefon', $kisi->telefon))
                ->when($kisi->eposta, fn ($s) => $s->orWhere('eposta', $kisi->eposta))
                ->first();

            if (! $uye) {
                $uye = Uye::query()->create([
                    'ad_soyad' => $kisi->ad_soyad,
                    'telefon' => $kisi->telefon,
                    'eposta' => $kisi->eposta,
                    'durum' => UyeDurumu::Beklemede->value,
                    'aktif' => false,
                    'telefon_dogrulandi' => false,
                    'eposta_dogrulandi' => false,
                    'sms_abonelik' => true,
                    'eposta_abonelik' => true,
                ]);
            }

            $kisi->update(['uye_id' => $uye->id]);

            if (! $this->rozetService->rozetVarMi($uye, RozetTipi::Bagisci)) {
                $this->rozetService->rozetEkle($uye, RozetTipi::Bagisci, 'bagis', $bagis->id);
            }
        }
    }
}
