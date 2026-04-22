<?php

namespace App\Http\Controllers;

use App\Models\BagisTuru;
use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\KurumsalSayfa;
use App\Support\KurumsalStatikSayfalar;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $sitemaps = [
            [
                'loc' => url('/sitemap-static.xml'),
                'lastmod' => now()->toAtomString(),
            ],
            [
                'loc' => url('/sitemap-haberler.xml'),
                'lastmod' => $this->atomize(Haber::query()->max('updated_at')),
            ],
            [
                'loc' => url('/sitemap-etkinlikler.xml'),
                'lastmod' => $this->atomize(Etkinlik::query()->max('updated_at')),
            ],
            [
                // Projede bagis detay sayfalari BagisTuru modelinden beslendigi icin lastmod buna gore alinır.
                'loc' => url('/sitemap-bagis.xml'),
                'lastmod' => $this->atomize(BagisTuru::query()->max('updated_at')),
            ],
            [
                'loc' => url('/sitemap-ekayit.xml'),
                'lastmod' => now()->toAtomString(),
            ],
            [
                'loc' => url('/sitemap-kurumsal.xml'),
                'lastmod' => $this->atomize(KurumsalSayfa::query()->max('updated_at')),
            ],
        ];

        return response()
            ->view('sitemap.index', compact('sitemaps'))
            ->header('Content-Type', 'application/xml');
    }

    public function static(): Response
    {
        $urls = Cache::remember('sitemap_static', now()->addHours(24), function () {
            $simdi = now()->toAtomString();

            return [
                ['loc' => route('home'), 'lastmod' => $simdi, 'changefreq' => 'daily', 'priority' => '1.0'],
                ['loc' => route('haberler.index'), 'lastmod' => $simdi, 'changefreq' => 'hourly', 'priority' => '0.9'],
                ['loc' => route('etkinlikler.index'), 'lastmod' => $simdi, 'changefreq' => 'daily', 'priority' => '0.8'],
                ['loc' => route('bagis.index'), 'lastmod' => $simdi, 'changefreq' => 'daily', 'priority' => '0.9'],
                ['loc' => route('kurumsal.show'), 'lastmod' => $simdi, 'changefreq' => 'weekly', 'priority' => '0.8'],
                ['loc' => route('kurumsal.show', ['slug' => 'kurumlar']), 'lastmod' => $simdi, 'changefreq' => 'weekly', 'priority' => '0.7'],
                ['loc' => route('kurumsal.show', ['slug' => 'atolyeler']), 'lastmod' => $simdi, 'changefreq' => 'weekly', 'priority' => '0.7'],
                ['loc' => route('iletisim.index'), 'lastmod' => $simdi, 'changefreq' => 'monthly', 'priority' => '0.6'],
                ['loc' => route('mezunlar.index'), 'lastmod' => $simdi, 'changefreq' => 'weekly', 'priority' => '0.6'],
            ];
        });

        return response()
            ->view('sitemap.static', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }

    public function haberler(): Response
    {
        $haberler = Cache::remember('sitemap_haberler', now()->addHours(6), function () {
            return Haber::query()
                ->where('durum', 'yayinda')
                ->whereNotNull('slug')
                ->select(['slug', 'updated_at'])
                ->orderByDesc('updated_at')
                ->get();
        });

        return response()
            ->view('sitemap.haberler', compact('haberler'))
            ->header('Content-Type', 'application/xml');
    }

    public function etkinlikler(): Response
    {
        $etkinlikler = Cache::remember('sitemap_etkinlikler', now()->addHours(12), function () {
            return Etkinlik::query()
                ->where('durum', 'yayinda')
                ->whereNotNull('slug')
                ->select(['slug', 'updated_at'])
                ->orderByDesc('updated_at')
                ->get();
        });

        return response()
            ->view('sitemap.etkinlikler', compact('etkinlikler'))
            ->header('Content-Type', 'application/xml');
    }

    public function bagis(): Response
    {
        $bagislar = Cache::remember('sitemap_bagis', now()->addHours(12), function () {
            // Projede bagis detay URL'leri bagis_turleri slug'lari ile olusturulur.
            return BagisTuru::query()
                ->where('aktif', true)
                ->whereNotNull('slug')
                ->select(['slug', 'updated_at'])
                ->orderByDesc('updated_at')
                ->get();
        });

        return response()
            ->view('sitemap.bagis', compact('bagislar'))
            ->header('Content-Type', 'application/xml');
    }

    public function ekayit(): Response
    {
        $urls = Cache::remember('sitemap_ekayit', now()->addHours(24), function () {
            return [[
                'loc' => route('ekayit.index'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ]];
        });

        return response()
            ->view('sitemap.ekayit', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }

    public function kurumsal(): Response
    {
        $sayfalar = Cache::remember('sitemap_kurumsal', now()->addHours(24), function () {
            $dinamikSayfalar = KurumsalSayfa::query()
                ->where('durum', 'yayinda')
                ->whereNotNull('slug')
                ->select(['slug', 'updated_at'])
                ->get()
                ->map(fn (KurumsalSayfa $sayfa) => [
                    'slug' => $sayfa->slug,
                    'updated_at' => $sayfa->updated_at,
                ])
                ->values()
                ->all();

            $statikSayfalar = collect(KurumsalStatikSayfalar::tumu())
                ->filter(fn (array $sayfa) => (bool) ($sayfa['aktif'] ?? false))
                ->map(fn (array $sayfa) => [
                    'slug' => (string) ($sayfa['slug'] ?? ''),
                    'updated_at' => now(),
                ])
                ->filter(fn (array $sayfa) => $sayfa['slug'] !== '')
                ->values()
                ->all();

            return collect(array_merge($dinamikSayfalar, $statikSayfalar))
                ->unique('slug')
                ->values();
        });

        return response()
            ->view('sitemap.kurumsal', compact('sayfalar'))
            ->header('Content-Type', 'application/xml');
    }

    private function atomize(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->toAtomString();
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value)->toAtomString();
        }

        return now()->toAtomString();
    }
}
