<?php

namespace App\Jobs;

use App\Models\Haber;
use App\Models\Kisi;
use App\Models\Kurum;
use App\Services\GeminiService;
use App\Services\HaberAiRevizyonService;
use App\Services\HaberKategoriEslestirmeService;
use App\Services\LevenshteinService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class AiHaberIsleJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public int $haberId)
    {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function handle(
        GeminiService $geminiService,
        HaberAiRevizyonService $haberAiRevizyonService,
        LevenshteinService $levenshteinService,
        HaberKategoriEslestirmeService $haberKategoriEslestirmeService,
    ): void
    {
        $haber = Haber::query()->find($this->haberId);

        if (! $haber || blank($haber->icerik)) {
            return;
        }

        $duzeltilmisMetin = $geminiService->imlaDuzelt((string) $haber->icerik);
        $ozet = filled($haber->ozet) ? $haber->ozet : $geminiService->ozetUret($duzeltilmisMetin);
        $metaDescription = filled($haber->meta_description) ? $haber->meta_description : $geminiService->metaDescriptionUret($duzeltilmisMetin);
        $seoBaslik = filled($haber->getRawOriginal('seo_baslik'))
            ? (string) $haber->getRawOriginal('seo_baslik')
            : $geminiService->seoBaslikUret((string) $haber->baslik);

        $duzeltilmisVeri = [
            'icerik' => $duzeltilmisMetin,
            'ozet' => $ozet,
            'meta_description' => $metaDescription,
            'seo_baslik' => $seoBaslik,
            'ai_islendi' => true,
        ];

        $haberAiRevizyonService->revizyonOlustur($haber, $duzeltilmisVeri, 'ai_imla_duzeltme', false);

        $haber->update([
            'ai_islendi' => true,
            'ai_onay' => false,
        ]);

        \Log::debug('AI_KISI_TESPIT_METIN', [
            'haber_id' => $haber->id,
            'metin_ilk_500' => mb_substr($duzeltilmisMetin, 0, 500),
        ]);
        $kisiSonuclar = $geminiService->kisiTespitEt($duzeltilmisMetin);
        $mevcutKisiler = Kisi::query()->select(['id', 'ad', 'soyad'])->get();
        $kisiAramaListesi = $mevcutKisiler
            ->map(fn (Kisi $kisi) => ['id' => $kisi->id, 'ad' => trim($kisi->ad . ' ' . $kisi->soyad)])
            ->values();

        foreach ($kisiSonuclar as $kisiVerisi) {
            $adSoyad = trim((string) ($kisiVerisi['ad_soyad'] ?? ''));
            if (! $this->kisiAdayiGecerliMi($adSoyad)) {
                continue;
            }

            $parcalar = preg_split('/\s+/', $adSoyad);
            $ad = $parcalar[0] ?? null;
            $soyad = count($parcalar) > 1 ? implode(' ', array_slice($parcalar, 1)) : null;

            if (! filled($ad) || ! filled($soyad)) {
                continue;
            }

            $eslesme = $this->mevcutKisiyiEsle($adSoyad, $mevcutKisiler, $kisiAramaListesi, $levenshteinService);

            if (! $eslesme) {
                continue;
            }

            $kisi = $eslesme['kisi'];

            DB::table('haber_kisiler')->updateOrInsert(
                ['haber_id' => $haber->id, 'kisi_id' => $kisi->id],
                [
                    'rol' => $kisiVerisi['gorev'] ?? $kisiVerisi['rol'] ?? null,
                    'onay_durumu' => $eslesme['onay_durumu'],
                    'updated_at' => now(),
                    'created_at' => now(),
                    'deleted_at' => null,
                ]
            );
        }

        $kurumSonuclar = $geminiService->kurumTespitEt($duzeltilmisMetin);
        $mevcutKurumlar = Kurum::query()->select(['id', 'ad'])->get();
        foreach ($kurumSonuclar as $kurumVerisi) {
            $ad = trim((string) ($kurumVerisi['ad'] ?? ''));

            if (! filled($ad)) {
                continue;
            }

            $eslesme = $this->mevcutKurumuEsle($ad, $mevcutKurumlar, $levenshteinService);

            if (! $eslesme) {
                continue;
            }

            $kurum = $eslesme['kurum'];

            DB::table('haber_kurumlar')->updateOrInsert(
                ['haber_id' => $haber->id, 'kurum_id' => $kurum->id],
                [
                    'onay_durumu' => $eslesme['onay_durumu'],
                    'updated_at' => now(),
                    'created_at' => now(),
                    'deleted_at' => null,
                ]
            );
        }

        $kategoriSonuclari = $haberKategoriEslestirmeService->haberIcinKategorileriBelirle($haber);
        $haberKategoriEslestirmeService->haberIcinKategorileriKaydet($haber, $kategoriSonuclari);
    }

    public function failed(Throwable $exception): void
    {
        $haber = Haber::query()->find($this->haberId);
        if ($haber) {
            $haber->update(['ai_islendi' => false]);
        }

        activity('ai_haber_isleme_hata')
            ->withProperties([
                'haber_id' => $this->haberId,
                'hata' => $exception->getMessage(),
            ])
            ->log('AI haber işleme job başarısız oldu');
    }

    private function kisiAdayiGecerliMi(string $adSoyad): bool
    {
        $adSoyad = trim($adSoyad);

        if (! filled($adSoyad) || mb_substr_count($adSoyad, ' ') < 1) {
            return false;
        }

        if (mb_strlen($adSoyad) < 5 || mb_strlen($adSoyad) > 80 || preg_match('/\d/u', $adSoyad)) {
            return false;
        }

        $kelimeler = preg_split('/\s+/u', $adSoyad, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($kelimeler) < 2 || count($kelimeler) > 4) {
            return false;
        }

        foreach ($kelimeler as $kelime) {
            if (mb_strlen($kelime) < 2 || ! preg_match('/^[A-ZÇĞİÖŞÜ][a-zçğıöşü]+$/u', $kelime)) {
                return false;
            }
        }

        $yasakliIfadeler = [
            'merkez', 'kursu', 'öğreticisi', 'yarışması', 'yarışmasında', 'rekabet', 'tilavetleri',
            'buluşması', 'anadolu', 'imam', 'hatip', 'ortaokulları', 'lisesi', 'uluslararası',
            'borsa', 'kurra', 'okuma', 'genç', 'programı', 'töreni', 'etkinliği', 'haber',
        ];

        $kucuk = mb_strtolower($adSoyad);
        foreach ($yasakliIfadeler as $ifade) {
            if (str_contains($kucuk, $ifade)) {
                return false;
            }
        }

        return true;
    }

    private function mevcutKisiyiEsle(string $adSoyad, $mevcutKisiler, $kisiAramaListesi, LevenshteinService $levenshteinService): ?array
    {
        $normalize = $this->metinNormalizeEt($adSoyad);

        $kisi = $mevcutKisiler->first(function (Kisi $kayit) use ($normalize) {
            return $this->metinNormalizeEt(trim($kayit->ad . ' ' . $kayit->soyad)) === $normalize;
        });

        if ($kisi) {
            return ['kisi' => $kisi, 'onay_durumu' => 'onaylandi'];
        }

        $benzerKisiler = $levenshteinService->benzerBul($adSoyad, $kisiAramaListesi, 92);
        $enBenzer = $benzerKisiler->first();

        if (! $enBenzer) {
            return null;
        }

        $kisi = $mevcutKisiler->firstWhere('id', $enBenzer['kayit']['id']);

        if (! $kisi) {
            return null;
        }

        return ['kisi' => $kisi, 'onay_durumu' => $enBenzer['skor'] >= 97 ? 'onaylandi' : 'beklemede'];
    }

    private function metinNormalizeEt(string $metin): string
    {
        return (string) str($metin)
            ->replace(['ş', 'Ş', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ü', 'Ü', 'ç', 'Ç'], ['s', 's', 'g', 'g', 'i', 'i', 'o', 'o', 'u', 'u', 'c', 'c'])
            ->lower()
            ->squish();
    }

    private function mevcutKurumuEsle(string $ad, $mevcutKurumlar, LevenshteinService $levenshteinService): ?array
    {
        $normalize = $this->metinNormalizeEt($ad);

        $kurum = $mevcutKurumlar->first(function (Kurum $kayit) use ($normalize) {
            return $this->metinNormalizeEt($kayit->ad) === $normalize;
        });

        if ($kurum) {
            return ['kurum' => $kurum, 'onay_durumu' => 'onaylandi'];
        }

        $kurumAramaListesi = $mevcutKurumlar
            ->map(fn (Kurum $kayit) => ['id' => $kayit->id, 'ad' => $kayit->ad])
            ->values();

        $benzerKurumlar = $levenshteinService->benzerBul($ad, $kurumAramaListesi, 90);
        $enBenzer = $benzerKurumlar->first();

        if (! $enBenzer) {
            return null;
        }

        $kurum = $mevcutKurumlar->firstWhere('id', $enBenzer['kayit']['id']);

        if (! $kurum) {
            return null;
        }

        return [
            'kurum' => $kurum,
            'onay_durumu' => $enBenzer['skor'] >= 96 ? 'onaylandi' : 'beklemede',
        ];
    }
}
