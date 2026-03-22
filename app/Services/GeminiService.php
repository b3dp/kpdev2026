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
        $varsayilan = mb_substr(trim(strip_tags($metin)), 0, 300);

        return $this->metinCevabiAl(
            "Aşağıdaki metin için Türkçe, 250-300 karakter arasında kısa bir özet üret:\n\n" . $metin,
            $varsayilan
        );
    }

    public function metaDescriptionUret(string $metin): string
    {
        $varsayilan = mb_substr(trim(strip_tags($metin)), 0, 160);

        return $this->metinCevabiAl(
            "Aşağıdaki metin için Türkçe, SEO uyumlu 150-160 karakter meta description üret:\n\n" . $metin,
            $varsayilan
        );
    }

    public function kisiTespitEt(string $metin): array
    {
        $json = $this->jsonCevabiAl(
            "Aşağıdaki metinden kişi adlarını JSON döndür. Sadece JSON üret.\n"
            . "Tercih edilen format: [{\"ad_soyad\":\"...\",\"rol\":\"...\"}]\n"
            . "Alternatif olarak {\"kisiler\":[...]} da kabul edilir.\n\n"
            . $metin
        );

        return $this->listeyiNormalizeEt($json, ['kisiler', 'people', 'sonuc', 'results']);
    }

    public function kurumTespitEt(string $metin): array
    {
        $json = $this->jsonCevabiAl(
            "Aşağıdaki metinden kurum adlarını JSON döndür. Sadece JSON üret.\n"
            . "Tercih edilen format: [{\"ad\":\"...\"}]\n"
            . "Alternatif olarak {\"kurumlar\":[...]} da kabul edilir.\n\n"
            . $metin
        );

        return $this->listeyiNormalizeEt($json, ['kurumlar', 'institutions', 'organizations', 'sonuc', 'results']);
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
}
