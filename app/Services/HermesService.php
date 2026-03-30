<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

class HermesService
{
    private const BASE_URL = 'https://live.iletisimmakinesi.com/api';

    private const TOKEN_CACHE_KEY = 'hermes_token';

    public function authenticate(): string
    {
        $cachedToken = Cache::get(self::TOKEN_CACHE_KEY);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $xml = $this->postIstegi('/UserGatewayWS/functions/authenticate', [
            'userName' => (string) config('services.iletisim_makinesi.username'),
            'userPass' => (string) config('services.iletisim_makinesi.password'),
            'customerCode' => (string) config('services.iletisim_makinesi.customer_code'),
            'apiKey' => (string) config('services.iletisim_makinesi.api_key'),
            'vendorCode' => (string) config('services.iletisim_makinesi.vendor_code'),
        ]);

        $cozulmus = $this->xmlParse($xml);

        if (! $cozulmus['basarili']) {
            Log::error('[HermesService] authenticate hatası', [
                'kod' => $cozulmus['kod'],
                'aciklama' => $cozulmus['aciklama'],
            ]);

            throw new RuntimeException('Hermes token alınamadı.');
        }

        $token = $this->xmlDegeriBul($cozulmus['icerik'], ['TOKEN_NO', 'TOKEN']);

        if ($token === null || $token === '') {
            throw new RuntimeException('Hermes token response içinde TOKEN_NO bulunamadı.');
        }

        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addMinutes(55));

        return $token;
    }

    public function sendSMS(array $telefonlar, string $mesaj, ?string $sendDate = null): array
    {
        $normalizeTelefonlar = $this->telefonListesiNormalize($telefonlar);

        $params = [
            'token' => $this->authenticate(),
            'phoneNumbers' => json_encode($normalizeTelefonlar, JSON_UNESCAPED_UNICODE),
            'templateText' => $mesaj,
            'originatorId' => (string) config('services.iletisim_makinesi.originator_id'),
            'isUTF8Allowed' => 'false',
            'isNLSSAllowed' => 'true',
            'validityPeriod' => (string) config('services.iletisim_makinesi.validity_period'),
            'isRepeatingDestinationAllowed' => 'false',
            'forceIYS' => 'false',
            'iysTransactionType' => 'bilgilendirme',
            'usingIvtboxOptoutSMS' => 'false',
        ];

        if ($sendDate !== null && $sendDate !== '') {
            $params['sendDate'] = $sendDate;
        }

        $xml = $this->postIstegi('/SMSGatewayWS/functions/sendSMS', $params);
        $cozulmus = $this->xmlParse($xml);

        $transactionId = $this->xmlIntDegeriBul($cozulmus['icerik'], [
            'TRANSACTION',
            'TRANSACTION_ID',
            'MAIN_TRANSACTION_ID',
        ]);
        if ($transactionId === null) {
            $transactionId = $this->xmlAttributeIntDegeriBul(
                $cozulmus['icerik'],
                ['TRANSACTION', 'MAIN_TRANSACTION'],
                ['id', 'ID']
            );
        }
        if ($transactionId === null) {
            $transactionList = $this->xmlDegeriBul($cozulmus['icerik'], ['TRANSACTIONS', 'TRANSACTION_IDS']);
            if (is_string($transactionList) && $transactionList !== '') {
                $ilkId = trim(explode(',', $transactionList)[0] ?? '');
                if (is_numeric($ilkId)) {
                    $transactionId = (int) $ilkId;
                }
            }
        }
        $gecerli = $this->xmlIntDegeriBul($cozulmus['icerik'], ['VALID_SMS_COUNT']) ?? 0;
        $gecersiz = $this->xmlIntDegeriBul($cozulmus['icerik'], ['INVALID_SMS_COUNT']) ?? 0;

        if ($cozulmus['basarili']) {
            Log::info('[HermesService] sendSMS başarılı', [
                'telefon_sayisi' => count($normalizeTelefonlar),
                'transaction_id' => $transactionId,
            ]);
        } else {
            Log::error('[HermesService] sendSMS hatası', [
                'kod' => $cozulmus['kod'],
                'aciklama' => $cozulmus['aciklama'],
                'telefon_sayisi' => count($normalizeTelefonlar),
            ]);
        }

        return [
            'basarili' => $cozulmus['basarili'],
            'transaction_id' => $transactionId,
            'gecerli' => $gecerli,
            'gecersiz' => $gecersiz,
        ];
    }

    public function setAsyncTransaction(array $telefonlar, string $mesaj, ?string $sendDate = null): array
    {
        $normalizeTelefonlar = $this->telefonListesiNormalize($telefonlar);

        $params = [
            'token' => $this->authenticate(),
            'phoneNumbers' => json_encode($normalizeTelefonlar, JSON_UNESCAPED_UNICODE),
            'templateText' => $mesaj,
            'originatorId' => (string) config('services.iletisim_makinesi.originator_id'),
            'isUTF8Allowed' => 'false',
            'isNLSSAllowed' => 'true',
            'validityPeriod' => (string) config('services.iletisim_makinesi.validity_period'),
            'isRepeatingDestinationAllowed' => 'false',
            'forceIYS' => 'false',
            'iysTransactionType' => 'bilgilendirme',
            'usingIvtboxOptoutSMS' => 'false',
        ];

        if ($sendDate !== null && $sendDate !== '') {
            $params['sendDate'] = $sendDate;
        }

        $xml = $this->postIstegi('/SMSGatewayWS/functions/SetAsyncTransaction', $params);
        $cozulmus = $this->xmlParse($xml);

        if ($cozulmus['kod'] === 43) {
            return $this->sendSMS($normalizeTelefonlar, $mesaj, $sendDate);
        }

        $reqLogId = $this->xmlIntDegeriBul($cozulmus['icerik'], ['REQ_LOG_ID', 'REQLOGID', 'REQ_LOGID']);
        if ($reqLogId === null) {
            $reqLogId = $this->xmlAttributeIntDegeriBul(
                $cozulmus['icerik'],
                ['REQ_LOG', 'REQLOG', 'ASYNC_TRANSACTION'],
                ['id', 'ID']
            );
        }
        $transactionId = $this->xmlIntDegeriBul($cozulmus['icerik'], [
            'TRANSACTION',
            'TRANSACTION_ID',
            'MAIN_TRANSACTION_ID',
        ]);
        if ($transactionId === null) {
            $transactionId = $this->xmlAttributeIntDegeriBul(
                $cozulmus['icerik'],
                ['TRANSACTION', 'MAIN_TRANSACTION'],
                ['id', 'ID']
            );
        }

        if ($cozulmus['basarili']) {
            Log::info('[HermesService] setAsyncTransaction', [
                'async' => true,
                'req_log_id' => $reqLogId,
            ]);
        } else {
            Log::error('[HermesService] setAsyncTransaction hatası', [
                'kod' => $cozulmus['kod'],
                'aciklama' => $cozulmus['aciklama'],
            ]);
        }

        return [
            'basarili' => $cozulmus['basarili'],
            'async' => $cozulmus['basarili'],
            'req_log_id' => $reqLogId,
            'transaction_id' => $transactionId,
        ];
    }

    public function confirmAsyncTransaction(int $reqLogId): bool
    {
        $xml = $this->postIstegi('/SMSGatewayWS/functions/confirmAsyncTransaction', [
            'token' => $this->authenticate(),
            'reqLogId' => $reqLogId,
        ]);

        $cozulmus = $this->xmlParse($xml);

        if ($cozulmus['basarili']) {
            Log::info('[HermesService] confirmAsyncTransaction', [
                'req_log_id' => $reqLogId,
            ]);

            return true;
        }

        Log::error('[HermesService] confirmAsyncTransaction hatası', [
            'kod' => $cozulmus['kod'],
            'aciklama' => $cozulmus['aciklama'],
        ]);

        return false;
    }

    public function calculateCost(array $telefonlar, string $mesaj): array
    {
        $normalizeTelefonlar = $this->telefonListesiNormalize($telefonlar);

        $xml = $this->postIstegi('/SMSGatewayWS/functions/calculateCost', [
            'token' => $this->authenticate(),
            'templateText' => $mesaj,
            'originatorId' => (string) config('services.iletisim_makinesi.originator_id'),
            'validityPeriod' => (string) config('services.iletisim_makinesi.validity_period'),
            'isNLSSAllowed' => 'true',
            'isUTF8Allowed' => 'false',
            'phoneNumbers' => json_encode($normalizeTelefonlar, JSON_UNESCAPED_UNICODE),
            'forceIYS' => 'false',
        ]);

        $cozulmus = $this->xmlParse($xml);

        if (! $cozulmus['basarili']) {
            Log::error('[HermesService] calculateCost hatası', [
                'kod' => $cozulmus['kod'],
                'aciklama' => $cozulmus['aciklama'],
            ]);

            return [
                'toplam_mesaj' => 0,
                'toplam_paket' => 0,
            ];
        }

        return [
            'toplam_mesaj' => $this->xmlIntDegeriBul($cozulmus['icerik'], ['TOTAL_MESSAGE_COUNT']) ?? 0,
            'toplam_paket' => $this->xmlIntDegeriBul($cozulmus['icerik'], ['TOTAL_PACKET_COUNT']) ?? 0,
        ];
    }

    public function getTransactionDetails(int $transactionId): array
    {
        $xml = $this->postIstegi('/SMSGatewayWS/functions/getTransactionDetails', [
            'token' => $this->authenticate(),
            'transactionId' => $transactionId,
            'outputColumns' => json_encode([
                'DATE',
                'PACKET_ID',
                'PACKET_DESTINATION',
                'PACKET_SENDDATE',
                'PACKET_REPORT_RECEIVE_DATE',
                'PACKET_CLIENT_ID',
                'PACKET_STATUS_ID',
                'PACKET_STATUS_CODE',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $cozulmus = $this->xmlParse($xml);

        if (! $cozulmus['basarili']) {
            return [];
        }

        $csv = $this->xmlDegeriBul($cozulmus['icerik'], ['TRANSACTION_CSV']) ?? '';

        return $this->csvCiftiCoz($csv);
    }

    public function getTransactionSummaries(string $baslangic, string $bitis): array
    {
        $xml = $this->postIstegi('/SMSGatewayWS/functions/getTransactionSummariesWithinDates', [
            'token' => $this->authenticate(),
            'beginDate' => $baslangic,
            'endDate' => $bitis,
            'outputColumns' => json_encode([
                'TRANSACTION_ID',
                'TRANSACTION_DATE',
                'SEND_DATE',
                'WAITING',
                'SUCCESSFUL',
                'FAILED',
                'CONTENT',
                'TRANSACTION_STATUS',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $cozulmus = $this->xmlParse($xml);

        if (! $cozulmus['basarili']) {
            return [];
        }

        $csv = $this->xmlDegeriBul($cozulmus['icerik'], ['TRANSACTIONS_CSV']) ?? '';

        return $this->csvCiftiCoz($csv);
    }

    public function retryFailed(int $transactionId): array
    {
        $xml = $this->postIstegi('/SMSGatewayWS/functions/sendToAllFailedPacketsOfTransaction', [
            'token' => $this->authenticate(),
            'transactionId' => $transactionId,
        ]);

        $cozulmus = $this->xmlParse($xml);

        return [
            'basarili' => $cozulmus['basarili'],
            'yeni_transaction_id' => $this->xmlIntDegeriBul($cozulmus['icerik'], ['TRANSACTION', 'TRANSACTION_ID'])
                ?? $this->xmlAttributeIntDegeriBul($cozulmus['icerik'], ['TRANSACTION'], ['id', 'ID']),
        ];
    }

    public function cancelScheduled(int $transactionId): bool
    {
        $xml = $this->postIstegi('/SMSGatewayWS/functions/abortScheduledTransaction', [
            'token' => $this->authenticate(),
            'transactionId' => $transactionId,
        ]);

        $cozulmus = $this->xmlParse($xml);

        return $cozulmus['basarili'];
    }

    public function akilliGonder(array $telefonlar, string $mesaj, ?string $sendDate = null): array
    {
        $normalizeTelefonlar = $this->telefonListesiNormalize($telefonlar);
        $asyncLimit = (int) config('services.iletisim_makinesi.async_limit', 500);

        if (count($normalizeTelefonlar) <= $asyncLimit) {
            return $this->sendSMS($normalizeTelefonlar, $mesaj, $sendDate);
        }

        $sonuc = $this->setAsyncTransaction($normalizeTelefonlar, $mesaj, $sendDate);

        if (($sonuc['async'] ?? false) === true && ! empty($sonuc['req_log_id'])) {
            $this->confirmAsyncTransaction((int) $sonuc['req_log_id']);
        }

        return $sonuc;
    }

    public function akillıGonder(array $telefonlar, string $mesaj, ?string $sendDate = null): array
    {
        return $this->akilliGonder($telefonlar, $mesaj, $sendDate);
    }

    public function checkUserAccount(): string
    {
        return $this->postIstegi('/UserGatewayWS/functions/checkUserAccount', [
            'token' => $this->authenticate(),
        ]);
    }

    public function getActiveCreditTransfers(int $serviceId = 1): string
    {
        return $this->postIstegi('/UserGatewayWS/functions/getActiveCreditTransfers', [
            'token' => $this->authenticate(),
            'serviceId' => $serviceId,
        ]);
    }

    private function postIstegi(string $endpoint, array $params): string
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->retry(2, 1000)
                ->post(self::BASE_URL.$endpoint, $params);
        } catch (Throwable $exception) {
            Log::error('[HermesService] HTTP istek hatası', [
                'endpoint' => $endpoint,
                'hata' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Hermes HTTP isteği başarısız oldu.', 0, $exception);
        }

        if (! $response->successful()) {
            Log::error('[HermesService] HTTP cevap hatası', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Hermes HTTP cevabı başarısız döndü.');
        }

        return $response->body();
    }

    private function xmlParse(string $xml): array
    {
        libxml_use_internal_errors(true);
        $icerik = simplexml_load_string($xml);

        if (! $icerik instanceof SimpleXMLElement) {
            $hata = libxml_get_last_error();

            throw new RuntimeException('Hermes XML parse hatası: '.($hata?->message ?? 'Bilinmeyen hata'));
        }

        $kod = $this->xmlIntDegeriBul($icerik, ['CODE']) ?? -1;
        $isim = $this->xmlDegeriBul($icerik, ['NAME']) ?? '';
        $aciklama = $this->xmlDegeriBul($icerik, ['DESCRIPTION', 'DESC', 'DETAILS']) ?? '';

        return [
            'basarili' => $kod === 0,
            'kod' => $kod,
            'isim' => $isim,
            'aciklama' => $aciklama,
            'icerik' => $icerik,
        ];
    }

    private function telefonNormalize(string $telefon): string
    {
        $temiz = preg_replace('/\D+/', '', $telefon) ?? '';

        if (str_starts_with($temiz, '90')) {
            $temiz = substr($temiz, 2);
        }

        if (str_starts_with($temiz, '0')) {
            $temiz = substr($temiz, 1);
        }

        return $temiz;
    }

    private function telefonListesiNormalize(array $telefonlar): array
    {
        $normalize = [];

        foreach ($telefonlar as $telefon) {
            $temiz = $this->telefonNormalize((string) $telefon);
            if ($temiz !== '') {
                $normalize[] = $temiz;
            }
        }

        return array_values(array_unique($normalize));
    }

    private function xmlDegeriBul(?SimpleXMLElement $xml, array $alanlar): ?string
    {
        if (! $xml instanceof SimpleXMLElement) {
            return null;
        }

        foreach ($alanlar as $alan) {
            $sonuc = $xml->xpath('//'.$alan);
            if (is_array($sonuc) && isset($sonuc[0])) {
                return trim((string) $sonuc[0]);
            }
        }

        return null;
    }

    private function xmlIntDegeriBul(?SimpleXMLElement $xml, array $alanlar): ?int
    {
        $deger = $this->xmlDegeriBul($xml, $alanlar);

        if ($deger === null || $deger === '' || ! is_numeric($deger)) {
            return null;
        }

        return (int) $deger;
    }

    private function xmlAttributeIntDegeriBul(?SimpleXMLElement $xml, array $elementler, array $attributeAdlari): ?int
    {
        if (! $xml instanceof SimpleXMLElement) {
            return null;
        }

        foreach ($elementler as $element) {
            foreach ($attributeAdlari as $attribute) {
                $sonuc = $xml->xpath('//'.$element.'/@'.$attribute);
                if (is_array($sonuc) && isset($sonuc[0])) {
                    $deger = trim((string) $sonuc[0]);
                    if ($deger !== '' && is_numeric($deger)) {
                        return (int) $deger;
                    }
                }
            }
        }

        return null;
    }

    private function csvCiftiCoz(string $csv): array
    {
        if (trim($csv) === '') {
            return [];
        }

        $satirlar = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];
        $sonuc = [];

        foreach ($satirlar as $satir) {
            $satir = trim($satir);
            if ($satir === '') {
                continue;
            }

            $parcalar = explode('";="', $satir);
            $kayit = [];

            for ($i = 0; $i < count($parcalar); $i += 2) {
                $anahtar = trim($parcalar[$i], '" ');
                $deger = trim($parcalar[$i + 1] ?? '', '" ');

                if ($anahtar !== '') {
                    $kayit[$anahtar] = $deger;
                }
            }

            if ($kayit !== []) {
                $sonuc[] = $kayit;
            }
        }

        return $sonuc;
    }
}
