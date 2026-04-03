<?php

namespace App\Http\Controllers;

use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\HaberKategorisi;

class HaberController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function show(string $slug)
    {
        $haber = Haber::where('slug', $slug)
            ->where('durum', 'yayinda')
            ->firstOrFail();

        $ilgiliHaberler = Haber::where('durum', 'yayinda')
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
