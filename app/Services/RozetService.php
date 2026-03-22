<?php

namespace App\Services;

use App\Enums\RozetTipi;
use App\Models\Uye;
use App\Models\UyeRozet;

class RozetService
{
    public function rozetEkle(Uye $uye, RozetTipi $tip, ?string $kaynakTip, ?int $kaynakId): void
    {
        UyeRozet::query()->firstOrCreate(
            [
                'uye_id' => $uye->id,
                'tip' => $tip->value,
            ],
            [
                'kazanilma_tarihi' => now(),
                'kaynak_tip' => $kaynakTip,
                'kaynak_id' => $kaynakId,
            ],
        );
    }

    public function rozetVarMi(Uye $uye, RozetTipi $tip): bool
    {
        return UyeRozet::query()
            ->where('uye_id', $uye->id)
            ->where('tip', $tip->value)
            ->exists();
    }

    public function rozetKaldir(Uye $uye, RozetTipi $tip): void
    {
        UyeRozet::query()
            ->where('uye_id', $uye->id)
            ->where('tip', $tip->value)
            ->delete();
    }
}