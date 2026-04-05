<?php

namespace App\Http\Controllers\Uye;

use App\Data\TurkiyeIller;
use App\Enums\BagisDurumu;
use App\Enums\EtkinlikDurumu;
use App\Http\Controllers\Controller;
use App\Models\Bagis;
use App\Models\Etkinlik;
use App\Models\MezunProfil;
use App\Models\Uye;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfilController extends Controller
{
    /**
     * Profil sayfası
     */
    public function index()
    {
        /** @var Uye $uye */
        $uye = Auth::guard('uye')->user()->load(['rozetler', 'mezunProfil.kurum']);

        $bagislar = Bagis::query()
            ->where('uye_id', $uye->id)
            ->where('durum', BagisDurumu::Odendi)
            ->latest('odeme_tarihi')
            ->latest('id')
            ->take(6)
            ->get();

        $bagisOzeti = [
            'adet' => Bagis::query()->where('uye_id', $uye->id)->where('durum', BagisDurumu::Odendi)->count(),
            'toplam' => (float) Bagis::query()->where('uye_id', $uye->id)->where('durum', BagisDurumu::Odendi)->sum('toplam_tutar'),
            'son_bagis' => optional($bagislar->first())->toplam_tutar,
        ];

        $yaklasanEtkinlikler = Etkinlik::query()
            ->where('durum', EtkinlikDurumu::Yayinda)
            ->where('baslangic_tarihi', '>=', now()->startOfDay())
            ->orderBy('baslangic_tarihi')
            ->take(3)
            ->get();

        $gecmisEtkinlikler = Etkinlik::query()
            ->whereIn('durum', [EtkinlikDurumu::Yayinda, EtkinlikDurumu::Tamamlandi])
            ->where('baslangic_tarihi', '<', now())
            ->orderByDesc('baslangic_tarihi')
            ->take(3)
            ->get();

        $mezuniyetYillari = range((int) now()->year, 1970);
        $iller = TurkiyeIller::secenekler();

        return view('uye.profil', [
            'uye' => $uye,
            'mezunProfil' => $uye->mezunProfil,
            'bagislar' => $bagislar,
            'bagisOzeti' => $bagisOzeti,
            'yaklasanEtkinlikler' => $yaklasanEtkinlikler,
            'gecmisEtkinlikler' => $gecmisEtkinlikler,
            'mezuniyetYillari' => $mezuniyetYillari,
            'iller' => $iller,
        ]);
    }

    /**
     * Profil güncelle
     */
    public function guncelle(Request $request)
    {
        /** @var Uye $uye */
        $uye = Auth::guard('uye')->user();

        $veri = $request->validate([
            'ad_soyad' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-]+$/u'],
            'eposta' => ['nullable', 'email', 'max:255'],
            'mezuniyet_yili' => ['nullable', 'integer', 'min:1960', 'max:' . now()->year],
            'meslek' => ['nullable', 'string', 'max:255'],
            'gorev_il' => ['nullable', 'string', 'max:100'],
            'gorev_ilce' => ['nullable', 'string', 'max:100'],
            'ikamet_il' => ['nullable', 'string', 'max:100'],
            'ikamet_ilce' => ['nullable', 'string', 'max:100'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'twitter' => ['nullable', 'string', 'max:255'],
            'hafiz' => ['nullable', 'boolean'],
            'sms_abonelik' => ['nullable', 'boolean'],
            'eposta_abonelik' => ['nullable', 'boolean'],
        ]);

        if ($request->filled('eposta') && $request->input('eposta') !== $uye->eposta) {
            if (Uye::query()
                ->where('eposta', $request->input('eposta'))
                ->whereKeyNot($uye->id)
                ->exists()) {
                throw ValidationException::withMessages(['eposta' => 'Bu e-posta zaten kullanılıyor.']);
            }
        }

        $uye->update([
            'ad_soyad' => trim((string) $veri['ad_soyad']),
            'eposta' => filled($veri['eposta'] ?? null) ? trim((string) $veri['eposta']) : null,
            'sms_abonelik' => $request->boolean('sms_abonelik'),
            'eposta_abonelik' => $request->boolean('eposta_abonelik'),
        ]);

        $mezunVerisi = [
            'mezuniyet_yili' => $veri['mezuniyet_yili'] ?? null,
            'meslek' => filled($veri['meslek'] ?? null) ? trim((string) $veri['meslek']) : null,
            'gorev_il' => filled($veri['gorev_il'] ?? null) ? trim((string) $veri['gorev_il']) : null,
            'gorev_ilce' => filled($veri['gorev_ilce'] ?? null) ? trim((string) $veri['gorev_ilce']) : null,
            'ikamet_il' => filled($veri['ikamet_il'] ?? null) ? trim((string) $veri['ikamet_il']) : null,
            'ikamet_ilce' => filled($veri['ikamet_ilce'] ?? null) ? trim((string) $veri['ikamet_ilce']) : null,
            'linkedin' => filled($veri['linkedin'] ?? null) ? trim((string) $veri['linkedin']) : null,
            'instagram' => filled($veri['instagram'] ?? null) ? trim((string) $veri['instagram']) : null,
            'twitter' => filled($veri['twitter'] ?? null) ? trim((string) $veri['twitter']) : null,
            'hafiz' => $request->boolean('hafiz'),
        ];

        $mezunProfilVar = $uye->mezunProfil()->exists();
        $doldurulanAlanVar = collect($mezunVerisi)
            ->filter(fn ($deger) => ! is_null($deger) && $deger !== '' && $deger !== false)
            ->isNotEmpty();

        if ($mezunProfilVar || $doldurulanAlanVar) {
            MezunProfil::query()->updateOrCreate(
                ['uye_id' => $uye->id],
                array_merge($mezunVerisi, [
                    'durum' => $uye->mezunProfil?->durum ?? 'beklemede',
                ])
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil bilgileriniz güncellendi.',
        ]);
    }

    /**
     * Abonelik güncelle
     */
    public function abonelikGuncelle(Request $request)
    {
        /** @var Uye $uye */
        $uye = Auth::guard('uye')->user();

        $request->validate([
            'sms_abonelik' => ['nullable', 'boolean'],
            'eposta_abonelik' => ['nullable', 'boolean'],
        ]);

        $uye->update([
            'sms_abonelik' => $request->boolean('sms_abonelik'),
            'eposta_abonelik' => $request->boolean('eposta_abonelik'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bildirim tercihleriniz güncellendi.',
        ]);
    }

    /**
     * Şifre güncelle
     */
    public function sifreGuncelle(Request $request)
    {
        /** @var Uye $uye */
        $uye = Auth::guard('uye')->user();

        $veri = $request->validate([
            'mevcut_sifre' => ['required', 'string'],
            'yeni_sifre' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'yeni_sifre.confirmed' => 'Yeni şifre tekrarı eşleşmiyor.',
        ]);

        if (! Hash::check($veri['mevcut_sifre'], (string) $uye->sifre)) {
            throw ValidationException::withMessages([
                'mevcut_sifre' => 'Mevcut şifreniz hatalı.',
            ]);
        }

        $uye->update([
            'sifre' => $veri['yeni_sifre'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Şifreniz başarıyla güncellendi.',
        ]);
    }
}
