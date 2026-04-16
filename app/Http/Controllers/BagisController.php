<?php

namespace App\Http\Controllers;

use App\Models\Bagis;
use App\Models\BagisTuru;
use App\Services\BagisOdemeService;
use App\Services\SepetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BagisController extends Controller
{
    public function index()
    {
        $bagisturleri = BagisTuru::orderBy('sira')->get();

        return view('pages.bagis.index', compact('bagisturleri'));
    }

    public function show(string $slug)
    {
        $bagisTuru = BagisTuru::where('slug', $slug)->firstOrFail();
        $sepet = $this->sessionSepetiGetir(request());
        $testOdemeAktif = app(BagisOdemeService::class)->testModuAktifMi();
        $testKartlari = $testOdemeAktif ? app(BagisOdemeService::class)->testKartlariniGetir() : [];

        return view('pages.bagis.detay', compact('bagisTuru', 'sepet', 'testOdemeAktif', 'testKartlari'));
    }

    public function sepet(Request $request)
    {
        $sepet = $this->sessionSepetiGetir($request);
        $sepetToplam = $this->sepetToplaminiHesapla($sepet);
        $odemeSayfasiUrl = filled($sepet[0]['slug'] ?? null)
            ? route('bagis.show', $sepet[0]['slug'])
            : route('bagis.index');

        return view('pages.bagis.sepet', compact('sepet', 'sepetToplam', 'odemeSayfasiUrl'));
    }

    public function sepeteEkle(Request $request): JsonResponse
    {
        $veri = $request->validate([
            'slug' => ['required', 'string', 'exists:bagis_turleri,slug'],
            'tutar' => ['required', 'numeric', 'min:1'],
            'adet' => ['nullable', 'integer', 'min:1', 'max:30'],
            'sahip_tipi' => ['nullable', 'in:kendi,baskasi'],
            'form_verisi' => ['nullable', 'array'],
        ]);

        $bagisTuru = BagisTuru::where('slug', $veri['slug'])->firstOrFail();
        $tutar = (float) $veri['tutar'];
        $adet = (int) ($veri['adet'] ?? 1);
        $sahipTipi = (string) ($veri['sahip_tipi'] ?? 'kendi');

        if ($bagisTuru->minimum_tutar && $tutar < (float) $bagisTuru->minimum_tutar) {
            return response()->json([
                'message' => 'Minimum bağış tutarı ₺'.number_format((float) $bagisTuru->minimum_tutar, 0, ',', '.').' olmalıdır.',
            ], 422);
        }

        $sepetService = app(SepetService::class);
        $dbSepet = $sepetService->aktifSepetAl($request);
        $satir = $sepetService->sepeteEkle($dbSepet, $bagisTuru, $adet, $sahipTipi, $tutar);

        $sessionSepet = $this->sessionSepetiGetir($request);
        $sessionSepet[] = [
            'satir_id' => $satir->id,
            'bagis_turu_id' => $bagisTuru->id,
            'slug' => $bagisTuru->slug,
            'ad' => $bagisTuru->ad,
            'adet' => $adet,
            'birim_fiyat' => $tutar,
            'toplam' => $tutar * $adet,
            'sahip_tipi' => $sahipTipi,
            'form_verisi' => $veri['form_verisi'] ?? [],
        ];

        $request->session()->put('sepet', $sessionSepet);

        return response()->json([
            'message' => 'Bağış sepetinize eklendi.',
            'sepet' => $sessionSepet,
            'sepet_adet' => count($sessionSepet),
            'toplam' => $this->sepetToplaminiHesapla($sessionSepet),
        ]);
    }

    public function sepettenCikar(Request $request, int $satirId): JsonResponse|
\Illuminate\Http\RedirectResponse
    {
        $sepetService = app(SepetService::class);
        $dbSepet = $sepetService->aktifSepetAl($request);
        $sepetService->sepettenCikar($dbSepet, $satirId);

        $sessionSepet = collect($this->sessionSepetiGetir($request))
            ->reject(fn (array $satir) => (int) ($satir['satir_id'] ?? 0) === $satirId)
            ->values()
            ->all();

        $request->session()->put('sepet', $sessionSepet);

        if (! $request->expectsJson()) {
            return back()->with('success', 'Bağış kalemi sepetten çıkarıldı.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Bağış kalemi sepetten çıkarıldı.',
            'sepet' => $sessionSepet,
            'sepet_adet' => count($sessionSepet),
            'toplam' => $this->sepetToplaminiHesapla($sessionSepet),
        ]);
    }

    public function odemeYap(Request $request): JsonResponse
    {
        $veri = $request->validate([
            'slug' => ['required', 'string', 'exists:bagis_turleri,slug'],
            'tutar' => ['required', 'numeric', 'min:1'],
            'adet' => ['nullable', 'integer', 'min:1', 'max:30'],
            'sahip_tipi' => ['nullable', 'in:kendi,baskasi'],
            'odeme_yontemi' => ['nullable', 'in:albaraka,paytr'],
            'kart_no' => ['required', 'string', 'min:12'],
            'kart_sahibi' => ['required', 'string', 'max:255'],
            'son_kullanma_ay' => ['required', 'string', 'min:2', 'max:2'],
            'son_kullanma_yil' => ['required', 'string', 'min:2', 'max:4'],
            'cvv' => ['required', 'string', 'min:3', 'max:4'],
            'form_verisi' => ['nullable', 'array'],
        ]);

        $bagis = app(BagisOdemeService::class)->odemeYap($request, $veri);

        $request->session()->put('son_bagis_no', $bagis->bagis_no);
        $request->session()->forget('sepet');

        return response()->json([
            'success' => true,
            'test_modu' => true,
            'message' => 'Test ödeme başarıyla alındı. Gerçek tahsilat yapılmadı.',
            'redirect_url' => route('bagis.tesekkur'),
            'bagis_no' => $bagis->bagis_no,
        ]);
    }

    public function makbuzDurum(string $bagisNo): JsonResponse
    {
        $bagis = Bagis::query()->where('bagis_no', $bagisNo)->firstOrFail();

        return response()->json([
            'hazir' => filled($bagis->makbuz_yol),
            'bagis_no' => $bagis->bagis_no,
            'makbuz_url' => $bagis->makbuzUrl(),
            'makbuz_gonderildi' => (bool) $bagis->makbuz_gonderildi,
        ]);
    }

    public function tesekkur()
    {
        $bagis = Bagis::where('bagis_no', session('son_bagis_no'))
            ->with(['kisiler', 'kalemler.bagisTuru'])
            ->first();

        if (! $bagis) {
            return redirect()
                ->route('bagis.index')
                ->with('info', 'Bağış bilgisi bulunamadı.');
        }

        $sepet = session('sepet', []);

        return view('pages.bagis.tesekkur', compact('bagis', 'sepet'));
    }

    private function sessionSepetiGetir(Request $request): array
    {
        $sepet = $request->session()->get('sepet', []);

        return is_array($sepet) ? array_values($sepet) : [];
    }

    private function sepetToplaminiHesapla(array $sepet): float
    {
        return (float) collect($sepet)->sum(fn (array $satir) => (float) ($satir['toplam'] ?? 0));
    }
}
