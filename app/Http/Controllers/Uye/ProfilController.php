<?php

namespace App\Http\Controllers\Uye;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProfilController extends Controller
{
    /**
     * Profil sayfası
     */
    public function index()
    {
        $uye = Auth::guard('uye')->user();
        return view('uye.profil', ['uye' => $uye]);
    }

    /**
     * Profil güncelle
     */
    public function guncelle(Request $request)
    {
        $uye = Auth::guard('uye')->user();

        $request->validate([
            'ad_soyad' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-]+$/u'],
            'eposta' => ['nullable', 'email:rfc,dns'],
        ]);

        // E-posta benzersizliği kontrol et
        if ($request->filled('eposta') && $request->input('eposta') !== $uye->eposta) {
            if (\App\Models\Uye::where('eposta', $request->input('eposta'))->exists()) {
                throw ValidationException::withMessages(['eposta' => 'Bu e-posta zaten kullanılıyor.']);
            }
        }

        $uye->update([
            'ad_soyad' => $request->input('ad_soyad'),
            'eposta' => $request->input('eposta'),
        ]);

        return response()->json(['success' => true, 'message' => 'Profil güncellendi.']);
    }

    /**
     * Abonelik güncelle
     */
    public function abonelikGuncelle(Request $request)
    {
        $uye = Auth::guard('uye')->user();

        $request->validate([
            'sms_abonelik' => ['nullable', 'boolean'],
            'eposta_abonelik' => ['nullable', 'boolean'],
        ]);

        $uye->update([
            'sms_abonelik' => $request->has('sms_abonelik'),
            'eposta_abonelik' => $request->has('eposta_abonelik'),
        ]);

        return response()->json(['success' => true, 'message' => 'Abonelik tercihleri güncellendi.']);
    }
}
