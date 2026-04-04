<?php

namespace App\Http\Controllers;

use App\Models\BagisTuru;
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
        $sepet = session('sepet', []);

        return view('pages.bagis.detay', compact('bagisTuru', 'sepet'));
    }

    public function sepeteEkle(Request $request): JsonResponse
    {
        $veri = $request->validate([
            'slug' => ['required', 'string', 'exists:bagis_turleri,slug'],
            'tutar' => ['required', 'numeric', 'min:1'],
            'adet' => ['nullable', 'integer', 'min:1', 'max:7'],
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

        if ($satir === false) {
            return response()->json([
                'message' => 'Bu bağış türü sepetinizde zaten var.',
            ], 409);
        }

        $sessionSepet = collect($request->session()->get('sepet', []))
            ->reject(fn (array $satir) => ($satir['bagis_turu_id'] ?? null) === $bagisTuru->id)
            ->values()
            ->all();

        $sessionSepet[] = [
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
            'sepet_adet' => count($sessionSepet),
            'toplam' => collect($sessionSepet)->sum('toplam'),
        ]);
    }

    public function tesekkur()
    {
        return view('welcome');
    }
}
