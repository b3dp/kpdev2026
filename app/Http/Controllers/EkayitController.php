<?php

namespace App\Http\Controllers;

use App\Models\EkayitDonem;
use App\Models\EkayitSinif;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EkayitController extends Controller
{
    public function index()
    {
        $aktifDonem = EkayitDonem::where('aktif', 1)->first();

        $siniflar = $aktifDonem
            ? $this->getirDonemSiniflari((int) $aktifDonem->id)
            : collect();

        $gruplar = $this->hazirlaSinifGruplari($siniflar);

        return view('pages.ekayit.index', compact('aktifDonem', 'siniflar', 'gruplar'));
    }

    protected function getirDonemSiniflari(int $donemId): Collection
    {
        $sorgu = EkayitSinif::query()
            ->where('donem_id', $donemId)
            ->where('aktif', true);

        if (Schema::hasColumn('ekayit_siniflar', 'sira')) {
            $sorgu->orderBy('sira');
        } elseif (Schema::hasColumn('ekayit_siniflar', 'sinif_no')) {
            $sorgu->orderBy('sinif_no');
        } else {
            $sorgu->orderBy('ad');
        }

        return $sorgu->get();
    }

    protected function hazirlaSinifGruplari(Collection $siniflar): Collection
    {
        $turSirasi = [
            'ilkokul' => 'İlkokul',
            'ortaokul' => 'Ortaokul',
            'lise' => 'Lise',
            'universite' => 'Üniversite',
            'diger' => 'Sınıf Seçenekleri',
        ];

        $hazirSiniflar = $siniflar
            ->map(function (EkayitSinif $sinif) use ($turSirasi) {
                $grupKey = $this->sinifGrupAnahtari($sinif);
                preg_match('/\d+/', (string) ($sinif->ad ?? ''), $eslesme);

                $sinifNo = $sinif->sinif_no ?? ($eslesme[0] ?? null);

                return [
                    'id' => $sinif->id,
                    'kart_baslik' => $sinifNo ?: Str::upper(Str::substr((string) $sinif->ad, 0, 10)),
                    'kart_alt_baslik' => $sinifNo ? 'Sınıf' : 'Başvuru',
                    'kart_rozet' => $sinif->tur_etiket ?? ($turSirasi[$grupKey] ?? 'Sınıf'),
                    'kart_ad' => $sinif->ad,
                    'grup_key' => $grupKey,
                    'siralama' => $sinifNo ? (int) $sinifNo : PHP_INT_MAX,
                ];
            })
            ->groupBy('grup_key');

        return collect($turSirasi)
            ->filter(fn (string $ad, string $key) => $hazirSiniflar->has($key))
            ->map(fn (string $ad, string $key) => [
                'anahtar' => $key,
                'ad' => $ad,
                'siniflar' => collect($hazirSiniflar->get($key, []))->sortBy('siralama')->values(),
            ])
            ->values();
    }

    protected function sinifGrupAnahtari(EkayitSinif $sinif): string
    {
        if (filled($sinif->tur ?? null)) {
            return (string) $sinif->tur;
        }

        $ad = mb_strtolower((string) ($sinif->ad ?? ''), 'UTF-8');

        return match (true) {
            str_contains($ad, 'ilkokul') => 'ilkokul',
            str_contains($ad, 'ortaokul') => 'ortaokul',
            str_contains($ad, 'lise') => 'lise',
            str_contains($ad, 'üniversite'), str_contains($ad, 'universite') => 'universite',
            default => 'diger',
        };
    }

    public function form()
    {
        return view('welcome');
    }

    public function store(Request $request)
    {
        return redirect()->route('ekayit.tesekkur');
    }

    public function tesekkur()
    {
        return view('welcome');
    }
}
