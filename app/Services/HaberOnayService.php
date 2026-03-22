<?php

namespace App\Services;

use App\Enums\HaberDurumu;
use App\Models\Haber;
use App\Models\HaberOnayToken;
use Illuminate\Support\Str;

class HaberOnayService
{
    public function tokenOlustur(Haber $haber, string $tip): string
    {
        $token = Str::random(64);

        HaberOnayToken::create([
            'haber_id' => $haber->id,
            'token' => $token,
            'tip' => $tip,
            'kullanildi' => false,
            'gecerlilik_tarihi' => now()->addHour(),
            'created_at' => now(),
        ]);

        return $token;
    }

    public function tokenDogrula(string $token): ?HaberOnayToken
    {
        return HaberOnayToken::query()
            ->where('token', $token)
            ->where('kullanildi', false)
            ->where('gecerlilik_tarihi', '>', now())
            ->first();
    }

    public function haberiYayinla(Haber $haber): void
    {
        $haber->update([
            'durum' => HaberDurumu::Yayinda,
            'yayin_tarihi' => $haber->yayin_tarihi ?? now(),
            'ai_onay' => true,
        ]);
    }

    public function haberiReddet(Haber $haber, string $neden): void
    {
        $haber->update([
            'durum' => HaberDurumu::Reddedildi,
            'ai_onay' => false,
        ]);

        activity('haber_onay')
            ->performedOn($haber)
            ->withProperties(['neden' => $neden])
            ->log('Haber reddedildi');
    }
}
