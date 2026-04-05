<?php

namespace App\Http\Controllers;

use App\Models\MezunProfil;
use App\Models\Uye;
use App\Services\ZeptomailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MezunController extends Controller
{
    public function index()
    {
        $mezunlar = MezunProfil::query()
            ->with(['uye', 'kurum'])
            ->where('durum', 'aktif')
            ->latest('mezuniyet_yili')
            ->latest('id')
            ->take(4)
            ->get();

        $istatistikler = [
            'aktif_mezun' => MezunProfil::query()->where('durum', 'aktif')->count(),
            'hafiz_mezun' => MezunProfil::query()->where('durum', 'aktif')->where('hafiz', true)->count(),
            'yil_araligi' => MezunProfil::query()->whereNotNull('mezuniyet_yili')->count() > 0
                ? ((int) now()->year - (int) MezunProfil::query()->min('mezuniyet_yili') + 1)
                : ((int) now()->year - 1966 + 1),
        ];

        $mezuniyetYillari = range((int) now()->year, 1970);

        return view('pages.mezunlar.index', compact('mezunlar', 'istatistikler', 'mezuniyetYillari'));
    }

    public function store(Request $request)
    {
        $veri = $request->validate([
            'ad_soyad' => ['required', 'string', 'max:255'],
            'eposta' => ['nullable', 'email', 'max:255', 'required_without:telefon'],
            'telefon' => ['nullable', 'string', 'min:10', 'max:20', 'required_without:eposta'],
            'mezuniyet_yili' => ['required', 'integer', 'min:1960', 'max:' . now()->year],
            'meslek' => ['nullable', 'string', 'max:255'],
            'kvkk' => ['accepted'],
        ], [
            'ad_soyad.required' => 'Ad soyad alanı zorunludur.',
            'eposta.required_without' => 'E-posta veya telefon alanlarından biri zorunludur.',
            'telefon.required_without' => 'Telefon veya e-posta alanlarından biri zorunludur.',
            'mezuniyet_yili.required' => 'Mezuniyet yılı zorunludur.',
            'kvkk.accepted' => 'KVKK onayı gereklidir.',
        ]);

        try {
            $eposta = trim((string) ($veri['eposta'] ?? ''));
            $telefon = preg_replace('/[^0-9]/', '', (string) ($veri['telefon'] ?? ''));

            $uye = null;

            if ($eposta !== '') {
                $uye = Uye::query()->where('eposta', $eposta)->first();
            }

            if (! $uye && $telefon !== '') {
                $uye = Uye::query()->where('telefon', $telefon)->first();
            }

            if (! $uye) {
                $uye = Uye::query()->create([
                    'ad_soyad' => $veri['ad_soyad'],
                    'eposta' => $eposta !== '' ? $eposta : null,
                    'telefon' => $telefon !== '' ? $telefon : null,
                    'durum' => 'aktif',
                    'aktif' => true,
                    'sms_abonelik' => true,
                    'eposta_abonelik' => true,
                ]);

                app(\App\Services\KisiEslestirmeService::class)->uyeEslestir($uye);
            } else {
                $guncellenecekAlanlar = [];

                if (blank($uye->ad_soyad)) {
                    $guncellenecekAlanlar['ad_soyad'] = $veri['ad_soyad'];
                }

                if (blank($uye->eposta) && $eposta !== '') {
                    $guncellenecekAlanlar['eposta'] = $eposta;
                }

                if (blank($uye->telefon) && $telefon !== '') {
                    $guncellenecekAlanlar['telefon'] = $telefon;
                }

                if ($guncellenecekAlanlar !== []) {
                    $uye->update($guncellenecekAlanlar);
                }
            }

            $profil = MezunProfil::query()->firstOrNew(['uye_id' => $uye->id]);
            $profil->mezuniyet_yili = (int) $veri['mezuniyet_yili'];
            $profil->meslek = $veri['meslek'] ?: $profil->meslek;
            $profil->durum = $profil->exists && $profil->durum === 'aktif' ? 'aktif' : 'beklemede';
            $profil->save();

            Log::info('Mezun kayıt başvurusu alındı.', [
                'uye_id' => $uye->id,
                'eposta' => $uye->eposta,
                'telefon' => $uye->telefon,
                'mezuniyet_yili' => $profil->mezuniyet_yili,
            ]);

            $aliciEposta = config('iletisim.merkez_eposta') ?: config('site.eposta');

            if (filled($aliciEposta)) {
                app(ZeptomailService::class)->yoneticiAlertGonder([
                    [
                        'eposta' => $aliciEposta,
                        'ad' => config('site.ad') . ' Mezun Başvurusu',
                    ],
                ], 'Yeni Mezun Başvurusu', "Ad Soyad: {$veri['ad_soyad']}\nE-posta: {$eposta}\nTelefon: {$telefon}\nMezuniyet Yılı: {$veri['mezuniyet_yili']}\nMeslek: " . ($veri['meslek'] ?: 'Belirtilmedi'));
            }

            return redirect()
                ->route('mezunlar.index')
                ->with('success', 'Başvurunuz alındı. Mezun kaydınız incelendikten sonra size dönüş yapılacaktır.');
        } catch (\Throwable $exception) {
            Log::error('Mezun kayıt başvurusu sırasında hata oluştu.', [
                'hata' => $exception->getMessage(),
                'eposta' => $veri['eposta'] ?? null,
                'telefon' => $veri['telefon'] ?? null,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Başvurunuz alınırken bir sorun oluştu. Lütfen tekrar deneyin.');
        }
    }

    public function show(string $id)
    {
        return view('welcome');
    }
}
