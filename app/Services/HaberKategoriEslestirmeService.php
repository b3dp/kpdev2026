<?php

namespace App\Services;

use App\Models\Haber;
use App\Models\HaberKategorisi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class HaberKategoriEslestirmeService
{
    public function __construct(
        protected GeminiService $geminiService,
    ) {}

    public function haberIcinKategorileriBelirle(Haber $haber): array
    {
        try {
            $kategoriler = HaberKategorisi::query()
                ->where('aktif', true)
                ->orderBy('sira')
                ->orderBy('ad')
                ->get(['id', 'ad', 'slug', 'aciklama']);

            if ($kategoriler->isEmpty()) {
                return [];
            }

            $icerik = trim(strip_tags((string) $haber->icerik));
            $icerik = Str::limit(preg_replace('/\s+/u', ' ', $icerik) ?: $icerik, 6000, '');

            $aiSonuclari = collect($this->geminiService->haberKategorileriniEslestir(
                baslik: (string) $haber->baslik,
                ozet: $haber->ozet,
                icerik: $icerik,
                kategoriler: $kategoriler
                    ->map(fn (HaberKategorisi $kategori) => [
                        'id' => $kategori->id,
                        'ad' => $kategori->ad,
                        'slug' => $kategori->slug,
                        'aciklama' => strip_tags((string) $kategori->aciklama),
                    ])
                    ->all(),
            ));

            $eslesenler = $this->aiSonuclariniKategoriKayitlarinaDonustur($aiSonuclari, $kategoriler);

            if ($eslesenler->isEmpty()) {
                $eslesenler = $this->kuralliKategoriSkoruUret($haber, $kategoriler);
            }

            return $eslesenler
                ->sortByDesc('skor')
                ->values()
                ->all();
        } catch (Throwable $exception) {
            Log::error('HaberKategoriEslestirmeService@haberIcinKategorileriBelirle hata', [
                'haber_id' => $haber->id,
                'mesaj' => $exception->getMessage(),
                'satir' => $exception->getLine(),
            ]);

            return [];
        }
    }

    public function haberIcinKategorileriKaydet(Haber $haber, array $sonuclar, string $kaynak = 'ai'): array
    {
        try {
            $kayitlar = collect($sonuclar)
                ->filter(fn (array $sonuc) => isset($sonuc['id']))
                ->unique('id')
                ->values();

            if ($kayitlar->isEmpty() && $haber->kategori_id) {
                $mevcutKategori = HaberKategorisi::query()->find($haber->kategori_id);

                if ($mevcutKategori) {
                    $kayitlar = collect([[
                        'id' => $mevcutKategori->id,
                        'ad' => $mevcutKategori->ad,
                        'slug' => $mevcutKategori->slug,
                        'skor' => 100,
                        'neden' => 'Mevcut ana kategori korundu.',
                    ]]);
                }
            }

            if ($kayitlar->isEmpty()) {
                return [];
            }

            $anaKategori = $kayitlar->first();
            $haber->update(['kategori_id' => $anaKategori['id']]);

            DB::table('haber_kategori_eslestirmeleri')
                ->where('haber_id', $haber->id)
                ->delete();

            $simdi = now();
            $eklenecekler = $kayitlar
                ->values()
                ->map(function (array $kayit, int $index) use ($haber, $kaynak, $simdi): array {
                    return [
                        'haber_id' => $haber->id,
                        'haber_kategorisi_id' => $kayit['id'],
                        'skor' => max(0, min(100, (int) ($kayit['skor'] ?? 0))),
                        'ana_kategori_mi' => $index === 0,
                        'kaynak' => $kaynak,
                        'created_at' => $simdi,
                        'updated_at' => $simdi,
                        'deleted_at' => null,
                    ];
                })
                ->all();

            DB::table('haber_kategori_eslestirmeleri')->insert($eklenecekler);

            return $kayitlar->all();
        } catch (Throwable $exception) {
            Log::error('HaberKategoriEslestirmeService@haberIcinKategorileriKaydet hata', [
                'haber_id' => $haber->id,
                'mesaj' => $exception->getMessage(),
                'satir' => $exception->getLine(),
            ]);

            return [];
        }
    }

    private function aiSonuclariniKategoriKayitlarinaDonustur(Collection $aiSonuclari, Collection $kategoriler): Collection
    {
        return $aiSonuclari
            ->map(function (array $sonuc) use ($kategoriler): ?array {
                $kategori = $kategoriler->firstWhere('slug', $sonuc['slug']);

                if (! $kategori) {
                    return null;
                }

                return [
                    'id' => $kategori->id,
                    'ad' => $kategori->ad,
                    'slug' => $kategori->slug,
                    'skor' => (int) ($sonuc['skor'] ?? 0),
                    'neden' => $sonuc['neden'] ?? '',
                ];
            })
            ->filter(fn (?array $kategori) => $kategori !== null && $kategori['skor'] >= 60)
            ->unique('id')
            ->values();
    }

    private function kuralliKategoriSkoruUret(Haber $haber, Collection $kategoriler): Collection
    {
        $metin = $this->metniNormalizeEt(implode(' ', [
            (string) $haber->baslik,
            (string) ($haber->ozet ?? ''),
            (string) strip_tags((string) $haber->icerik),
        ]));

        return $kategoriler
            ->map(function (HaberKategorisi $kategori) use ($metin): ?array {
                $kategoriMetni = $this->metniNormalizeEt(implode(' ', [
                    $kategori->ad,
                    $kategori->slug,
                    strip_tags((string) $kategori->aciklama),
                ]));

                $skor = $this->kategoriKelimeSkoruHesapla($metin, $kategoriMetni, $kategori);

                if ($skor < 55) {
                    return null;
                }

                return [
                    'id' => $kategori->id,
                    'ad' => $kategori->ad,
                    'slug' => $kategori->slug,
                    'skor' => $skor,
                    'neden' => 'Kural tabanlı eşleşme',
                ];
            })
            ->filter()
            ->sortByDesc('skor')
            ->take(3)
            ->values();
    }

    private function kategoriKelimeSkoruHesapla(string $haberMetni, string $kategoriMetni, HaberKategorisi $kategori): int
    {
        $kategoriKelimeleri = collect(preg_split('/\s+/u', $kategoriMetni, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->filter(fn (string $kelime) => mb_strlen($kelime) >= 4)
            ->unique()
            ->values();

        if ($kategoriKelimeleri->isEmpty()) {
            return 0;
        }

        $eslesenKelimeSayisi = $kategoriKelimeleri
            ->filter(fn (string $kelime) => str_contains($haberMetni, $kelime))
            ->count();

        $temelSkor = (int) round(($eslesenKelimeSayisi / max(1, $kategoriKelimeleri->count())) * 100);
        $adSkoru = str_contains($haberMetni, $this->metniNormalizeEt($kategori->ad)) ? 20 : 0;
        $slugSkoru = str_contains($haberMetni, $this->metniNormalizeEt(str_replace('-', ' ', $kategori->slug))) ? 10 : 0;

        return min(100, $temelSkor + $adSkoru + $slugSkoru);
    }

    private function metniNormalizeEt(string $metin): string
    {
        return (string) Str::of($metin)
            ->replace(['ş', 'Ş', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ü', 'Ü', 'ç', 'Ç'], ['s', 's', 'g', 'g', 'i', 'i', 'o', 'o', 'u', 'u', 'c', 'c'])
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]+/u', ' ')
            ->squish();
    }
}