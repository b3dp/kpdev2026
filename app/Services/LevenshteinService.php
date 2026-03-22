<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LevenshteinService
{
    public function benzerlikSkoru(string $a, string $b): int
    {
        $ilkMetin = $this->normalizeEt($a);
        $ikinciMetin = $this->normalizeEt($b);

        if ($ilkMetin === '' && $ikinciMetin === '') {
            return 100;
        }

        similar_text($ilkMetin, $ikinciMetin, $oran);

        return (int) round($oran);
    }

    public function benzerBul(string $aranan, Collection $liste, int $esik = 80): Collection
    {
        return $liste
            ->map(function ($kayit) use ($aranan) {
                $deger = $this->kayitMetni($kayit);
                $skor = $this->benzerlikSkoru($aranan, $deger);

                return [
                    'kayit' => $kayit,
                    'skor' => $skor,
                ];
            })
            ->filter(fn (array $sonuc) => $sonuc['skor'] >= $esik)
            ->sortByDesc('skor')
            ->values();
    }

    protected function normalizeEt(string $metin): string
    {
        $donusumler = [
            'ş' => 's',
            'Ş' => 's',
            'ğ' => 'g',
            'Ğ' => 'g',
            'ı' => 'i',
            'İ' => 'i',
            'ö' => 'o',
            'Ö' => 'o',
            'ü' => 'u',
            'Ü' => 'u',
            'ç' => 'c',
            'Ç' => 'c',
        ];

        return Str::of($metin)
            ->replace(array_keys($donusumler), array_values($donusumler))
            ->lower()
            ->squish()
            ->value();
    }

    protected function kayitMetni(mixed $kayit): string
    {
        if (is_string($kayit)) {
            return $kayit;
        }

        if (is_array($kayit)) {
            return (string) ($kayit['ad'] ?? $kayit['ad_soyad'] ?? $kayit['isim'] ?? '');
        }

        if (is_object($kayit)) {
            return (string) ($kayit->ad ?? $kayit->ad_soyad ?? $kayit->isim ?? '');
        }

        return '';
    }
}