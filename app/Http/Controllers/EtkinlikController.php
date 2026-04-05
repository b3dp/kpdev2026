<?php

namespace App\Http\Controllers;

use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\HaberKategorisi;

class EtkinlikController extends Controller
{
    public function index()
    {
        $filtre = request('filtre', 'tumu');

        $etkinlikler = Etkinlik::where('durum', 'yayinda')
            ->when($filtre === 'bu-ay', fn ($q) => $q
                ->whereMonth('baslangic_tarihi', now()->month)
                ->whereYear('baslangic_tarihi', now()->year))
            ->when($filtre === 'gelecek', fn ($q) => $q
                ->where('baslangic_tarihi', '>=', now()))
            ->when($filtre === 'gecmis', fn ($q) => $q
                ->where('baslangic_tarihi', '<', now()))
            ->orderBy('baslangic_tarihi')
            ->paginate(12);

        return view('pages.etkinlikler.index', compact('etkinlikler', 'filtre'));
    }

    public function show(string $slug)
    {
        $etkinlik = Etkinlik::where('slug', $slug)
            ->where('durum', 'yayinda')
            ->with('gorseller')
            ->firstOrFail();

        $sonHaberler = Haber::where('durum', 'yayinda')
            ->latest('yayin_tarihi')
            ->take(3)
            ->get();

        $kategoriler = HaberKategorisi::where('aktif', 1)
            ->orderBy('sira')
            ->get();

        $yaklasanEtkinlikler = Etkinlik::where('durum', 'yayinda')
            ->where('baslangic_tarihi', '>=', now())
            ->where('id', '!=', $etkinlik->id)
            ->orderBy('baslangic_tarihi')
            ->take(2)
            ->get();

        return view('pages.etkinlikler.detay', compact(
            'etkinlik',
            'sonHaberler',
            'kategoriler',
            'yaklasanEtkinlikler'
        ));
    }
}
