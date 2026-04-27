<?php

namespace App\Http\Controllers;

use App\Enums\BagisDurumu;
use App\Models\Bagis;
use App\Models\BagisTuru;
use App\Models\OdemeHatasi;
use App\Services\AlbarakaService;
use App\Services\BagisOdemeService;
use App\Services\SepetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

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

    public function odemeYap(Request $request): JsonResponse|Response
    {
        $albarakaAktif = (bool) config('services.albaraka.aktif', false);
        $albarakaUseOos = (int) config('services.albaraka.use_oos', 1) === 1;

        $kurallar = [
            'slug' => ['required', 'string', 'exists:bagis_turleri,slug'],
            'tutar' => ['required', 'numeric', 'min:1'],
            'adet' => ['nullable', 'integer', 'min:1', 'max:30'],
            'sahip_tipi' => ['nullable', 'in:kendi,baskasi'],
            'odeme_yontemi' => ['nullable', 'in:albaraka,paytr'],
            'form_verisi' => ['nullable', 'array'],
        ];

        if ($albarakaAktif && ! $albarakaUseOos) {
            // UseOOS=0: kart bilgisi bizim formdan alınır.
            $kurallar += [
                'kart_no' => ['required', 'string', 'min:12'],
                'kart_sahibi' => ['required', 'string', 'max:255'],
                'son_kullanma' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\s*\/\s*(\d{2}|\d{4})$/'],
                'cvv' => ['required', 'string', 'regex:/^\d{3,4}$/'],
            ];
        } elseif (! $albarakaAktif) {
            // Test modunda kart alanları zorunlu
            $kurallar += [
                'kart_no' => ['required', 'string', 'min:12'],
                'kart_sahibi' => ['required', 'string', 'max:255'],
                'son_kullanma' => ['nullable', 'string', 'regex:/^(0[1-9]|1[0-2])\s*\/\s*(\d{2}|\d{4})$/'],
                'son_kullanma_ay' => ['required_without:son_kullanma', 'string', 'min:2', 'max:2'],
                'son_kullanma_yil' => ['required_without:son_kullanma', 'string', 'min:2', 'max:4'],
                'cvv' => ['required', 'string', 'min:3', 'max:4'],
            ];
        }

        $veri = $request->validate($kurallar);

        if (filled($veri['son_kullanma'] ?? null)) {
            [$ay, $yil] = $this->sonKullanmaParcala((string) $veri['son_kullanma']);
            $veri['son_kullanma_ay'] = $ay;
            $veri['son_kullanma_yil'] = $yil;
        }

        if ($albarakaAktif) {
            // 3D Secure yönlendirme: bagis oluştur, banka formunu döndür
            $bagis = app(BagisOdemeService::class)->bagisKaydet($request, $veri);

            $request->session()->put('son_bagis_no', $bagis->bagis_no);

            // Tutar kuruşa çevir (1 TL = 100)
            $tutarKurus = (int) round($bagis->toplam_tutar * 100);
            $html = app(AlbarakaService::class)->ucBoyutluFormOlustur($bagis->id, $tutarKurus, [
                'kart_no' => $veri['kart_no'] ?? '',
                'kart_sahibi' => $veri['kart_sahibi'] ?? '',
                'son_kullanma_ay' => $veri['son_kullanma_ay'] ?? '',
                'son_kullanma_yil' => $veri['son_kullanma_yil'] ?? '',
                'cvv' => $veri['cvv'] ?? '',
            ]);

            return response($html, 200)->header('Content-Type', 'text/html');
        }

        // Test modu
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

    /**
     * Albaraka 3D Secure geri dönüş noktası (bank POST callback).
     * CSRF muafiyeti routes/web.php'de tanımlanmıştır.
     */
    public function albarakaCallback(Request $request): RedirectResponse
    {
        $data = $request->all();

        Log::channel('odeme')->info('Albaraka callback alındı.', [
            'MdStatus' => $data['MdStatus'] ?? 'yok',
            'OrderId'  => $data['OrderId'] ?? 'yok',
            'keys'     => array_keys($data),
        ]);

        $orderId = (string) ($data['OrderId'] ?? '');
        $hataliUrl = (string) config('services.albaraka.hatali_url', route('bagis.sepet'));
        $basariliUrl = (string) config('services.albaraka.basarili_url', route('bagis.tesekkur'));

        if ($orderId === '') {
            Log::channel('odeme')->warning('Albaraka callback: OrderId eksik.');
            return redirect($hataliUrl)->with('error', 'Ödeme işlemi başarısız oldu.');
        }

        // Albaraka'dan gelen padli OrderId'den asıl bagis.id'yi çöz
        $bagisId = app(AlbarakaService::class)->bagisIdCoz($orderId);

        // Bağışı id ile bul
        $bagis = Bagis::query()->find($bagisId);

        if (! $bagis) {
            Log::channel('odeme')->warning('Albaraka callback: Bağış bulunamadı.', ['orderId' => $orderId, 'bagisId' => $bagisId]);
            return redirect($hataliUrl)->with('error', 'Ödeme bilgisi bulunamadı.');
        }

        // İdempotency: zaten ödenmiş
        if ($bagis->durum === BagisDurumu::Odendi) {
            $request->session()->put('son_bagis_no', $bagis->bagis_no);
            return redirect($basariliUrl);
        }

        $albarakaService = app(AlbarakaService::class);

        // MAC doğrulama ve MdStatus=1 kontrolü
        if (! $albarakaService->callbackDogrula($data)) {
            $mdStatus = (string) ($data['MdStatus'] ?? '?');
            $mdStatusHatasi = $mdStatus !== '1';
            Log::channel('odeme')->warning('Albaraka callback: MAC doğrulama başarısız veya MdStatus!=1.', [
                'orderId'  => $orderId,
                'mdStatus' => $mdStatus,
            ]);

            OdemeHatasi::query()->create([
                'bagis_id'    => $bagis->id,
                'saglayici'   => 'albaraka',
                'hata_kodu'   => $mdStatusHatasi ? 'MDSTATUS_'.$mdStatus : 'MAC_INVALID',
                'hata_mesaji' => $mdStatusHatasi
                    ? '3D Secure doğrulama başarısız. MdStatus: '.$mdStatus
                    : '3D Secure doğrulama başarısız. Callback MAC doğrulanamadı.',
                'tutar'       => $bagis->toplam_tutar,
                'created_at'  => now(),
            ]);

            return redirect($hataliUrl)->with('error', '3D Secure doğrulama başarısız oldu. Lütfen tekrar deneyiniz.');
        }

        // Tutar doğrulama: callback'teki Amount kuruş cinsinden gelir
        $callbackTutar = (int) ($data['Amount'] ?? 0);
        $beklenenTutar = (int) round($bagis->toplam_tutar * 100);

        if ($callbackTutar !== $beklenenTutar) {
            Log::channel('odeme')->error('Albaraka callback: Tutar uyuşmazlığı.', [
                'orderId'         => $orderId,
                'callbackTutar'   => $callbackTutar,
                'beklenenTutar'   => $beklenenTutar,
            ]);

            OdemeHatasi::query()->create([
                'bagis_id'    => $bagis->id,
                'saglayici'   => 'albaraka',
                'hata_kodu'   => 'AMOUNT_MISMATCH',
                'hata_mesaji' => "Tutar uyuşmazlığı: beklenen {$beklenenTutar}, gelen {$callbackTutar}",
                'tutar'       => $bagis->toplam_tutar,
                'created_at'  => now(),
            ]);

            return redirect($hataliUrl)->with('error', 'Ödeme tutarı doğrulanamadı.');
        }

        // Sale çağrısı
        $sonuc = $albarakaService->satisYap($data, $bagisId, $beklenenTutar);

        if (! $sonuc['basarili']) {
            OdemeHatasi::query()->create([
                'bagis_id'    => $bagis->id,
                'saglayici'   => 'albaraka',
                'hata_kodu'   => $sonuc['hata_kodu'] ?? 'UNKNOWN',
                'hata_mesaji' => $sonuc['hata_mesaji'] ?? 'Sale çağrısı başarısız.',
                'tutar'       => $bagis->toplam_tutar,
                'created_at'  => now(),
            ]);

            return redirect($hataliUrl)->with('error', 'Ödeme işlemi bankada reddedildi. Lütfen tekrar deneyiniz.');
        }

        // Başarılı: bağışı güncelle, iş kuyruğunu tetikle
        $referans = (string) ($sonuc['referans'] ?? $orderId);
        app(BagisOdemeService::class)->odemeBasarili($bagis, $referans, $request);

        $request->session()->put('son_bagis_no', $bagis->bagis_no);

        return redirect($basariliUrl);
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

    private function sonKullanmaParcala(string $deger): array
    {
        $parcalar = preg_split('/\s*\/\s*/', trim($deger)) ?: [];
        $ay = preg_replace('/\D+/', '', (string) ($parcalar[0] ?? '')) ?: '';
        $yil = preg_replace('/\D+/', '', (string) ($parcalar[1] ?? '')) ?: '';

        $ay = str_pad(substr($ay, 0, 2), 2, '0', STR_PAD_LEFT);
        if (strlen($yil) === 4) {
            $yil = substr($yil, 2, 2);
        } else {
            $yil = str_pad(substr($yil, 0, 2), 2, '0', STR_PAD_LEFT);
        }

        return [$ay, $yil];
    }
}
