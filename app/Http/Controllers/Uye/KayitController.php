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
use Throwable;

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
        try {
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

            $request->validate([
                'ad_soyad' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-]+$/u'],
                'eposta' => ['nullable', 'email', 'max:255', 'required_without:telefon'],
                'telefon' => ['nullable', 'string', 'min:10', 'max:20', 'required_without:eposta'],
            ]);

            $eposta = trim((string) $request->input('eposta', ''));
            $telefon = preg_replace('/[^0-9]/', '', (string) $request->input('telefon', ''));
            $adSoyad = trim((string) $request->input('ad_soyad'));

            if ($telefon !== '' && Uye::where('telefon', $telefon)->exists()) {
                throw ValidationException::withMessages(['telefon' => 'Bu telefon numarası zaten kayıtlı.']);
            }

            if ($eposta !== '' && Uye::where('eposta', $eposta)->exists()) {
                throw ValidationException::withMessages(['eposta' => 'Bu e-posta zaten kayıtlı.']);
            }

            // Yeni üye oluştur (durum: aktif, aktif=true - giriş yapabilecek)
            $uye = Uye::create([
                'ad_soyad' => $adSoyad,
                'telefon' => $telefon !== '' ? $telefon : null,
                'eposta' => $eposta !== '' ? $eposta : null,
                'durum' => 'aktif',
                'aktif' => true,
                'sms_abonelik' => true,
                'eposta_abonelik' => true,
            ]);

            $otpTipi = $eposta !== '' ? OtpTipi::EpostaDogrulama : OtpTipi::TelefonDogrulama;
            $this->otpGonder($uye, $otpTipi);

            Session::put('kayit_uye_id', $uye->id);

            return response()->json(['step' => 'otp']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Uye kayit hatasi', [
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
                'ad_soyad' => $request->input('ad_soyad'),
                'eposta' => $request->input('eposta'),
                'telefon' => $request->input('telefon'),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => app()->environment('local')
                    ? ('Sunucu hatası: ' . $e->getMessage())
                    : 'Beklenmeyen bir sunucu hatası oluştu.',
            ], 500);
        }
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
            'durum' => 'aktif',
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
     * Mantık: Eğer eposta varsa, HER ZAMAN eposta ile gönder.
     * Sadece email yoksa, telefon üzerinden SMS deneme.
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

        // Eposta varsa her zaman email ile gönder (OTP tipi=EpostaDogrulama)
        if ($uye->eposta) {
            app(ZeptomailService::class)->otpGonder(
                $uye->eposta,
                $uye->ad_soyad,
                $kod,
                'kayit'
            );
            Log::info('OTP e-posta ile gönderildi (kayıt)', [
                'eposta' => $uye->eposta,
                'kod' => $kod,
                'uye_id' => $uye->id,
            ]);
        } else {
            // Sadece telefon varsa SMS dene (sistem hazır olmaya kadar log)
            Log::info('OTP SMS gönderilecek (kayıt)', [
                'telefon' => $uye->telefon,
                'kod' => $kod,
                'uye_id' => $uye->id,
            ]);
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
