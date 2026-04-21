<?php

namespace App\Http\Controllers;

use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\HaberKategorisi;

class HaberController extends Controller
{
    public function index()
    {
        $kategoriSlug  = request('kategori');
        $arama        = trim((string) request('q', ''));
        $kisiId       = (int) request('kisi_id', 0);
        $kurumSlug    = request('kurum', '');

        $kategoriler = HaberKategorisi::where('aktif', 1)
            ->orderBy('sira')
            ->get();

        $listeQuery = Haber::with(['kategori', 'kategoriler'])
            ->where('durum', 'yayinda')
            ->when($kategoriSlug, function ($query) use ($kategoriSlug) {
                $query->where(function ($altQuery) use ($kategoriSlug) {
                    $altQuery
                        ->whereHas('kategori', function ($kategoriQuery) use ($kategoriSlug) {
                            $kategoriQuery->where('slug', $kategoriSlug);
                        })
                        ->orWhereHas('kategoriler', function ($kategoriQuery) use ($kategoriSlug) {
                            $kategoriQuery->where('slug', $kategoriSlug);
                        });
                });
            })
            ->when($arama !== '', function ($query) use ($arama) {
                $query->where(function ($altQuery) use ($arama) {
                    $altQuery->where('baslik', 'like', "%{$arama}%")
                        ->orWhere('ozet', 'like', "%{$arama}%");
                });
            })
            ->when($kisiId > 0, fn ($q) => $q->whereHas('kisiler', fn ($k) => $k->where('kisiler.id', $kisiId)))
            ->when($kurumSlug !== '', fn ($q) => $q->whereHas('kurumlar', fn ($k) => $k->where('kurumlar.slug', $kurumSlug)));

        $oneCikanHaber = (clone $listeQuery)
            ->where('manset', true)
            ->orderByRaw('COALESCE(yayin_tarihi, created_at) DESC')
            ->first();

        if (! $oneCikanHaber) {
            $oneCikanHaber = (clone $listeQuery)
                ->orderByRaw('COALESCE(yayin_tarihi, created_at) DESC')
                ->first();
        }

        $haberler = (clone $listeQuery)
            ->when($oneCikanHaber, fn ($query) => $query->where('id', '!=', $oneCikanHaber->id))
            ->orderByRaw('COALESCE(yayin_tarihi, created_at) DESC')
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
        $haber = Haber::with(['yonetici', 'kategori', 'kategoriler', 'etiketler', 'gorseller', 'kisiler', 'kurumlar'])
            ->where('slug', $slug)
            ->where('durum', 'yayinda')
            ->firstOrFail();

        $haber->increment('goruntuleme');
        $haber->refresh();

        $kategoriIdleri = $haber->kategoriler->pluck('id')->push($haber->kategori_id)->filter()->unique()->values();

        $ilgiliHaberler = Haber::with(['kategori', 'kategoriler'])
            ->where('durum', 'yayinda')
            ->where('id', '!=', $haber->id)
            ->when($kategoriIdleri->isNotEmpty(), function ($query) use ($kategoriIdleri) {
                $query->where(function ($altQuery) use ($kategoriIdleri) {
                    $altQuery
                        ->whereIn('kategori_id', $kategoriIdleri->all())
                        ->orWhereHas('kategoriler', fn ($kategoriQuery) => $kategoriQuery->whereIn('haber_kategorileri.id', $kategoriIdleri->all()));
                });
            })
            ->orderByRaw('COALESCE(yayin_tarihi, created_at) DESC')
            ->take(3)
            ->get();

        $sonHaberler = Haber::where('durum', 'yayinda')
            ->orderByRaw('COALESCE(yayin_tarihi, created_at) DESC')
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
