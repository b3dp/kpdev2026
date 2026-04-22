<?php

namespace App\Http\Controllers;

use App\Models\BagisTuru;
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

        $bagisTurleri = collect();
        $haberler = collect();
        $etkinlikler = collect();
        $sayfalar = collect();

        if (mb_strlen($q) >= 2) {
            if (! $request->filled('tip')) {
                $arama_servisi->kaydetArama($q);
            }

            $bagisTurleri = BagisTuru::query()
                ->where('aktif', true)
                ->where(function ($query) use ($q) {
                    $query->where('ad', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhere('aciklama', 'like', "%{$q}%");
                })
                ->orderByRaw(
                    "CASE
                        WHEN LOWER(ad) = LOWER(?) THEN 0
                        WHEN LOWER(slug) = LOWER(?) THEN 1
                        WHEN LOWER(ad) LIKE LOWER(?) THEN 2
                        WHEN LOWER(slug) LIKE LOWER(?) THEN 3
                        WHEN LOWER(aciklama) LIKE LOWER(?) THEN 4
                        ELSE 5
                    END",
                    [$q, $q, $q . '%', $q . '%', '%' . $q . '%']
                )
                ->orderBy('sira')
                ->take(8)
                ->get();

            $haberler = Haber::with('kategori')
                ->where('durum', 'yayinda')
                ->where(fn ($query) => $query
                    ->where('baslik', 'like', "%{$q}%")
                    ->orWhere('ozet', 'like', "%{$q}%"))
                ->orderByRaw('COALESCE(yayin_tarihi, created_at) DESC')
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

        $toplamSonuc = $bagisTurleri->count()
            + $haberler->count()
            + $etkinlikler->count()
            + $sayfalar->count();

        $populerAramalar = $arama_servisi->getirPopulerAramalar();

        return view('pages.arama', compact(
            'q', 'bagisTurleri', 'haberler', 'etkinlikler', 'sayfalar', 'toplamSonuc', 'populerAramalar'
        ));
    }
}
