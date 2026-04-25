<?php

namespace App\Services;

use App\Enums\EtkinlikKatilimDurumu;
use App\Models\Etkinlik;
use App\Models\EtkinlikKatilimi;
use App\Models\Uye;
use Illuminate\Support\Facades\Log;
use Throwable;

class EtkinlikKatilimService
{
    public function katilimDurumuGuncelle(Etkinlik $etkinlik, Uye $uye, string $durum): EtkinlikKatilimi
    {
        try {
            $katilimDurumu = EtkinlikKatilimDurumu::from($durum);

            $katilim = EtkinlikKatilimi::query()->updateOrCreate(
                [
                    'etkinlik_id' => $etkinlik->id,
                    'uye_id' => $uye->id,
                ],
                [
                    'durum' => $katilimDurumu->value,
                ]
            );

            $katilimSayisi = EtkinlikKatilimi::query()
                ->where('etkinlik_id', $etkinlik->id)
                ->where('durum', EtkinlikKatilimDurumu::Katiliyorum->value)
                ->count();

            $etkinlik->update([
                'kayitli_kisi' => $katilimSayisi,
            ]);

            return $katilim;
        } catch (Throwable $exception) {
            Log::error('EtkinlikKatilimService@katilimDurumuGuncelle hatasi', [
                'etkinlik_id' => $etkinlik->id,
                'uye_id' => $uye->id,
                'durum' => $durum,
                'hata' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
