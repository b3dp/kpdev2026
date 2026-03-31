<?php

namespace App\Services;

use App\Enums\OtpTipi;
use App\Models\OtpKod;
use App\Models\Uye;
use App\Services\HermesService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    public function kodUret(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function smsDonder(string $telefon, string $tip): bool
    {
        $otpTipi = OtpTipi::from($tip);
        $anahtar = $this->oranSinirAnahtari('sms', $telefon, $otpTipi->value);

        if (RateLimiter::tooManyAttempts($anahtar, 3)) {
            Log::warning('OTP SMS rate limit aşıldı.', [
                'telefon' => $telefon,
                'tip' => $otpTipi->value,
            ]);

            return false;
        }

        $otp = $this->otpOlustur(telefon: $telefon, eposta: null, tip: $otpTipi);

        RateLimiter::hit($anahtar, 600);

        // SMS gönder
        try {
            $mesaj = 'Kestanepazarı doğrulama kodunuz: ' . $otp->kod . '. 10 dakika geçerlidir.';
            app(HermesService::class)->sendSMS([$telefon], $mesaj);

            Log::info('OTP SMS gönderildi.', [
                'telefon' => $telefon,
                'tip' => $otpTipi->value,
                'otp_id' => $otp->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('OTP SMS gönderilemedi.', [
                'telefon' => $telefon,
                'tip' => $otpTipi->value,
                'otp_id' => $otp->id,
                'hata' => $e->getMessage(),
            ]);
            // SMS başarısız olsa da OTP kodu oluşturuldu, false dönme
            // Kullanıcı e-posta ile devam edebilir
        }

        return true;
    }

    public function epostaDonder(string $eposta, string $tip): bool
    {
        $otpTipi = OtpTipi::from($tip);
        $anahtar = $this->oranSinirAnahtari('eposta', $eposta, $otpTipi->value);

        if (RateLimiter::tooManyAttempts($anahtar, 3)) {
            return false;
        }

        $otp = $this->otpOlustur(telefon: Uye::query()->where('eposta', $eposta)->value('telefon') ?? $eposta, eposta: $eposta, tip: $otpTipi);

        RateLimiter::hit($anahtar, 600);

        Log::info('OTP e-posta kodu oluşturuldu.', [
            'eposta' => $eposta,
            'tip' => $otpTipi->value,
            'otp_id' => $otp->id,
        ]);

        return true;
    }

    public function dogrula(string $telefon, string $kod, string $tip): bool
    {
        $otpTipi = OtpTipi::from($tip);

        $otp = OtpKod::query()
            ->where('telefon', $telefon)
            ->where('tip', $otpTipi->value)
            ->where('kod', $kod)
            ->where('kullanildi', false)
            ->latest('id')
            ->first();

        if (! $otp || $this->sureDolduMu($otp)) {
            return false;
        }

        $otp->forceFill([
            'kullanildi' => true,
        ])->save();

        if ($otpTipi === OtpTipi::TelefonDogrulama && $otp->uye) {
            $otp->uye->forceFill(['telefon_dogrulandi' => true])->save();
        }

        return true;
    }

    public function sureDolduMu(OtpKod $otp): bool
    {
        return $otp->gecerlilik_tarihi->isPast();
    }

    protected function otpOlustur(string $telefon, ?string $eposta, OtpTipi $tip): OtpKod
    {
        OtpKod::query()
            ->where('tip', $tip->value)
            ->where('kullanildi', false)
            ->where(function ($query) use ($telefon, $eposta): void {
                $query->where('telefon', $telefon);

                if ($eposta !== null) {
                    $query->orWhere('eposta', $eposta);
                }
            })
            ->update(['kullanildi' => true]);

        $uyeId = Uye::query()
            ->where('telefon', $telefon)
            ->orWhere('eposta', $eposta)
            ->value('id');

        return OtpKod::query()->create([
            'uye_id' => $uyeId,
            'telefon' => $telefon,
            'eposta' => $eposta,
            'kod' => $this->kodUret(),
            'tip' => $tip->value,
            'kullanildi' => false,
            'gecerlilik_tarihi' => now()->addMinutes(10),
            'created_at' => now(),
        ]);
    }

    protected function oranSinirAnahtari(string $kanal, string $hedef, string $tip): string
    {
        $ipAdresi = request()?->ip() ?? 'cli';

        return implode(':', ['otp', $kanal, $tip, $ipAdresi, $hedef]);
    }
}