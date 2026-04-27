<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AlbarakaService
{
    private string $merchantNo;
    private string $terminalNo;
    private string $eposNo;
    private string $encKey;
    private string $jsonApiUrl;
    private string $threeDUrl;
    private string $returnUrl;
    private int $timeoutSn;
    private bool $verifySsl;

    public function __construct()
    {
        $this->merchantNo  = (string) config('services.albaraka.merchant_no');
        $this->terminalNo  = (string) config('services.albaraka.terminal_no');
        $this->eposNo      = (string) config('services.albaraka.epos_no');
        $this->encKey      = (string) config('services.albaraka.enc_key');
        $this->jsonApiUrl  = rtrim((string) config('services.albaraka.json_api_url'), '/');
        $this->threeDUrl   = (string) config('services.albaraka.3d_url');
        $this->returnUrl   = (string) config('services.albaraka.return_url');
        $this->timeoutSn   = (int) config('services.albaraka.timeout_sn', 30);
        $this->verifySsl   = (bool) config('services.albaraka.verify_ssl', true);
    }

    /**
     * 3D Secure doğrulama HTML formu üretir.
     * UseOOS=0 ise kart alanları merchant formundan gönderilir.
     *
     * @param  string $orderId   Bağış no (bagis_no); Albaraka için 20 karaktere pad edilir
     * @param  int    $tutarKurus Tutar kuruş olarak (1 TL = 100)
     * @param  array  $kartBilgileri kart_no, kart_sahibi, son_kullanma_ay, son_kullanma_yil, cvv
     * @return string            Otomatik submit eden HTML
     */
    public function ucBoyutluFormOlustur(string $orderId, int $tutarKurus, array $kartBilgileri = []): string
    {
        try {
            // Albaraka OrderId tam olarak 20 karakter olmalı; sol-sıfır pad uygula
            $albarakaOrderId = $this->albarakaOrderId($orderId);
            $useOos = (int) config('services.albaraka.use_oos', 1) === 1;
            $transactionType = (string) config('services.albaraka.txn_type', 'Sale');
            $installmentCount = (string) config('services.albaraka.installment_count', 0);
            $merchantReturnUrl = $this->returnUrl;
            $language = (string) config('services.albaraka.language', 'TR');
            $currencyCode = (string) config('services.albaraka.currency_code', 'TL');
            $openNewWindow = (string) config('services.albaraka.open_new_window', 0);
            $useOosValue = $useOos ? '1' : '0';
            $txnState = (string) config('services.albaraka.txn_state', 'INITIAL');
            $useJokerVadaa = (string) config('services.albaraka.use_joker_vadaa', 0);

            $cardNo = '';
            $cvv = '';
            $expiredDate = '';
            $cardHolderName = '';

            if (! $useOos) {
                $cardNo = preg_replace('/\D+/', '', (string) ($kartBilgileri['kart_no'] ?? '')) ?: '';
                $cvv = preg_replace('/\D+/', '', (string) ($kartBilgileri['cvv'] ?? '')) ?: '';
                $ay = str_pad(substr((string) ($kartBilgileri['son_kullanma_ay'] ?? ''), 0, 2), 2, '0', STR_PAD_LEFT);
                $yil = substr(preg_replace('/\D+/', '', (string) ($kartBilgileri['son_kullanma_yil'] ?? '')) ?: '', -2);
                // Dokümana göre son kullanma formatı YYAA (örn: 2001)
                $expiredDate = $yil.$ay;
                $cardHolderName = trim((string) ($kartBilgileri['kart_sahibi'] ?? ''));
            }

            // MacNew, bankaya post edilen gizli alanların sırasıyla ';' ile birleştirilmesiyle hesaplanır.
            $macNewPayload = [
                $this->eposNo,
                $this->merchantNo,
                $this->terminalNo,
                $albarakaOrderId,
                $transactionType,
                $cardNo,
                $expiredDate,
                $cvv,
                $cardHolderName,
                (string) $tutarKurus,
                $installmentCount,
                $merchantReturnUrl,
                $language,
                $currencyCode,
                $useJokerVadaa,
                $openNewWindow,
                $useOosValue,
                $txnState,
            ];
            $macNew = $this->formMacNewHesapla($macNewPayload);

            Log::channel('odeme')->info('Albaraka 3D form MAC hazırlandı.', [
                'orderId' => $albarakaOrderId,
                'useOos' => $useOos,
                'amount' => $tutarKurus,
                'card_len' => strlen($cardNo),
                'expire_len' => strlen($expiredDate),
                'mac_new_len' => strlen($macNew),
                'payload_fields' => [
                    'PosnetID', 'MerchantNo', 'TerminalNo', 'OrderId', 'TransactionType',
                    'CardNo', 'ExpiredDate', 'Cvv', 'CardHolderName', 'Amount',
                    'InstallmentCount', 'MerchantReturnURL', 'Language', 'CurrencyCode',
                    'UseJokerVadaa', 'OpenNewWindow', 'UseOOS', 'TxnState',
                ],
            ]);

            $alan = fn (string $name, string $value): string =>
                '<input type="hidden" name="'.htmlspecialchars($name, ENT_QUOTES).'" value="'.htmlspecialchars($value, ENT_QUOTES).'" />';

            $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
            $html .= '<form id="albaraka3dForm" method="post" action="'.htmlspecialchars($this->threeDUrl, ENT_QUOTES).'">';
            $html .= $alan('PosnetID',          $this->eposNo);
            $html .= $alan('MerchantNo',        $this->merchantNo);
            $html .= $alan('TerminalNo',        $this->terminalNo);
            $html .= $alan('OrderId',           $albarakaOrderId);
            $html .= $alan('TransactionType',   $transactionType);
            $html .= $alan('CardNo',            $cardNo);
            $html .= $alan('ExpiredDate',       $expiredDate);
            $html .= $alan('Cvv',               $cvv);
            $html .= $alan('CardHolderName',    $cardHolderName);
            $html .= $alan('Amount',            (string) $tutarKurus);
            $html .= $alan('InstallmentCount',  $installmentCount);
            $html .= $alan('MerchantReturnURL', $merchantReturnUrl);
            $html .= $alan('Language',          $language);
            $html .= $alan('CurrencyCode',      $currencyCode);
            $html .= $alan('MacNew',            $macNew);
            $html .= $alan('UseJokerVadaa',     $useJokerVadaa);
            $html .= $alan('OpenNewWindow',     $openNewWindow);
            $html .= $alan('UseOOS',            $useOosValue);
            $html .= $alan('TxnState',          $txnState);
            $html .= '</form>';
            $html .= '<script>document.getElementById("albaraka3dForm").submit();</script>';
            $html .= '</body></html>';

            return $html;
        } catch (Throwable $e) {
            Log::channel('odeme')->error('Albaraka 3D form oluşturulamadı.', [
                'hata'     => $e->getMessage(),
                'orderId'  => $orderId,
                'tutar'    => $tutarKurus,
            ]);
            throw $e;
        }
    }

    /**
     * Bankadan gelen callback verisinin MAC doğrulamasını yapar.
     * MdStatus=1 (Full 3D) şartı da burada kontrol edilir.
     *
     * @param  array $data  $_POST içeriği
     * @return bool
     */
    public function callbackDogrula(array $data): bool
    {
        try {
            $mdStatus = $this->callbackDegeriAl($data, ['MdStatus', 'MDSTATUS', 'mdstatus']);

            if ($mdStatus !== '1') {
                return false;
            }

            $secureTransactionId = $this->callbackDegeriAl($data, ['SecureTransactionId', 'secureTransactionId', 'SECURETRANSACTIONID']);
            $cavv = $this->callbackDegeriAl($data, ['CAVV', 'CavvData', 'Cavv', 'cavvData', 'cavv']);
            $eci = $this->callbackDegeriAl($data, ['ECI', 'Eci', 'eci']);
            $gelenMac = $this->callbackDegeriAl($data, ['Mac', 'MAC', 'mac']);
            $gelenMacNew = $this->callbackDegeriAl($data, ['MacNew', 'MACNEW', 'macnew', 'Macnew']);
            $orderId = $this->callbackDegeriAl($data, ['OrderId', 'ORDERID', 'orderid']);
            $md = $this->callbackDegeriAl($data, ['MD', 'Md', 'md']);

            $mac = $this->callbackMacHesapla($secureTransactionId, $cavv, $eci, $mdStatus);
            $macMdFallback = $md !== ''
                ? $this->callbackMacHesapla($secureTransactionId, $md, $eci, $mdStatus)
                : '';

            $macNew = $this->callbackMacNewHesapla($secureTransactionId, $cavv, $eci, $mdStatus, $orderId);
            $macNewMdFallback = $md !== ''
                ? $this->callbackMacNewHesapla($secureTransactionId, $md, $eci, $mdStatus, $orderId)
                : '';

            if ($gelenMacNew !== '') {
                if (hash_equals($macNew, $gelenMacNew) || ($macNewMdFallback !== '' && hash_equals($macNewMdFallback, $gelenMacNew))) {
                    return true;
                }
            }

            if ($gelenMac !== '' && hash_equals($mac, $gelenMac)) {
                return true;
            }

            if ($gelenMac !== '' && $macMdFallback !== '' && hash_equals($macMdFallback, $gelenMac)) {
                return true;
            }

            Log::channel('odeme')->warning('Albaraka callback MAC eşleşmedi.', [
                'secureTransactionId_var' => $secureTransactionId !== '',
                'cavv_var' => $cavv !== '',
                'eci_var' => $eci !== '',
                'md_var' => $md !== '',
                'orderId_var' => $orderId !== '',
                'gelen_mac_var' => $gelenMac !== '',
                'gelen_mac_new_var' => $gelenMacNew !== '',
                'mdStatus' => $mdStatus,
            ]);

            return false;
        } catch (Throwable $e) {
            Log::channel('odeme')->error('Albaraka callback MAC doğrulama hatası.', ['hata' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 3D doğrulaması başarılıysa bankaya Sale çağrısı atar.
     *
     * @param  array  $callbackData  Bankadan dönen POST verisi
     * @param  string $orderId       Orijinal OrderId
     * @param  int    $tutarKurus    Kuruş cinsinden tutar
     * @return array  ['basarili' => bool, 'referans' => string|null, 'hata_kodu' => string|null, 'hata_mesaji' => string|null]
     */
    public function satisYap(array $callbackData, string $orderId, int $tutarKurus): array
    {
        try {
            $secureTransactionId = $this->callbackDegeriAl($callbackData, ['SecureTransactionId', 'secureTransactionId', 'SECURETRANSACTIONID']);
            $cavv = $this->callbackDegeriAl($callbackData, ['CAVV', 'CavvData', 'Cavv', 'cavvData', 'cavv']);
            $eci = $this->callbackDegeriAl($callbackData, ['ECI', 'Eci', 'eci']);
            $mdStatus = $this->callbackDegeriAl($callbackData, ['MdStatus', 'MDSTATUS', 'mdstatus']);
            $md = $this->callbackDegeriAl($callbackData, ['MD', 'Md', 'md']);

            if ($cavv === '' && $md !== '') {
                $cavv = $md;
            }

            $mac = $this->callbackMacHesapla($secureTransactionId, $cavv, $eci, $mdStatus);
            $macNew = $this->callbackMacNewHesapla(
                $secureTransactionId,
                $cavv,
                $eci,
                $mdStatus,
                $this->albarakaOrderId($orderId)
            );

            $params = [
                'ApiType'                => 'JSON',
                'ApiVersion'             => 'V100',
                'MerchantNo'             => $this->merchantNo,
                'TerminalNo'             => $this->terminalNo,
                'PaymentInstrumentType'  => 'CARD',
                'IsEncrypted'            => 'N',
                'IsTDSecureMerchant'     => 'Y',
                'IsMailOrder'            => 'N',
                'ThreeDSecureData'       => [
                    'SecureTransactionId' => $secureTransactionId,
                    'CavvData'            => $cavv,
                    'Eci'                 => $eci,
                    'MdStatus'            => $mdStatus,
                    'MD'                  => $md,
                ],
                'MAC'                    => $mac,
                'MACParams'              => 'MerchantNo:TerminalNo:SecureTransactionId:CavvData:Eci:MdStatus',
                'MACNew'                 => $macNew,
                'MACNewParams'           => 'MerchantNo:TerminalNo:SecureTransactionId:CavvData:Eci:MdStatus:OrderId',
                'Amount'                 => $tutarKurus,
                'CurrencyCode'           => config('services.albaraka.currency_code', 'TL'),
                'PointAmount'            => 0,
                'OrderId'                => $this->albarakaOrderId($orderId),
                'InstallmentCount'       => config('services.albaraka.installment_count', 0),
            ];

            $response = Http::withHeaders([
                'Content-Type'      => 'application/json',
                'X-MERCHANT-ID'     => $this->merchantNo,
                'X-TERMINAL-ID'     => $this->terminalNo,
                'X-POSNET-ID'       => $this->eposNo,
                'X-CORRELATION-ID'  => $orderId,
            ])
                ->timeout($this->timeoutSn)
                ->withOptions(['verify' => $this->verifySsl])
                ->post($this->jsonApiUrl.'/Sale', $params);

            $veri = $response->json();

            $responseCode = (string) ($veri['ServiceResponseData']['ResponseCode'] ?? 'UNKNOWN');
            $responseDesc = (string) ($veri['ServiceResponseData']['ResponseDescription'] ?? 'Bilinmeyen hata');
            $approvedCode = (string) ($veri['ServiceResponseData']['ApprovedCode'] ?? '');

            if ($responseCode === '0000') {
                return [
                    'basarili'     => true,
                    'referans'     => $approvedCode ?: $orderId,
                    'hata_kodu'    => null,
                    'hata_mesaji'  => null,
                ];
            }

            Log::channel('odeme')->warning('Albaraka Sale başarısız.', [
                'orderId'       => $orderId,
                'responseCode'  => $responseCode,
                'responseDesc'  => $responseDesc,
            ]);

            return [
                'basarili'     => false,
                'referans'     => null,
                'hata_kodu'    => $responseCode,
                'hata_mesaji'  => $responseDesc,
            ];
        } catch (Throwable $e) {
            Log::channel('odeme')->error('Albaraka Sale çağrısı başarısız.', [
                'hata'     => $e->getMessage(),
                'orderId'  => $orderId,
                'tutar'    => $tutarKurus,
            ]);

            return [
                'basarili'     => false,
                'referans'     => null,
                'hata_kodu'    => 'EXCEPTION',
                'hata_mesaji'  => $e->getMessage(),
            ];
        }
    }

    /**
     * Albaraka için 20 karakterlik OrderId üretir (sol-sıfır pad).
     * bagis_no hiçbir zaman '0' ile başlamaz, bu yüzden ltrim ile geri dönüşüm güvenlidir.
     */
    public function albarakaOrderId(string $bagisNo): string
    {
        return str_pad($bagisNo, 20, '0', STR_PAD_LEFT);
    }

    /**
     * Albaraka'dan dönen padli OrderId'den orijinal bagis_no'yu çıkarır.
     */
    public function bagisNoCoz(string $albarakaOrderId): string
    {
        return ltrim($albarakaOrderId, '0') ?: $albarakaOrderId;
    }

    /**
     * Yeni düzenleme: MAC hesaplamasına OrderId dahil edilir (MacNew).
     */
    private function formMacNewHesapla(array $payloadValues): string
    {
        $str = implode(';', $payloadValues);
        return base64_encode(hash_hmac('sha256', $str, $this->hmacAnahtari(), true));
    }

    /**
     * Callback ve Sale için MAC hesaplama
     */
    private function callbackMacHesapla(
        string $secureTransactionId,
        string $cavv,
        string $eci,
        string $mdStatus
    ): string {
        $str = $this->merchantNo.$this->terminalNo.$secureTransactionId.$cavv.$eci.$mdStatus;
        return base64_encode(hash('sha256', $str.$this->encKey, true));
    }

    /**
     * Yeni düzenleme: callback/sale MAC hesaplamasına OrderId dahil edilir (MacNew).
     */
    private function callbackMacNewHesapla(
        string $secureTransactionId,
        string $cavv,
        string $eci,
        string $mdStatus,
        string $orderId
    ): string {
        $str = $this->merchantNo.$this->terminalNo.$secureTransactionId.$cavv.$eci.$mdStatus.$orderId;
        return base64_encode(hash('sha256', $str.$this->encKey, true));
    }

    /**
     * Callback alan adları banka tarafında farklı casing ile gelebilir.
     */
    private function callbackDegeriAl(array $data, array $anahtarlar): string
    {
        foreach ($anahtarlar as $anahtar) {
            if (array_key_exists($anahtar, $data)) {
                return trim((string) $data[$anahtar]);
            }
        }

        $lowerMap = [];
        foreach ($data as $anahtar => $deger) {
            $lowerMap[strtolower((string) $anahtar)] = $deger;
        }

        foreach ($anahtarlar as $anahtar) {
            $lower = strtolower($anahtar);
            if (array_key_exists($lower, $lowerMap)) {
                return trim((string) $lowerMap[$lower]);
            }
        }

        return '';
    }

    /**
     * ENCKEY bazı kurulumlarda "10,10,10,..." byte listesi olarak gelebilir.
     */
    private function hmacAnahtari(): string
    {
        $hamAnahtar = trim($this->encKey);

        if ($hamAnahtar !== '' && preg_match('/^\d+(\s*,\s*\d+)+$/', $hamAnahtar) === 1) {
            $binary = '';

            foreach (explode(',', $hamAnahtar) as $parca) {
                $binary .= chr((int) trim($parca));
            }

            return $binary;
        }

        return $hamAnahtar;
    }
}
