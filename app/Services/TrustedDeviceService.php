<?php

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\Uye;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrustedDeviceService
{
    public function deviceKaydet(Uye $uye, Request $request): string
    {
        $this->eskiDeviceSil($uye);

        $hamToken = Str::random(64);

        TrustedDevice::query()->create([
            'uye_id' => $uye->id,
            'device_token' => hash('sha256', $hamToken),
            'device_adi' => Str::limit($request->userAgent() ?? 'Bilinmeyen Cihaz', 255, ''),
            'ip_adresi' => $request->ip(),
            'son_kullanim' => now(),
            'gecerlilik_tarihi' => now()->addHours(72),
        ]);

        return $hamToken;
    }

    public function deviceDogrula(Uye $uye, string $token): bool
    {
        $device = TrustedDevice::query()
            ->where('uye_id', $uye->id)
            ->where('device_token', hash('sha256', $token))
            ->where('gecerlilik_tarihi', '>', now())
            ->first();

        if (! $device) {
            return false;
        }

        $device->forceFill([
            'son_kullanim' => now(),
            'gecerlilik_tarihi' => now()->addHours(72),
        ])->save();

        return true;
    }

    public function eskiDeviceSil(Uye $uye): void
    {
        TrustedDevice::query()
            ->where('uye_id', $uye->id)
            ->where('son_kullanim', '<', now()->subDays(30))
            ->delete();
    }
}