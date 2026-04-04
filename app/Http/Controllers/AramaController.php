<?php

namespace App\Http\Controllers;

use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\KurumsalSayfa;
use App\Services\AramaService;
use Illuminate\Http\Request;

class AramaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        /** @var AramaService $arama_servisi */
        $arama_servisi = app(AramaService::class);

        $haberler = collect();
        $etkinlikler = collect();
        $sayfalar = collect();

        if (mb_strlen($q) >= 2) {
            if (! $request->filled('tip')) {
                $arama_servisi->kaydetArama($q);
            }

            $haberler = Haber::with('kategori')
                ->where('durum', 'yayinda')
                ->where(fn ($query) => $query
                    ->where('baslik', 'like', "%{$q}%")
                    ->orWhere('ozet', 'like', "%{$q}%"))
                ->latest('yayin_tarihi')
                ->take(10)
                ->get();

            $etkinlikler = Etkinlik::where('durum', 'yayinda')
                ->where(function ($query) use ($q) {
                    $query->where('baslik', 'like', "%{$q}%")
                        ->orWhere('ozet', 'like', "%{$q}%")
                        ->orWhere('konum_ad', 'like', "%{$q}%");
                })
                ->latest('baslangic_tarihi')
                ->take(5)
                ->get();

            $sayfalar = KurumsalSayfa::where('durum', 'yayinda')
                ->where(function ($query) use ($q) {
                    $query->where('ad', 'like', "%{$q}%")
                        ->orWhere('ozet', 'like', "%{$q}%");
                })
                ->orderBy('sira')
                ->take(5)
                ->get();
        }

        $toplamSonuc = $haberler->count()
            + $etkinlikler->count()
            + $sayfalar->count();

        $populerAramalar = $arama_servisi->getirPopulerAramalar();

        return view('pages.arama', compact(
            'q', 'haberler', 'etkinlikler', 'sayfalar', 'toplamSonuc', 'populerAramalar'
        ));
    }
}
