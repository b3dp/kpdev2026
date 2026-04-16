<?php

namespace App\Http\Controllers\Uye;

use App\Data\TurkiyeIller;
use App\Enums\BagisDurumu;
use App\Enums\EkayitDurumu;
use App\Enums\EtkinlikDurumu;
use App\Http\Controllers\Controller;
use App\Models\Bagis;
use App\Models\EkayitKayit;
use App\Models\Etkinlik;
use App\Models\MezunProfil;
use App\Models\Uye;
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
        /** @var Uye $uye */
        $uye = Auth::guard('uye')->user()->load(['rozetler', 'mezunProfil.kurum']);

        $uyeEposta = filled($uye->eposta) ? trim((string) $uye->eposta) : null;
        $telefonAdaylari = collect([
            $uye->telefon,
            preg_replace('/[^0-9]/', '', (string) $uye->telefon),
        ])->filter()->unique()->values();

        $bagisSorgusu = Bagis::query()
            ->where('durum', BagisDurumu::Odendi)
            ->where(function ($query) use ($uye, $uyeEposta, $telefonAdaylari): void {
                $query->where('uye_id', $uye->id);

                if ($uyeEposta || $telefonAdaylari->isNotEmpty()) {
                    $query->orWhereHas('kisiler', function ($kisiQuery) use ($uyeEposta, $telefonAdaylari): void {
                        $kisiQuery->where(function ($iletisimQuery) use ($uyeEposta, $telefonAdaylari): void {
                            if ($uyeEposta) {
                                $iletisimQuery->orWhere('eposta', $uyeEposta);
                            }

                            foreach ($telefonAdaylari as $telefon) {
                                $iletisimQuery->orWhere('telefon', $telefon);
                            }
                        });
                    });
                }
            });

        $bagislar = (clone $bagisSorgusu)
            ->latest('odeme_tarihi')
            ->latest('id')
            ->take(6)
            ->get();

        $bagisOzeti = [
            'adet' => (clone $bagisSorgusu)->count(),
            'toplam' => (float) (clone $bagisSorgusu)->sum('toplam_tutar'),
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

        $ekayitSorgusu = EkayitKayit::query()
            ->with(['sinif.donem', 'sinif.kurum', 'ogrenciBilgisi', 'veliBilgisi'])
            ->where(function ($query) use ($uye, $uyeEposta, $telefonAdaylari): void {
                $query->where('uye_id', $uye->id);

                if ($uyeEposta || $telefonAdaylari->isNotEmpty()) {
                    $query->orWhereHas('veliBilgisi', function ($veliQuery) use ($uyeEposta, $telefonAdaylari): void {
                        $veliQuery->where(function ($iletisimQuery) use ($uyeEposta, $telefonAdaylari): void {
                            if ($uyeEposta) {
                                $iletisimQuery->orWhere('eposta', $uyeEposta);
                            }

                            foreach ($telefonAdaylari as $telefon) {
                                $iletisimQuery->orWhere('telefon_1', $telefon)
                                    ->orWhere('telefon_2', $telefon);
                            }
                        });
                    });
                }
            });

        $ekayitKayitlar = (clone $ekayitSorgusu)
            ->latest('durum_tarihi')
            ->latest('updated_at')
            ->latest('id')
            ->take(5)
            ->get();

        $ekayitOzeti = [
            'adet' => (clone $ekayitSorgusu)->count(),
            'bekleyen' => (clone $ekayitSorgusu)
                ->whereIn('durum', [EkayitDurumu::Beklemede->value, EkayitDurumu::Yedek->value])
                ->count(),
            'onaylanan' => (clone $ekayitSorgusu)
                ->where('durum', EkayitDurumu::Onaylandi->value)
                ->count(),
        ];

        $mezuniyetYillari = range((int) now()->year, 1970);
        $iller = TurkiyeIller::secenekler();

        return view('uye.profil', [
            'uye' => $uye,
            'mezunProfil' => $uye->mezunProfil,
            'bagislar' => $bagislar,
            'bagisOzeti' => $bagisOzeti,
            'ekayitKayitlar' => $ekayitKayitlar,
            'ekayitOzeti' => $ekayitOzeti,
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
            'acik_adres' => ['nullable', 'string', 'max:2000'],
            'aciklama' => ['nullable', 'string', 'max:2000'],
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
            'acik_adres' => filled($veri['acik_adres'] ?? null) ? trim((string) $veri['acik_adres']) : null,
            'aciklama' => filled($veri['aciklama'] ?? null) ? trim((string) $veri['aciklama']) : null,
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

}
