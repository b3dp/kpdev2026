<?php

namespace App\Services;

use App\Enums\SepetDurumu;
use App\Models\BagisSepet;
use App\Models\BagisSepetSatir;
use App\Models\BagisTuru;
use Illuminate\Http\Request;

class SepetService
{
    public function aktifSepetAl(Request $request): BagisSepet
    {
        return BagisSepet::aktifSepet($request);
    }

    public function sepeteEkle(
        BagisSepet $sepet,
        BagisTuru $tur,
        int $adet,
        string $sahipTipi = 'kendi'
    ): BagisSepetSatir|false {
        $varMi = $sepet->satirlar()
            ->where('bagis_turu_id', $tur->id)
            ->exists();

        if ($varMi) {
            return false;
        }

        $adet = max(1, $adet);
        $birimFiyat = (float) ($tur->fiyat ?? 0);

        $satir = $sepet->satirlar()->create([
            'bagis_turu_id' => $tur->id,
            'adet' => $adet,
            'birim_fiyat' => $birimFiyat,
            'toplam' => $birimFiyat * $adet,
            'sahip_tipi' => $sahipTipi,
            'vekalet_onay' => false,
            'created_at' => now(),
        ]);

        $sepet->toplamHesapla();

        return $satir;
    }

    public function sepettenCikar(BagisSepet $sepet, int $satirId): void
    {
        $sepet->satirlar()->whereKey($satirId)->delete();
        $sepet->toplamHesapla();
    }

    public function sepetiBosalt(BagisSepet $sepet): void
    {
        $sepet->satirlar()->delete();
        $sepet->toplamHesapla();
    }

    public function terkEdilenSepetleriTemizle(): void
    {
        BagisSepet::query()
            ->where('durum', SepetDurumu::Aktif->value)
            ->where('updated_at', '<=', now()->subHours(8))
            ->update(['durum' => SepetDurumu::TerkEdildi->value]);
    }
}
