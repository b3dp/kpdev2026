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

            // Validasyon - e-posta ve telefon ayrı alanlar, en az biri zorunlu
            $request->validate([
                'eposta' => ['nullable', 'email', 'max:255', 'required_without:telefon'],
                'telefon' => ['nullable', 'string', 'min:10', 'max:20', 'required_without:eposta'],
            ]);

            $eposta = trim((string) $request->input('eposta', ''));
            $telefon = preg_replace('/[^0-9]/', '', (string) $request->input('telefon', ''));

            $uye = null;
            $kanal = 'eposta';

            if ($eposta !== '') {
                $uye = Uye::where('eposta', $eposta)->first();
                $kanal = 'eposta';
            }

            if (! $uye && $telefon !== '') {
                $uye = Uye::where('telefon', $telefon)->first();
                $kanal = 'sms';
            }

            // Üye mevcut mu?
            if ($uye) {
                if (! $uye->aktif) {
                    throw ValidationException::withMessages(['eposta' => 'Bu hesap pasif durumdadır.']);
                }

                if ($uye->sifre) {
                    Session::put('uye_id_temp', $uye->id);
                    return response()->json(['step' => 'sifre']);
                }

                $this->otpGonder($uye, $kanal);
                Session::put('uye_id_temp', $uye->id);
                return response()->json(['step' => 'otp']);
            }

            throw ValidationException::withMessages(['eposta' => 'Bu bilgilerle kayıtlı hesap bulunamadı.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Uye giris hatasi', [
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
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

        $deviceToken = null;

        try {
            // Trusted device tokenı kaydet
            $trustedDeviceService = app(TrustedDeviceService::class);
            $deviceToken = $trustedDeviceService->deviceKaydet($uye, $request);

            // Giriş yap
            Auth::guard('uye')->login($uye);
        } catch (Throwable $e) {
            Log::warning('Giris OTP sonrasi otomatik giris adimi basarisiz oldu', [
                'uye_id' => $uye->id,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);
        }

        Session::forget('uye_id_temp');

        $response = response()->json([
            'redirect' => route('uye.profil.index'),
            'message' => 'Dogrulama basariyla tamamlandi.',
        ]);

        // Cookie'ye device token kaydet (72 saat)
        if ($deviceToken) {
            $response->cookie('device_token', $deviceToken, 72 * 60);
        }

        return $response;
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
     * Mantık: Eğer eposta varsa, HER ZAMAN eposta ile gönder.
     * Sadece email yoksa, telefon üzerinden SMS deneme.
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

        // Eposta varsa her zaman email ile gönder (kanalından bağımsız)
        if ($uye->eposta) {
            app(ZeptomailService::class)->otpGonder(
                $uye->eposta,
                $uye->ad_soyad,
                $kod,
                'giris'
            );
            Log::info('OTP e-posta ile gönderildi (giriş)', [
                'eposta' => $uye->eposta,
                'kod' => $kod,
                'uye_id' => $uye->id,
            ]);
        } else {
            // Sadece telefon varsa SMS dene (sistem hazır olmaya kadar log)
            Log::info('OTP SMS gönderilecek (giriş)', [
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
