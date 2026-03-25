<?php

namespace App\Http\Controllers\Uye;

use App\Http\Controllers\Controller;
use App\Models\OtpKod;
use App\Models\TrustedDevice;
use App\Models\Uye;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
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
        // reCAPTCHA doğrulaması
        $recaptchaResponse = $this->dogrulaRecaptcha($request->input('g-recaptcha-response'));
        $esik = (float) config('services.recaptcha.threshold', 0.5);
        if (! $recaptchaResponse || ((float) ($recaptchaResponse['score'] ?? 0)) < $esik) {
            return back()->withErrors(['recaptcha' => 'Bot aktivitesi tespit edildi.']);
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

        // Yeni üye - e-posta adresi/telefonu kaydet, OTP gönder, sonra kayıt sayfasına yönel
        // Bu akış kayit.php'de yapılacak, burada sadece mevcut üyeler girer
        throw ValidationException::withMessages(['iletisim' => 'Hesap bulunamadı. Lütfen kayıt olunuz.']);
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

        // OTP kontrolü
        $otpKaydi = OtpKod::where('uye_id', $uyeId)
            ->where('kod', $request->input('kod'))
            ->where('kullanildi', false)
            ->where('gecerlilik_tarihi', '>', now())
            ->first();

        if (!$otpKaydi) {
            throw ValidationException::withMessages(['kod' => 'Geçersiz veya süresi dolmuş kod.']);
        }

        // OTP'yi kullanıldı olarak işaretle
        $otpKaydi->update(['kullanildi' => true]);

        // Doğrulama bitişi
        if ($otpKaydi->tip->value === 'sms') {
            $uye->update(['telefon_dogrulandi' => true]);
        } else {
            $uye->update(['eposta_dogrulandi' => true]);
        }

        // Trusted device token oluştur
        $deviceToken = Str::random(64);
        TrustedDevice::create([
            'uye_id' => $uyeId,
            'device_token' => hash('sha256', $deviceToken),
            'device_adi' => substr($request->userAgent(), 0, 255),
            'ip_adresi' => $request->ip(),
            'son_kullanim' => now(),
            'gecerlilik_tarihi' => now()->addDays(3),
        ]);

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
     * OTP Gonder
     */
    private function otpGonder(Uye $uye, string $kanal = 'sms')
    {
        // Eski OTP'leri geçersiz yap
        OtpKod::where('uye_id', $uye->id)->update(['kullanildi' => true]);

        // Yeni OTP oluştur
        $kod = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpKaydi = OtpKod::create([
            'uye_id' => $uye->id,
            'telefon' => $uye->telefon,
            'eposta' => $uye->eposta,
            'kod' => $kod,
            'tip' => $kanal === 'sms' ? 'sms' : 'eposta',
            'kullanildi' => false,
            'gecerlilik_tarihi' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        // SMS/e-posta gönder (TODO: SMS/e-posta servisi)
        // $this->sendOtp($uye, $kod, $kanal);
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
