<?php

namespace App\Http\Controllers\Uye;

use App\Enums\OtpTipi;
use App\Http\Controllers\Controller;
use App\Models\OtpKod;
use App\Models\Uye;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class KayitController extends Controller
{
    /**
     * Kayıt formu
     */
    public function form()
    {
        return view('uye.kayit');
    }

    /**
     * Kayıt işlemi
     */
    public function kayit(Request $request)
    {
        // Demo/local ortamda reCAPTCHA kontrolünü es geçiyoruz.
        if (! app()->environment('local')) {
            $recaptchaResponse = $this->dogrulaRecaptcha($request->input('g-recaptcha-response'));
            $esik = (float) config('services.recaptcha.threshold', 0.5);

            if (! $recaptchaResponse || ! ($recaptchaResponse['success'] ?? false) || ((float) ($recaptchaResponse['score'] ?? 0)) < $esik) {
                throw ValidationException::withMessages([
                    'recaptcha' => 'reCAPTCHA doğrulaması başarısız. Lütfen tekrar deneyiniz.',
                ]);
            }
        }

        // Validasyon
        $request->validate([
            'ad_soyad' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-]+$/u'],
            'iletisim' => ['required', 'string', 'min:10', 'max:255'], // telefon veya e-posta
        ]);

        $iletisim = trim($request->input('iletisim'));
        $adSoyad = trim($request->input('ad_soyad'));

        // Telefon veya e-posta kontrol et
        $isTelefon = preg_match('/^[0-9\s\-\+]{10,}$/', $iletisim);
        $isEposta = filter_var($iletisim, FILTER_VALIDATE_EMAIL);

        if (!$isTelefon && !$isEposta) {
            throw ValidationException::withMessages(['iletisim' => 'Geçerli bir telefon numarası veya e-posta giriniz.']);
        }

        // Telefon numarasını standartlaştır
        if ($isTelefon) {
            $iletisim = preg_replace('/[^0-9]/', '', $iletisim);
            
            // Telefon benzersizliğini kontrol et
            if (Uye::where('telefon', $iletisim)->exists()) {
                throw ValidationException::withMessages(['iletisim' => 'Bu telefon numarası zaten kayıtlı.']);
            }
        } else {
            // E-posta benzersizliğini kontrol et
            if (Uye::where('eposta', $iletisim)->exists()) {
                throw ValidationException::withMessages(['iletisim' => 'Bu e-posta zaten kayıtlı.']);
            }
        }

        // Yeni üye oluştur
        $uye = Uye::create([
            'ad_soyad' => $adSoyad,
            'telefon' => $isTelefon ? $iletisim : null,
            'eposta' => !$isTelefon ? $iletisim : null,
            'durum' => 'aktif',
            'aktif' => true,
            'sms_abonelik' => true,
            'eposta_abonelik' => true,
        ]);

        // OTP gönder
        $this->otpGonder($uye, $isTelefon ? OtpTipi::TelefonDogrulama : OtpTipi::EpostaDogrulama);

        return response()->json(['uye_id' => $uye->id, 'step' => 'otp']);
    }

    /**
     * OTP Gönder
     */
    private function otpGonder(Uye $uye, OtpTipi $tip = OtpTipi::TelefonDogrulama)
    {
        $kod = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        OtpKod::create([
            'uye_id' => $uye->id,
            'telefon' => $uye->telefon,
            'eposta' => $uye->eposta,
            'kod' => $kod,
            'tip' => $tip,
            'kullanildi' => false,
            'gecerlilik_tarihi' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        // SMS/e-posta gönder (TODO: SMS/e-posta servisi)
        // $this->sendOtp($uye, $kod, $tip);
    }

    /**
     * reCAPTCHA v3 doğrulaması
     */
    private function dogrulaRecaptcha(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        try {
            $response = Http::post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $token,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            return null;
        }
    }
}
