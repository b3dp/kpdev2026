<?php

namespace App\Services;

use App\Models\EpostaGonderim;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZeptomailService
{
    private function gonderTemel(
        string $aliciEposta,
        string $aliciAd,
        string $konu,
        string $htmlIcerik,
        string $queue = 'default',
        ?string $ilgiliTip = null,
        ?int $ilgiliId = null,
        array $ekler = [],
    ): bool {
        // Rate limit kontrolü
        $key = 'eposta_rl_' . md5($aliciEposta . $konu);
        if (Cache::has($key)) {
            return false;
        }
        Cache::put($key, true, 60);

        // eposta_gonderimleri tablosuna kaydet
        $gonderim = EpostaGonderim::create([
            'sablon_kodu' => $ilgiliTip ?? 'manuel',
            'alici_eposta' => $aliciEposta,
            'alici_ad' => $aliciAd,
            'konu' => $konu,
            'durum' => 'beklemede',
            'ilgili_tip' => $ilgiliTip,
            'ilgili_id' => $ilgiliId,
            'created_at' => now(),
        ]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => config('services.zeptomail.api_key'),
            ])->post('https://api.zeptomail.com/v1.1/email', [
                'from' => [
                    'address' => config('services.zeptomail.from_address'),
                    'name' => config('services.zeptomail.from_name'),
                ],
                'to' => [
                    [
                        'email_address' => [
                            'address' => $aliciEposta,
                            'name' => $aliciAd ?: $aliciEposta,
                        ],
                    ],
                ],
                'subject' => $konu,
                'htmlbody' => $htmlIcerik,
                'track_clicks' => true,
                'track_opens' => true,
                'attachments' => $this->ekleriHazirla($ekler),
            ]);

            if ($response->successful()) {
                $gonderim->update([
                    'durum' => 'gonderildi',
                    'zeptomail_message_id' => $response->json('data.0.code') ?? null,
                ]);
                return true;
            }

            $gonderim->update([
                'durum' => 'basarisiz',
                'hata_mesaji' => $response->json('error.message') ?? $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            $gonderim->update([
                'durum' => 'basarisiz',
                'hata_mesaji' => $e->getMessage(),
            ]);
            Log::error('ZeptoMail gönderim hatası', [
                'alici' => $aliciEposta,
                'hata' => $e->getMessage(),
            ]);
            return false;
        }
    }

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
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => config('services.zeptomail.api_key'),
            ])->post('https://api.zeptomail.com/v1.1/email', [
                'from' => [
                    'address' => config('services.zeptomail.from_address'),
                    'name' => config('services.zeptomail.from_name'),
                ],
                'to' => [
                    [
                        'email_address' => [
                            'address' => $aliciEposta,
                            'name' => $aliciAd ?: $aliciEposta,
                        ],
                    ],
                ],
                'subject' => $konu,
                'htmlbody' => $icerik,
                'track_clicks' => true,
                'track_opens' => true,
            ]);

            if ($response->successful()) {
                $gonderim->update([
                    'durum' => 'gonderildi',
                    'zeptomail_message_id' => $response->json('data.0.code') ?? null,
                ]);

                return;
            }

            $gonderim->update([
                'durum' => 'basarisiz',
                'hata_mesaji' => $response->json('error.message') ?? $response->body(),
            ]);
        } catch (\Exception $e) {
            $gonderim->update([
                'durum' => 'basarisiz',
                'hata_mesaji' => $e->getMessage(),
            ]);

            Log::error('ZeptoMail gönderim hatası', [
                'alici' => $aliciEposta,
                'hata' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function otpGonder(string $eposta, string $ad, string $kod, string $tip = 'giris'): bool
    {
        $htmlIcerik = view('emails.otp', [
            'adSoyad' => $ad,
            'kod' => $kod,
            'gecerlilik' => '10 dakika',
            'islemAdi' => $tip === 'giris' ? 'Giriş işleminizi' : 'Kayıt işleminizi',
        ])->render();

        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            konu: 'Kestanepazarı Doğrulama Kodu: ' . $kod,
            htmlIcerik: $htmlIcerik,
            queue: 'high',
            ilgiliTip: 'otp_' . $tip,
        );
    }

    public function makbuzGonder(string $eposta, string $ad, string $makbuzUrl, string $bagisNo, string $tutar = ''): bool
    {
        $htmlIcerik = view('emails.bagis_makbuz', [
            'adSoyad' => $ad,
            'bagisNo' => $bagisNo,
            'tutar' => (float) $tutar,
            'tarih' => now()->format('d.m.Y H:i'),
            'makbuzUrl' => $makbuzUrl,
        ])->render();

        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            konu: "Bağış Makbuzunuz - #{$bagisNo}",
            htmlIcerik: $htmlIcerik,
            queue: 'high',
            ilgiliTip: 'bagis',
        );
    }

    public function bagisRaporGonder(string $eposta, string $ad, string $dosyaYolu, string $dosyaAdi, string $driveUrl, string $periyot, string $tarihAraligi): bool
    {
        $htmlIcerik = view('emails.bagis_rapor', [
            'periyot' => $periyot,
            'tarihAraligi' => $tarihAraligi,
            'driveUrl' => $driveUrl,
        ])->render();

        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            konu: "Bağış Raporu - {$periyot}",
            htmlIcerik: $htmlIcerik,
            queue: 'default',
            ilgiliTip: 'bagis_rapor',
            ekler: [[
                'name' => $dosyaAdi,
                'path' => $dosyaYolu,
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]],
        );
    }

    public function kurbanBildirimGonder(string $eposta, string $ad, string $kurbanNo, string $kesimTarihi): bool
    {
        $htmlIcerik = view('emails.kurban_kesildi', [
            'adSoyad' => $ad,
            'kurbanNo' => $kurbanNo,
            'kesimTarihi' => $kesimTarihi,
        ])->render();

        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            konu: "Kurban Kesim Bildirimi - #{$kurbanNo}",
            htmlIcerik: $htmlIcerik,
            queue: 'default',
            ilgiliTip: 'kurban',
        );
    }

    public function haberOnayGonder(string $eposta, string $ad, string $haberBaslik, string $onayUrl, string $redUrl): bool
    {
        $htmlIcerik = view('emails.haber_onay', [
            'adSoyad' => $ad,
            'haberBaslik' => $haberBaslik,
            'onayUrl' => $onayUrl,
            'redUrl' => $redUrl,
        ])->render();

        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            konu: "Haber Onay Talebi - {$haberBaslik}",
            htmlIcerik: $htmlIcerik,
            queue: 'default',
            ilgiliTip: 'haber',
        );
    }

    public function mezunOnayGonder(string $eposta, string $ad, bool $onaylandi, ?string $redNotu = null): bool
    {
        $htmlIcerik = view($onaylandi ? 'emails.mezun_onaylandi' : 'emails.mezun_reddedildi', [
            'adSoyad' => $ad,
            'redNotu' => $redNotu,
        ])->render();

        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            konu: $onaylandi ? 'Mezun Kaydınız Onaylandı' : 'Mezun Kaydınız Hakkında Bilgilendirme',
            htmlIcerik: $htmlIcerik,
            queue: 'default',
            ilgiliTip: 'mezun',
        );
    }

    public function mezunBildirimGonder(string $eposta, string $ad, bool $onaylandi, ?string $redNotu = null): bool
    {
        return $this->mezunOnayGonder($eposta, $ad, $onaylandi, $redNotu);
    }

    public function ekayitOnayGonder(string $eposta, string $ad, string $kayitNo, string $evrakUrl): bool
    {
        $htmlIcerik = view('emails.ekayit_onay', [
            'adSoyad' => $ad,
            'kayitNo' => $kayitNo,
            'evrakUrl' => $evrakUrl,
        ])->render();

        return $this->gonderTemel(
            aliciEposta: $eposta,
            aliciAd: $ad,
            konu: "E-Kayıt Başvurunuz Onaylandı - #{$kayitNo}",
            htmlIcerik: $htmlIcerik,
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

            $htmlIcerik = view('emails.yonetici_alert', [
                'konu' => $konu,
                'mesaj' => $mesaj,
            ])->render();

            $gonderildi = $this->gonderTemel(
                aliciEposta: $eposta,
                aliciAd: $ad,
                konu: $konu,
                htmlIcerik: $htmlIcerik,
                queue: 'high',
                ilgiliTip: 'sistem',
            );
            if (! $gonderildi) {
                $sonuc = false;
            }
        }
        return $sonuc;
    }

    private function ekleriHazirla(array $ekler): array
    {
        return collect($ekler)
            ->filter(fn (array $ek) => isset($ek['path']) && is_file($ek['path']))
            ->map(fn (array $ek) => [
                'name' => $ek['name'] ?? basename((string) $ek['path']),
                'mime_type' => $ek['mime_type'] ?? 'application/octet-stream',
                'content' => base64_encode((string) file_get_contents((string) $ek['path'])),
            ])
            ->values()
            ->all();
    }
}
