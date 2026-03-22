<?php

namespace App\Http\Controllers\Uye;

use App\Http\Controllers\Controller;
use App\Models\Uye;
use Illuminate\Http\Request;

class AbonelikController extends Controller
{
    /**
     * Abonelik iptal - Token bazlı
     */
    public function iptal(Request $request, string $token, string $kanal)
    {
        // Token'i hash'leştir ve veritabanında ara
        $uyeToken = hash('sha256', $token);
        $uye = Uye::where('abonelik_token', $uyeToken)->first();

        if (!$uye) {
            return view('uye.abonelik-hata', ['mesaj' => 'Geçersiz link.']);
        }

        // Kanal kontrol et
        if ($kanal === 'sms') {
            $uye->update(['sms_abonelik' => false]);
            $mesaj = 'SMS aboneliğiniz iptal edilmiştir.';
        } elseif ($kanal === 'eposta') {
            $uye->update(['eposta_abonelik' => false]);
            $mesaj = 'E-posta aboneliğiniz iptal edilmiştir.';
        } else {
            return view('uye.abonelik-hata', ['mesaj' => 'Geçersiz kanal.']);
        }

        return view('uye.abonelik-basarili', ['mesaj' => $mesaj]);
    }
}
