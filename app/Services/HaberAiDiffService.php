<?php

namespace App\Services;

class HaberAiDiffService
{
    public function diffOzetiHazirla(array $orijinal, array $duzeltilmis): array
    {
        $icerikEski = $this->metniNormalizeEt($orijinal['icerik'] ?? '');
        $icerikYeni = $this->metniNormalizeEt($duzeltilmis['icerik'] ?? '');

        $eskiCumleler = $this->cumlelereAyir($icerikEski);
        $yeniCumleler = $this->cumlelereAyir($icerikYeni);

        $degisenCumleler = $this->degisenCumleSayisiniBul($eskiCumleler, $yeniCumleler);
        $kelimeFarki = $this->kelimeFarkiSayisiniBul($icerikEski, $icerikYeni);
        $noktalamaFarki = $this->noktalamaFarkiSayisiniBul((string) ($orijinal['icerik'] ?? ''), (string) ($duzeltilmis['icerik'] ?? ''));

        return [
            'degisen_cumle_sayisi' => $degisenCumleler,
            'degisen_kelime_sayisi' => $kelimeFarki,
            'noktalama_duzeltme_sayisi' => $noktalamaFarki,
        ];
    }

    public function satirBazliDiffHazirla(string $orijinalMetin, string $duzeltilmisMetin): array
    {
        $eskiSatirlar = preg_split('/\r\n|\r|\n/u', strip_tags($orijinalMetin), -1) ?: [];
        $yeniSatirlar = preg_split('/\r\n|\r|\n/u', strip_tags($duzeltilmisMetin), -1) ?: [];
        $maksimumSatir = max(count($eskiSatirlar), count($yeniSatirlar));
        $satirlar = [];

        for ($index = 0; $index < $maksimumSatir; $index++) {
            $eski = trim((string) ($eskiSatirlar[$index] ?? ''));
            $yeni = trim((string) ($yeniSatirlar[$index] ?? ''));

            $satirlar[] = [
                'eski' => $this->kelimeVurguluMetin($eski, $yeni, 'eski'),
                'yeni' => $this->kelimeVurguluMetin($eski, $yeni, 'yeni'),
                'degisti' => $eski !== $yeni,
            ];
        }

        return $satirlar;
    }

    public function farkOrnekleriHazirla(string $orijinalMetin, string $duzeltilmisMetin, int $limit = 12): array
    {
        $eskiTokenlar = $this->tokenlereAyir(strip_tags($orijinalMetin));
        $yeniTokenlar = $this->tokenlereAyir(strip_tags($duzeltilmisMetin));
        $maksimum = max(count($eskiTokenlar), count($yeniTokenlar));
        $ornekler = [];

        for ($index = 0; $index < $maksimum; $index++) {
            $eski = $eskiTokenlar[$index] ?? '';
            $yeni = $yeniTokenlar[$index] ?? '';

            if ($eski === $yeni) {
                continue;
            }

            $ornekler[] = [
                'eski' => $eski !== '' ? $eski : 'silindi',
                'yeni' => $yeni !== '' ? $yeni : 'eklendi',
            ];

            if (count($ornekler) >= $limit) {
                break;
            }
        }

        return $ornekler;
    }

    private function cumlelereAyir(string $metin): array
    {
        $cumleler = preg_split('/(?<=[.!?])\s+/u', trim($metin), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map('trim', $cumleler)));
    }

    private function degisenCumleSayisiniBul(array $eskiCumleler, array $yeniCumleler): int
    {
        $maksimum = max(count($eskiCumleler), count($yeniCumleler));
        $degisen = 0;

        for ($i = 0; $i < $maksimum; $i++) {
            $eski = $eskiCumleler[$i] ?? null;
            $yeni = $yeniCumleler[$i] ?? null;

            if ($eski !== $yeni) {
                $degisen++;
            }
        }

        return $degisen;
    }

    private function kelimeFarkiSayisiniBul(string $eskiMetin, string $yeniMetin): int
    {
        $eskiKelimeler = preg_split('/\s+/u', trim($eskiMetin), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $yeniKelimeler = preg_split('/\s+/u', trim($yeniMetin), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $maksimum = max(count($eskiKelimeler), count($yeniKelimeler));
        $degisen = 0;

        for ($i = 0; $i < $maksimum; $i++) {
            if (($eskiKelimeler[$i] ?? null) !== ($yeniKelimeler[$i] ?? null)) {
                $degisen++;
            }
        }

        return $degisen;
    }

    private function noktalamaFarkiSayisiniBul(string $eskiMetin, string $yeniMetin): int
    {
        preg_match_all('/[[:punct:]]/u', $eskiMetin, $eski);
        preg_match_all('/[[:punct:]]/u', $yeniMetin, $yeni);

        return abs(count($eski[0] ?? []) - count($yeni[0] ?? []));
    }

    private function metniNormalizeEt(string $metin): string
    {
        $metin = strip_tags($metin);
        $metin = html_entity_decode($metin, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $metin));
    }

    private function kelimeVurguluMetin(string $eskiMetin, string $yeniMetin, string $mod): string
    {
        $eskiTokenlar = $this->tokenlereAyir($eskiMetin);
        $yeniTokenlar = $this->tokenlereAyir($yeniMetin);

        if ($mod === 'eski' && empty($eskiTokenlar)) {
            return '—';
        }

        if ($mod === 'yeni' && empty($yeniTokenlar)) {
            return '—';
        }

        $hizalanmisTokenlar = $this->tokenDiffHizala($eskiTokenlar, $yeniTokenlar);

        return $this->vurguluMetinOlustur($hizalanmisTokenlar, $mod);
    }

    private function tokenDiffHizala(array $eskiTokenlar, array $yeniTokenlar): array
    {
        $eskiAdet = count($eskiTokenlar);
        $yeniAdet = count($yeniTokenlar);
        $lcs = array_fill(0, $eskiAdet + 1, array_fill(0, $yeniAdet + 1, 0));

        for ($i = $eskiAdet - 1; $i >= 0; $i--) {
            for ($j = $yeniAdet - 1; $j >= 0; $j--) {
                if ($eskiTokenlar[$i] === $yeniTokenlar[$j]) {
                    $lcs[$i][$j] = $lcs[$i + 1][$j + 1] + 1;

                    continue;
                }

                $lcs[$i][$j] = max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $i = 0;
        $j = 0;
        $sonuc = [];

        while ($i < $eskiAdet && $j < $yeniAdet) {
            if ($eskiTokenlar[$i] === $yeniTokenlar[$j]) {
                $sonuc[] = [
                    'tip' => 'ayni',
                    'deger' => $eskiTokenlar[$i],
                ];
                $i++;
                $j++;

                continue;
            }

            if ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $sonuc[] = [
                    'tip' => 'silinen',
                    'deger' => $eskiTokenlar[$i],
                ];
                $i++;

                continue;
            }

            $sonuc[] = [
                'tip' => 'eklenen',
                'deger' => $yeniTokenlar[$j],
            ];
            $j++;
        }

        while ($i < $eskiAdet) {
            $sonuc[] = [
                'tip' => 'silinen',
                'deger' => $eskiTokenlar[$i],
            ];
            $i++;
        }

        while ($j < $yeniAdet) {
            $sonuc[] = [
                'tip' => 'eklenen',
                'deger' => $yeniTokenlar[$j],
            ];
            $j++;
        }

        return $sonuc;
    }

    private function vurguluMetinOlustur(array $hizalanmisTokenlar, string $mod): string
    {
        $sonuc = [];

        foreach ($hizalanmisTokenlar as $token) {
            if ($token['tip'] === 'eklenen' && $mod === 'eski') {
                continue;
            }

            if ($token['tip'] === 'silinen' && $mod === 'yeni') {
                continue;
            }

            $guvenliKelime = e($token['deger']);

            if ($token['tip'] === 'ayni') {
                $sonuc[] = $guvenliKelime;

                continue;
            }

            $stil = $token['tip'] === 'silinen'
                ? 'background:#fecdd3;color:#881337;text-decoration:line-through;text-decoration-thickness:2px;border-radius:4px;padding:0 4px;'
                : 'background:#bbf7d0;color:#14532d;border-radius:4px;padding:0 4px;';

            $sonuc[] = '<span style="' . $stil . '">' . $guvenliKelime . '</span>';
        }

        return $this->tokenlariBirlestir($sonuc);
    }

    private function tokenlereAyir(string $metin): array
    {
        preg_match_all('/\[[^\]]*\]|\w+|[^\s\w]/u', $metin, $eslesmeler);

        return $eslesmeler[0] ?? [];
    }

    private function tokenlariBirlestir(array $tokenlar): string
    {
        $metin = implode(' ', $tokenlar);
        $metin = preg_replace('/\s+([,.;:!?\)\]])/u', '$1', $metin) ?? $metin;
        $metin = preg_replace('/([\(\[] )/u', '$1', $metin) ?? $metin;

        return $metin;
    }
}