<?php

namespace App\Services;

use App\Models\Bagis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeasurementProtocolService
{
    private string $apiSecret;

    private string $measurementId;

    public function __construct()
    {
        $this->apiSecret = (string) config('services.ga4.api_secret', '');
        $this->measurementId = (string) config('services.ga4.measurement_id', '');
    }

    public function purchaseGonder(Bagis $bagis): bool
    {
        $bagis->loadMissing('kalemler.bagisTuru', 'sepet');

        $items = $bagis->kalemler->map(function ($kalem): array {
            return [
                'item_id' => (string) ($kalem->bagisTuru?->slug ?? $kalem->bagis_turu_id),
                'item_name' => (string) ($kalem->bagisTuru?->ad ?? 'Bağış'),
                'price' => (float) $kalem->birim_fiyat,
                'quantity' => (int) $kalem->adet,
            ];
        })->values()->all();

        return $this->eventGonder((string) ($bagis->uye_id ?? $bagis->sepet?->session_id ?? 'misafir'), 'purchase', [
            'transaction_id' => $bagis->bagis_no,
            'value' => (float) $bagis->toplam_tutar,
            'currency' => 'TRY',
            'items' => $items,
        ]);
    }

    public function signUpGonder(string $clientId): bool
    {
        return $this->eventGonder($clientId, 'sign_up', []);
    }

    public function loginGonder(string $clientId): bool
    {
        return $this->eventGonder($clientId, 'login', []);
    }

    private function eventGonder(string $clientId, string $eventName, array $params): bool
    {
        if ($this->measurementId === '' || $this->apiSecret === '') {
            Log::warning('GA4 ayarlari eksik, event gonderilemedi.', ['event' => $eventName]);

            return false;
        }

        try {
            $url = 'https://www.google-analytics.com/mp/collect?measurement_id='
                .urlencode($this->measurementId)
                .'&api_secret='
                .urlencode($this->apiSecret);

            $yanit = Http::timeout(10)->asJson()->post($url, [
                'client_id' => $clientId,
                'events' => [[
                    'name' => $eventName,
                    'params' => $params,
                ]],
            ]);

            if ($yanit->successful()) {
                return true;
            }

            Log::warning('GA4 event gonderimi basarisiz.', [
                'event' => $eventName,
                'status' => $yanit->status(),
                'body' => $yanit->body(),
            ]);

            return false;
        } catch (\Throwable $exception) {
            Log::warning('GA4 event gonderimi hata verdi.', [
                'event' => $eventName,
                'hata' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
