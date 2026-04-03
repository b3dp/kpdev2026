<?php

namespace App\Http\Controllers;

use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\HaberKategorisi;

class HaberController extends Controller
{
    public function index()
    {
        $kategoriSlug = request('kategori');
        $arama = trim((string) request('q', ''));

        $kategoriler = HaberKategorisi::where('aktif', 1)
            ->orderBy('sira')
            ->get();

        $listeQuery = Haber::with('kategori')
            ->where('durum', 'yayinda')
            ->when($kategoriSlug, function ($query) use ($kategoriSlug) {
                $query->whereHas('kategori', function ($kategoriQuery) use ($kategoriSlug) {
                    $kategoriQuery->where('slug', $kategoriSlug);
                });
            })
            ->when($arama !== '', function ($query) use ($arama) {
                $query->where(function ($altQuery) use ($arama) {
                    $altQuery->where('baslik', 'like', "%{$arama}%")
                        ->orWhere('ozet', 'like', "%{$arama}%");
                });
            });

        $oneCikanHaber = (clone $listeQuery)
            ->where('manset', true)
            ->latest('yayin_tarihi')
            ->first();

        if (! $oneCikanHaber) {
            $oneCikanHaber = (clone $listeQuery)
                ->latest('yayin_tarihi')
                ->first();
        }

        $haberler = (clone $listeQuery)
            ->when($oneCikanHaber, fn ($query) => $query->where('id', '!=', $oneCikanHaber->id))
            ->latest('yayin_tarihi')
            ->paginate(9)
            ->withQueryString();

        return view('pages.haberler.index', compact(
            'kategoriler',
            'kategoriSlug',
            'arama',
            'oneCikanHaber',
            'haberler'
        ));
    }

    public function show(string $slug)
    {
        $haber = Haber::with(['kategori', 'etiketler', 'gorseller'])
            ->where('slug', $slug)
            ->where('durum', 'yayinda')
            ->firstOrFail();

        $haber->increment('goruntuleme');
        $haber->refresh();

        $ilgiliHaberler = Haber::with('kategori')
            ->where('durum', 'yayinda')
            ->where('id', '!=', $haber->id)
            ->latest('yayin_tarihi')
            ->take(3)
            ->get();

        $sonHaberler = Haber::where('durum', 'yayinda')
            ->latest('yayin_tarihi')
            ->take(3)
            ->get();

        $kategoriler = HaberKategorisi::where('aktif', 1)
            ->orderBy('sira')
            ->get();

        $yaklasanEtkinlikler = Etkinlik::where('durum', 'yayinda')
            ->where('baslangic_tarihi', '>=', now())
            ->orderBy('baslangic_tarihi')
            ->take(2)
            ->get();

        return view('pages.haberler.detay', compact(
            'haber',
            'ilgiliHaberler',
            'sonHaberler',
            'kategoriler',
            'yaklasanEtkinlikler'
        ));
    }
}
