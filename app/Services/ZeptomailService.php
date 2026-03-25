<?php

namespace App\Services;

use App\Jobs\EpostaGonderJob;
use App\Models\EpostaGonderim;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class ZeptomailService
{
    private string $apiKey;
    private string $fromAddress;
    private string $fromName;
    private string $bounceAddress;

    public function __construct()
    {
        $this->apiKey       = config('services.zeptomail.api_key', '');
        $this->fromAddress  = config('services.zeptomail.from_address', 'bildirim@n.kestanepazari.org.tr');
        $this->fromName     = config('services.zeptomail.from_name', 'Kestanepazarı Öğrenci Yetiştirme Derneği');
        $this->bounceAddress = config('services.zeptomail.bounce_address', 'bounce@kestanepazari.org.tr');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Temel gönderim (Job aracılığıyla çağrılır)
    // ────────────────────────────────────────────────────────────────────────

    public function gonderTemel(
        string $aliciEposta,
        string $aliciAd,
        string $sablonKodu,
        array $degiskenler = [],
        string $queue = 'default',
        ?string $ilgiliTip = null,
        ?int $ilgiliId = null
    ): bool {
        // Rate limit kontrolü — aynı eposta + şablon 60sn içinde 2 kez gidemez
        $rlKey = 'eposta_rl_' . md5($aliciEposta . $sablonKodu);
        if (Cache::has($rlKey)) {
            Log::warning('ZeptomailService: Rate limit — e-posta engellendi', [
                'eposta'     => $aliciEposta,
                'sablon_kodu' => $sablonKodu,
            ]);
            return false;
        }
        Cache::put($rlKey, true, 60);

        // Blade render
        try {
            $icerik = View::make('emails.' . $sablonKodu, $degiskenler)->render();
        } catch (\Throwable $e) {
            Log::error('ZeptomailService: Blade render hatası', [
                'sablon' => $sablonKodu,
                'hata'   => $e->getMessage(),
            ]);
            return false;
        }

        // Konu satırı — şablon tablosundan ya da değişkenden
        $konu = $degiskenler['konu'] ?? $sablonKodu;

        // DB kaydı — beklemede
        $gonderim = EpostaGonderim::create([
            'sablon_kodu' => $sablonKodu,
            'alici_eposta' => $aliciEposta,
            'alici_ad'    => $aliciAd ?: null,
            'konu'        => $konu,
            'durum'       => 'beklemede',
            'ilgili_tip'  => $ilgiliTip,
            'ilgili_id'   => $ilgiliId,
            'created_at'  => now(),
        ]);

        // Job'a yolla
        EpostaGonderJob::dispatch($gonderim->id, $icerik, $aliciEposta, $aliciAd, $konu)
            ->onQueue($queue);

        return true;
    }

    // ────────────────────────────────────────────────────────────────────────
    // ZeptoMail API'ye gerçek gönderim (Job içinden çağrılır)
    // ────────────────────────────────────────────────────────────────────────

    public function apiGonder(
        int $gonderimId,
        string $icerik,
        string $aliciEposta,
        string $aliciAd,
        string $konu
    ): void {
        $gonderim = EpostaGonderim::find($gonderimId);
        if (! $gonderim) {
            return;
        }

        try {
            $payload = [
                'from' => [
                    'address' => $this->fromAddress,
                    'name'    => $this->fromName,
                ],
                'to' => [[
                    'email_address' => [
                        'address' => $aliciEposta,
                        'name'    => $aliciAd,
                    ],
                ]],
                'subject'     => $konu,
                'htmlbody'    => $icerik,
                'bounce_address' => $this->bounceAddress,
            ];

            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->post('https://api.zeptomail.com/v1.1/email', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $gonderim->update([
                    'durum'                  => 'gonderildi',
                    'zeptomail_message_id'   => $data['data'][0]['message_id'] ?? null,
                ]);
            } else {
                $hata = $response->body();
                $gonderim->update([
                    'durum'        => 'basarisiz',
                    'hata_mesaji'  => mb_substr($hata, 0, 500),
                ]);
                Log::error('ZeptomailService: API hatası', [
                    'status'  => $response->status(),
                    'body'    => $hata,
                    'alici'   => $aliciEposta,
                    'sablon'  => $gonderim->sablon_kodu,
                ]);
            }
        } catch (\Throwable $e) {
            $gonderim->update([
                'durum'       => 'basarisiz',
                'hata_mesaji' => mb_substr($e->getMessage(), 0, 500),
            ]);
            Log::error('ZeptomailService: İstisna', ['hata' => $e->getMessage()]);
            throw $e; // Job'un retry mekanizması çalışsın
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Özel metodlar
    // ────────────────────────────────────────────────────────────────────────

    public function otpGonder(string $eposta, string $ad, string $kod, string $tip = 'giris'): bool
    {
        $sablonKodu = 'otp_' . $tip;
        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            sablonKodu: $sablonKodu,
            degiskenler: [
                'adSoyad'     => $ad,
                'kod'         => $kod,
                'gecerlilik'  => '10 dakika',
                'konu'        => $tip === 'giris' ? 'Giriş Doğrulama Kodu' : 'Kayıt Doğrulama Kodu',
            ],
            queue: 'high',
        );
    }

    public function makbuzGonder(string $eposta, string $ad, string $makbuzUrl, string $bagisNo, string $tutar = ''): bool
    {
        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            sablonKodu: 'bagis_makbuz',
            degiskenler: [
                'adSoyad'   => $ad,
                'bagisNo'   => $bagisNo,
                'tutar'     => $tutar,
                'makbuzUrl' => $makbuzUrl,
                'konu'      => "Bağış Makbuzunuz — #{$bagisNo}",
            ],
            queue: 'high',
            ilgiliTip: 'bagis',
        );
    }

    public function kurbanBildirimGonder(string $eposta, string $ad, string $kurbanNo, string $kesimTarihi): bool
    {
        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            sablonKodu: 'kurban_kesildi',
            degiskenler: [
                'adSoyad'     => $ad,
                'kurbanNo'    => $kurbanNo,
                'kesimTarihi' => $kesimTarihi,
                'konu'        => "Kurban Kesim Bildirimi — #{$kurbanNo}",
            ],
            queue: 'default',
            ilgiliTip: 'kurban',
        );
    }

    public function haberOnayGonder(string $eposta, string $ad, string $haberBaslik, string $onayUrl, string $redUrl): bool
    {
        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            sablonKodu: 'haber_onay',
            degiskenler: [
                'adSoyad'     => $ad,
                'haberBaslik' => $haberBaslik,
                'onayUrl'     => $onayUrl,
                'redUrl'      => $redUrl,
                'konu'        => "Haber Onay Talebi — {$haberBaslik}",
            ],
            queue: 'default',
            ilgiliTip: 'haber',
        );
    }

    public function mezunBildirimGonder(string $eposta, string $ad, bool $onaylandi, ?string $redNotu = null): bool
    {
        $sablonKodu = $onaylandi ? 'mezun_onaylandi' : 'mezun_reddedildi';
        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            sablonKodu: $sablonKodu,
            degiskenler: [
                'adSoyad' => $ad,
                'redNotu' => $redNotu,
                'konu'    => $onaylandi ? 'Mezun Kaydınız Onaylandı' : 'Mezun Kaydınız Hakkında Bilgilendirme',
            ],
            queue: 'default',
            ilgiliTip: 'mezun',
        );
    }

    public function ekayitOnayGonder(string $eposta, string $ad, string $kayitNo, string $evrakUrl): bool
    {
        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            sablonKodu: 'ekayit_onay',
            degiskenler: [
                'adSoyad'  => $ad,
                'kayitNo'  => $kayitNo,
                'evrakUrl' => $evrakUrl,
                'konu'     => "E-Kayıt Başvurunuz Onaylandı — #{$kayitNo}",
            ],
            queue: 'default',
            ilgiliTip: 'ekayit',
        );
    }

    public function yoneticiAlertGonder(array $alicilar, string $konu, string $mesaj): bool
    {
        $sonuc = true;
        foreach ($alicilar as $alici) {
            $eposta = is_array($alici) ? ($alici['eposta'] ?? $alici['email'] ?? '') : $alici;
            $ad     = is_array($alici) ? ($alici['ad'] ?? $alici['name'] ?? 'Yönetici') : 'Yönetici';
            if (! $eposta) continue;

            $gonderildi = $this->gonderTemel(
                aliciEposta: $eposta,
                aliciAd: $ad,
                sablonKodu: 'yonetici_alert',
                degiskenler: [
                    'adSoyad' => $ad,
                    'konu'    => $konu,
                    'mesaj'   => $mesaj,
                ],
                queue: 'high',
                ilgiliTip: 'sistem',
            );
            if (! $gonderildi) {
                $sonuc = false;
            }
        }
        return $sonuc;
    }
}
