<?php

namespace App\Services;

use GuzzleHttp\Client;
use Throwable;

class GeminiService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com',
            'timeout' => 30,
        ]);
    }

    public function imlaDuzelt(string $metin): string
    {
        return $this->metinCevabiAl(
            "Aşağıdaki metinde sadece yazım ve imla hatalarını düzelt. Anlamı, üslubu ve cümle yapısını değiştirme:\n\n" . $metin,
            $metin
        );
    }

    public function ozetUret(string $metin): string
    {
        $varsayilan = $this->metniAnlamliSinirla((string) strip_tags($metin), 280);

        $yanit = $this->metinCevabiAl(
            "Aşağıdaki metin için Türkçe özet üret. En fazla 280 karakter olsun, cümle yarım kalmasın. "
            . "Gereksiz giriş cümlesi yazma, doğrudan özeti ver:\n\n" . $metin,
            $varsayilan
        );

        return $this->metniAnlamliSinirla($yanit, 280);
    }

    public function metaDescriptionUret(string $metin): string
    {
        $varsayilan = $this->metniAnlamliSinirla((string) strip_tags($metin), 150);

        $yanit = $this->metinCevabiAl(
            "Aşağıdaki metin için Türkçe SEO uyumlu meta description üret. En fazla 150 karakter olsun, "
            . "cümle yarım kalmasın:\n\n" . $metin,
            $varsayilan
        );

        return $this->metniAnlamliSinirla($yanit, 150);
    }

    public function kisiTespitEt(string $metin): array
    {
        $json = $this->jsonCevabiAl(
            "Aşağıdaki metinden kişi adlarını JSON döndür. Sadece JSON üret.\n"
            . "Tercih edilen format: [{\"ad_soyad\":\"...\",\"rol\":\"...\"}]\n"
            . "Alternatif olarak {\"kisiler\":[...]} da kabul edilir. Sadece metinde geçen gerçek kişi adlarını yaz.\n\n"
            . $metin
        );

        $liste = $this->listeyiNormalizeEt($json, ['kisiler', 'people', 'sonuc', 'results']);
        if (! empty($liste)) {
            return $liste;
        }

        $satirYaniti = $this->metinCevabiAl(
            "Aşağıdaki metinden sadece kişi adlarını satır satır ver. "
            . "Format: Ad Soyad | Rol. Sadece metinde geçenleri yaz:\n\n" . $metin,
            ''
        );

        return $this->satirlardanVarlikListesiUret($satirYaniti, 'kisi');
    }

    public function kurumTespitEt(string $metin): array
    {
        $json = $this->jsonCevabiAl(
            "Aşağıdaki metinden kurum adlarını JSON döndür. Sadece JSON üret.\n"
            . "Tercih edilen format: [{\"ad\":\"...\"}]\n"
            . "Alternatif olarak {\"kurumlar\":[...]} da kabul edilir. Sadece metinde geçen kurumları yaz.\n\n"
            . $metin
        );

        $liste = $this->listeyiNormalizeEt($json, ['kurumlar', 'institutions', 'organizations', 'sonuc', 'results']);
        if (! empty($liste)) {
            return $liste;
        }

        $satirYaniti = $this->metinCevabiAl(
            "Aşağıdaki metinden sadece kurum adlarını satır satır ver. "
            . "Sadece metinde geçen kurumları yaz:\n\n" . $metin,
            ''
        );

        return $this->satirlardanVarlikListesiUret($satirYaniti, 'kurum');
    }

    private function metinCevabiAl(string $prompt, string $fallback): string
    {
        try {
            $metin = $this->apiIstegiYap($prompt);

            return filled($metin) ? trim($metin) : $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function jsonCevabiAl(string $prompt): array
    {
        try {
            $metin = $this->apiIstegiYap($prompt);
            if (! filled($metin)) {
                return [];
            }

            $dogrudan = $this->jsonDecodeEt($metin);
            if ($dogrudan !== null) {
                return $dogrudan;
            }

            $jsonParcasi = $this->metindenJsonParcasiAyikla($metin);
            if (! filled($jsonParcasi)) {
                return [];
            }

            $ayiklanan = $this->jsonDecodeEt($jsonParcasi);

            return $ayiklanan ?? [];
        } catch (Throwable) {
            return [];
        }
    }

    private function jsonDecodeEt(string $icerik): ?array
    {
        $temiz = trim($icerik);
        $temiz = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $temiz) ?? $temiz;

        $json = json_decode(trim($temiz), true);

        return is_array($json) ? $json : null;
    }

    private function metindenJsonParcasiAyikla(string $metin): ?string
    {
        if (preg_match('/\[[\s\S]*\]/', $metin, $eslesen)) {
            return $eslesen[0];
        }

        if (preg_match('/\{[\s\S]*\}/', $metin, $eslesen)) {
            return $eslesen[0];
        }

        return null;
    }

    private function listeyiNormalizeEt(array $json, array $olasiListeAnahtarlari): array
    {
        if (array_is_list($json)) {
            return $json;
        }

        foreach ($olasiListeAnahtarlari as $anahtar) {
            $deger = $json[$anahtar] ?? null;
            if (is_array($deger) && array_is_list($deger)) {
                return $deger;
            }
        }

        if (! empty($json) && isset($json[0]) && is_array($json[0])) {
            return $json;
        }

        return [];
    }

    private function apiIstegiYap(string $prompt): ?string
    {
        $apiKey = config('services.gemini.api_key');

        if (! filled($apiKey)) {
            return null;
        }

        $response = $this->http->post('/v1beta/models/gemini-1.5-flash:generateContent', [
            'query' => ['key' => $apiKey],
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    private function metniAnlamliSinirla(string $metin, int $maxKarakter): string
    {
        $temiz = trim(preg_replace('/\s+/u', ' ', strip_tags($metin)) ?? '');
        if ($temiz === '') {
            return '';
        }

        if (mb_strlen($temiz) <= $maxKarakter) {
            return $temiz;
        }

        $cumleler = preg_split('/(?<=[.!?…])\s+/u', $temiz, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $sonuc = '';
        foreach ($cumleler as $cumle) {
            $cumle = trim($cumle);
            if ($cumle === '') {
                continue;
            }

            $aday = $sonuc === '' ? $cumle : $sonuc . ' ' . $cumle;
            if (mb_strlen($aday) > $maxKarakter) {
                break;
            }
            $sonuc = $aday;
        }

        if ($sonuc !== '') {
            return $sonuc;
        }

        $kirpilmis = trim(mb_substr($temiz, 0, $maxKarakter));
        $sonBosluk = mb_strrpos($kirpilmis, ' ');
        if ($sonBosluk !== false && $sonBosluk > 0) {
            $kirpilmis = trim(mb_substr($kirpilmis, 0, $sonBosluk));
        }

        $kirpilmis = rtrim($kirpilmis, " \t\n\r\0\x0B,;:-");
        if ($kirpilmis === '') {
            return trim(mb_substr($temiz, 0, $maxKarakter));
        }

        if (! preg_match('/[.!?…]$/u', $kirpilmis)) {
            if (mb_strlen($kirpilmis) + 1 <= $maxKarakter) {
                $kirpilmis .= '.';
            }
        }

        return $kirpilmis;
    }

    private function satirlardanVarlikListesiUret(string $metin, string $tip): array
    {
        $satirlar = preg_split('/\R+/u', trim($metin), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $liste = [];

        foreach ($satirlar as $satir) {
            $satir = trim(preg_replace('/^[-*\d.)\s]+/u', '', $satir) ?? '');
            if ($satir === '' || mb_strlen($satir) < 2) {
                continue;
            }

            if ($tip === 'kisi') {
                $parcalar = preg_split('/\s*[|\-–]\s*/u', $satir);
                $adSoyad = trim((string) ($parcalar[0] ?? ''));
                if ($adSoyad === '') {
                    continue;
                }
                $liste[] = [
                    'ad_soyad' => $adSoyad,
                    'rol' => isset($parcalar[1]) ? trim((string) $parcalar[1]) : null,
                ];
                continue;
            }

            $liste[] = ['ad' => $satir];
        }

        return $liste;
    }
}
