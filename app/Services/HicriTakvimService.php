<?php

namespace App\Services;

use Alkoumi\LaravelHijriDate\Hijri;
use App\Enums\BagisAcilisTipi;
use App\Models\BagisTuru;

class HicriTakvimService
{
    public function bugunHicri(): array
    {
        $deger = Hijri::Date('Y-m-d');
        [$yil, $ay, $gun] = array_map('intval', explode('-', $deger));

        return [
            'yil' => $yil,
            'ay' => $ay,
            'gun' => $gun,
        ];
    }

    public function turAcikMi(BagisTuru $tur): bool
    {
        if ($tur->acilis_tipi === BagisAcilisTipi::Manuel) {
            return (bool) $tur->aktif;
        }

        if (! $tur->acilis_hicri_ay || ! $tur->acilis_hicri_gun) {
            return (bool) $tur->aktif;
        }

        $bugun = $this->bugunHicri();
        $bugunDeger = ($bugun['ay'] * 100) + $bugun['gun'];
        $acilisDeger = ((int) $tur->acilis_hicri_ay * 100) + (int) $tur->acilis_hicri_gun;

        if (! $tur->kapanis_hicri_ay || ! $tur->kapanis_hicri_gun) {
            return $bugunDeger >= $acilisDeger;
        }

        $kapanisDeger = ((int) $tur->kapanis_hicri_ay * 100) + (int) $tur->kapanis_hicri_gun;
        if ($bugunDeger < $acilisDeger || $bugunDeger > $kapanisDeger) {
            return false;
        }

        if ($bugunDeger === $kapanisDeger && $tur->kapanis_saat) {
            return now()->format('H:i:s') <= $tur->kapanis_saat;
        }

        return true;
    }

    public function acilacakTurleriAc(): int
    {
        $bugun = $this->bugunHicri();

        return BagisTuru::query()
            ->where('acilis_tipi', BagisAcilisTipi::Otomatik->value)
            ->where('aktif', false)
            ->where('acilis_hicri_ay', $bugun['ay'])
            ->where('acilis_hicri_gun', $bugun['gun'])
            ->update(['aktif' => true]);
    }

    public function kapanacakTurleriKapat(): int
    {
        $bugun = $this->bugunHicri();
        $bugunDeger = ($bugun['ay'] * 100) + $bugun['gun'];

        $kapanacaklar = BagisTuru::query()
            ->where('acilis_tipi', BagisAcilisTipi::Otomatik->value)
            ->where('aktif', true)
            ->whereNotNull('kapanis_hicri_ay')
            ->whereNotNull('kapanis_hicri_gun')
            ->get();

        $adet = 0;
        foreach ($kapanacaklar as $tur) {
            $kapanisDeger = ((int) $tur->kapanis_hicri_ay * 100) + (int) $tur->kapanis_hicri_gun;

            $kapat = $bugunDeger > $kapanisDeger
                || ($bugunDeger === $kapanisDeger
                    && $tur->kapanis_saat
                    && now()->format('H:i:s') >= $tur->kapanis_saat);

            if ($kapat) {
                $tur->update(['aktif' => false]);
                $adet++;
            }
        }

        return $adet;
    }
}
