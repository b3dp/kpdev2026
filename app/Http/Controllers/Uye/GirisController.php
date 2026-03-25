<?php

namespace App\Http\Controllers\Uye;

use App\Enums\OtpTipi;
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

class GirisController extends Controller
{
    /**
     * Giriş formu
     */
    public function form()
    {
        return view('uye.giris');
    }

    /**
     * Giriş işlemi - telefon/e-posta kontrol ve OTP gönder
     */
    public function giris(Request $request)
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
            'iletisim' => ['required', 'string', 'min:10', 'max:255'], // telefon veya e-posta
        ]);

        $iletisim = trim($request->input('iletisim'));

        // Telefon veya e-posta kontrol et
        $isTelefon = preg_match('/^[0-9\s\-\+]{10,}$/', $iletisim);
        $isEposta = filter_var($iletisim, FILTER_VALIDATE_EMAIL);

        if (!$isTelefon && !$isEposta) {
            throw ValidationException::withMessages(['iletisim' => 'Geçerli bir telefon numarası veya e-posta giriniz.']);
        }

        // Telefon numarasını standartlaştır (sadece rakam)
        if ($isTelefon) {
            $iletisim = preg_replace('/[^0-9]/', '', $iletisim);
            $uye = Uye::where('telefon', $iletisim)->first();
            $eslesenUye = $uye;
        } else {
            $eslesenUye = Uye::where('eposta', $iletisim)->first();
            $uye = $eslesenUye;
        }

        // Üye mevcut mu?
        if ($uye) {
            // Mevcut üye - kontrol et
            if (!$uye->aktif) {
                throw ValidationException::withMessages(['iletisim' => 'Bu hesap pasif durumdadır.']);
            }

            // Şifre varsa direkt şifre gir
            if ($uye->sifre) {
                Session::put('uye_id_temp', $uye->id);
                return response()->json(['step' => 'sifre']);
            }

            // Şifre yoksa OTP gönder
            $this->otpGonder($uye, $isTelefon ? 'sms' : 'eposta');
            Session::put('uye_id_temp', $uye->id);
            return response()->json(['step' => 'otp']);
        }

        // Yeni üye - bu akış kayit sayfasında yapılacak
        throw ValidationException::withMessages(['iletisim' => 'Bu bilgilerle kayıtlı hesap bulunamadı.']);
    }

    /**
     * OTP doğrulama
     */
    public function otpDogrula(Request $request)
    {
        $request->validate([
            'kod' => ['required', 'string', 'size:6'],
        ]);

        $uyeId = Session::get('uye_id_temp');
        if (!$uyeId) {
            throw ValidationException::withMessages(['kod' => 'Oturum geçerli değildir.']);
        }

        $uye = Uye::find($uyeId);
        if (!$uye) {
            throw ValidationException::withMessages(['kod' => 'Üye bulunamadı.']);
        }

        // Çok fazla deneme kontrolü
        $rlAnahtari = 'otp_giris_' . $uyeId;
        if (RateLimiter::tooManyAttempts($rlAnahtari, 5)) {
            throw ValidationException::withMessages(['kod' => 'Çok fazla deneme yapıldı. Lütfen bekleyiniz.']);
        }

        // Önce kodu bul (süresi dolmuş olabilir)
        $herhangiOtp = OtpKod::where('uye_id', $uyeId)
            ->where('kod', $request->input('kod'))
            ->where('kullanildi', false)
            ->latest('id')
            ->first();

        if (!$herhangiOtp) {
            RateLimiter::hit($rlAnahtari, 600);
            throw ValidationException::withMessages(['kod' => 'Doğrulama kodu hatalı.']);
        }

        // Süre kontrolü
        if ($herhangiOtp->gecerlilik_tarihi->isPast()) {
            RateLimiter::hit($rlAnahtari, 600);
            throw ValidationException::withMessages(['kod' => 'Doğrulama kodu süresi dolmuş.']);
        }

        // OTP'yi kullanıldı olarak işaretle
        $herhangiOtp->update(['kullanildi' => true]);
        RateLimiter::clear($rlAnahtari);

        // Trusted device tokenı kaydet
        $trustedDeviceService = app(TrustedDeviceService::class);
        $deviceToken = $trustedDeviceService->deviceKaydet($uye, $request);

        // Giriş yap
        Auth::guard('uye')->login($uye);
        Session::forget('uye_id_temp');

        // Cookie'ye device token kaydet (72 saat)
        return response()->json(['redirect' => route('uye.profil.index')])
            ->cookie('device_token', $deviceToken, 72 * 60);
    }

    /**
     * Çıkış
     */
    public function cikis(Request $request)
    {
        Auth::guard('uye')->logout();
        Session::invalidate();
        return redirect('/giris');
    }

    /**
     * OTP Gönder
     */
    private function otpGonder(Uye $uye, string $kanal = 'eposta')
    {
        // Eski OTP'leri geçersiz yap
        OtpKod::where('uye_id', $uye->id)->where('kullanildi', false)->update(['kullanildi' => true]);

        // Yeni OTP oluştur
        $kod = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        OtpKod::create([
            'uye_id' => $uye->id,
            'telefon' => $uye->telefon,
            'eposta' => $uye->eposta,
            'kod' => $kod,
            'tip' => OtpTipi::Giris->value,
            'kullanildi' => false,
            'gecerlilik_tarihi' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        if ($kanal === 'eposta' && $uye->eposta) {
            app(ZeptomailService::class)->otpGonder(
                $uye->eposta,
                $uye->ad_soyad,
                $kod,
                'giris'
            );
        } else {
            // SMS servisi (HermesService) entegre edildiğinde buraya eklenecek
            Log::info('OTP SMS gönderilecek', ['telefon' => $uye->telefon, 'kod' => $kod]);
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
