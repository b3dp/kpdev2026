<?php

namespace App\Http\Controllers;

use App\Models\BagisTuru;
use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\HaberKategorisi;
use App\Settings\AnaSayfaAyarlari;

class HomeController extends Controller
{
    public function index()
    {
        $anaSayfaAyarlari = app(AnaSayfaAyarlari::class);

        $mansetHaberler = Haber::with('kategori')
            ->where('durum', 'yayinda')
            ->where('manset', 1)
            ->latest('yayin_tarihi')
            ->take(3)
            ->get();

        $sonHaberler = Haber::with('kategori')
            ->where('durum', 'yayinda')
            ->latest('yayin_tarihi')
            ->take(6)
            ->get();

        $kategoriler = HaberKategorisi::where('aktif', 1)
            ->orderBy('sira')
            ->get();

        $yaklasanEtkinlikler = Etkinlik::where('durum', 'yayinda')
            ->where('baslangic_tarihi', '>=', now())
            ->orderBy('baslangic_tarihi')
            ->take(3)
            ->get();

        $bagisturleri = BagisTuru::orderBy('sira')->get();

        return view('pages.index', compact(
            'anaSayfaAyarlari',
            'mansetHaberler',
            'sonHaberler',
            'kategoriler',
            'yaklasanEtkinlikler',
            'bagisturleri'
        ));
    }
}
