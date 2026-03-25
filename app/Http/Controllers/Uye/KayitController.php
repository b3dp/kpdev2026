<?php

namespace App\Http\Controllers\Uye;

use App\Enums\OtpTipi;
use App\Enums\UyeDurumu;
use App\Http\Controllers\Controller;
use App\Models\OtpKod;
use App\Models\Uye;
use App\Services\TrustedDeviceService;
use App\Services\ZeptomailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
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

        // Yeni üye oluştur (durum: beklemede - OTP onayı bekleniyor)
        $uye = Uye::create([
            'ad_soyad' => $adSoyad,
            'telefon' => $isTelefon ? $iletisim : null,
            'eposta' => !$isTelefon ? $iletisim : null,
            'durum' => UyeDurumu::Beklemede->value,
            'aktif' => false,
            'sms_abonelik' => true,
            'eposta_abonelik' => true,
        ]);

        // OTP gönder
        $this->otpGonder($uye, $isTelefon ? OtpTipi::TelefonDogrulama : OtpTipi::EpostaDogrulama);

        // Session'a uye_id kaydet
        Session::put('kayit_uye_id', $uye->id);

        return response()->json(['step' => 'otp']);
    }

    /**
     * Kayıt OTP doğrulama
     */
    public function otpDogrula(Request $request)
    {
        $request->validate([
            'kod' => ['required', 'string', 'size:6'],
        ]);

        $uyeId = Session::get('kayit_uye_id');
        if (!$uyeId) {
            throw ValidationException::withMessages(['kod' => 'Oturum geçerli değildir.']);
        }

        $uye = Uye::find($uyeId);
        if (!$uye) {
            throw ValidationException::withMessages(['kod' => 'Üye bulunamadı.']);
        }

        // Çok fazla deneme kontrolü
        $rlAnahtari = 'otp_kayit_' . $uyeId;
        if (RateLimiter::tooManyAttempts($rlAnahtari, 5)) {
            throw ValidationException::withMessages(['kod' => 'Çok fazla deneme yapıldı. Lütfen bekleyiniz.']);
        }

        // OTP kaydını bul
        $otpKaydi = OtpKod::where('uye_id', $uyeId)
            ->where('kod', $request->input('kod'))
            ->where('kullanildi', false)
            ->latest('id')
            ->first();

        if (!$otpKaydi) {
            RateLimiter::hit($rlAnahtari, 600);
            throw ValidationException::withMessages(['kod' => 'Doğrulama kodu hatalı.']);
        }

        if ($otpKaydi->gecerlilik_tarihi->isPast()) {
            RateLimiter::hit($rlAnahtari, 600);
            throw ValidationException::withMessages(['kod' => 'Doğrulama kodu süresi dolmuş.']);
        }

        // OTP'yi kullanıldı olarak işaretle
        $otpKaydi->update(['kullanildi' => true]);
        RateLimiter::clear($rlAnahtari);

        // Üye aktifleştir
        $dogruladiAlani = $otpKaydi->tip === OtpTipi::EpostaDogrulama
            ? 'eposta_dogrulandi'
            : 'telefon_dogrulandi';

        $uye->update([
            'durum' => UyeDurumu::Aktif->value,
            'aktif' => true,
            $dogruladiAlani => true,
        ]);

        // Trusted device kaydet
        $trustedDeviceService = app(TrustedDeviceService::class);
        $deviceToken = $trustedDeviceService->deviceKaydet($uye, $request);

        // Otomatik giriş yap
        Auth::guard('uye')->login($uye);
        Session::forget('kayit_uye_id');

        return response()->json(['redirect' => route('uye.basari')])
            ->cookie('device_token', $deviceToken, 72 * 60);
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

        if ($tip === OtpTipi::EpostaDogrulama && $uye->eposta) {
            app(ZeptomailService::class)->otpGonder(
                $uye->eposta,
                $uye->ad_soyad,
                $kod,
                'kayit'
            );
        } else {
            // SMS servisi (HermesService) entegre edildiğinde buraya eklenecek
            Log::info('OTP SMS gönderilecek (kayıt)', ['telefon' => $uye->telefon, 'kod' => $kod]);
        }
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
