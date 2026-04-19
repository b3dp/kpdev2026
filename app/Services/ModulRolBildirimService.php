<?php

namespace App\Services;

use App\Models\KurbanKayit;
use App\Models\MezunProfil;
use App\Models\Yonetici;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModulRolBildirimService
{
    public function mezunKaydiOlustu(int $mezunProfilId): void
    {
        try {
            $mezunProfil = MezunProfil::query()
                ->with(['uye', 'kurum'])
                ->find($mezunProfilId);

            if (! $mezunProfil) {
                return;
            }

            $alicilar = $this->rolAlicilariGetir(['Halkla İlişkiler']);

            if ($alicilar->isEmpty()) {
                return;
            }

            $adSoyad = (string) ($mezunProfil->uye?->ad_soyad ?? 'Belirtilmedi');
            $mezuniyetYili = $mezunProfil->mezuniyet_yili ?: 'Belirtilmedi';
            $kurum = (string) ($mezunProfil->kurum?->ad ?? $mezunProfil->kurum_manuel ?? 'Belirtilmedi');
            $durum = (string) ($mezunProfil->durum ?? 'beklemede');

            $konu = 'Yeni Mezun Kaydı Oluştu';
            $epostaMesaji = "Yeni mezun kaydı modüle düştü.\n\n"
                . "Mezun: {$adSoyad}\n"
                . "Mezuniyet Yılı: {$mezuniyetYili}\n"
                . "Kurum: {$kurum}\n"
                . "Durum: {$durum}\n"
                . "Kayıt ID: {$mezunProfil->id}\n\n"
                . "Panelden kontrol edebilirsiniz.";
            $smsMesaji = "Yeni mezun kaydı: {$adSoyad}, {$mezuniyetYili}, durum {$durum}. Panelden kontrol edin.";

            $this->epostaGonder($alicilar, $konu, $epostaMesaji);
            $this->smsGonder($alicilar, $smsMesaji, 'mezun', $mezunProfil->id);

            Log::info('Mezun rol bildirimleri kuyruğu işlendi.', [
                'mezun_profil_id' => $mezunProfil->id,
                'alici_sayisi' => $alicilar->count(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Mezun rol bildirimleri gönderilemedi.', [
                'mezun_profil_id' => $mezunProfilId,
                'hata' => $exception->getMessage(),
            ]);
        }
    }

    public function kurbanKaydiOlustu(int $kurbanKayitId): void
    {
        try {
            $kurbanKayit = KurbanKayit::query()
                ->with(['bagis', 'kisiler'])
                ->find($kurbanKayitId);

            if (! $kurbanKayit) {
                return;
            }

            $alicilar = $this->rolAlicilariGetir(['Kurban', 'Kurban Görüntüleme']);

            if ($alicilar->isEmpty()) {
                return;
            }

            $konu = 'Yeni Kurban Kaydı Oluştu';
            $epostaMesaji = "Yeni kurban kaydı modüle düştü.\n\n"
                . "Kurban No: {$kurbanKayit->kurban_no}\n"
                . "Bağış Türü: {$kurbanKayit->bagis_turu_adi}\n"
                . "Sahipler: {$kurbanKayit->sahiplerOzeti()}\n"
                . "Bağış No: " . ($kurbanKayit->bagis?->bagis_no ?? 'Belirtilmedi') . "\n"
                . "Kayıt ID: {$kurbanKayit->id}\n\n"
                . "Panelden kontrol edebilirsiniz.";
            $smsMesaji = "Yeni kurban kaydı: {$kurbanKayit->kurban_no}, {$kurbanKayit->bagis_turu_adi}. Panelden kontrol edin.";

            $this->epostaGonder($alicilar, $konu, $epostaMesaji);
            $this->smsGonder($alicilar, $smsMesaji, 'kurban', $kurbanKayit->id);

            Log::info('Kurban rol bildirimleri kuyruğu işlendi.', [
                'kurban_id' => $kurbanKayit->id,
                'alici_sayisi' => $alicilar->count(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Kurban rol bildirimleri gönderilemedi.', [
                'kurban_id' => $kurbanKayitId,
                'hata' => $exception->getMessage(),
            ]);
        }
    }

    private function rolAlicilariGetir(array $roller): Collection
    {
        return Yonetici::query()
            ->role($roller, 'admin')
            ->where('aktif', true)
            ->get()
            ->unique('id')
            ->values();
    }

    private function epostaGonder(Collection $alicilar, string $konu, string $mesaj): void
    {
        try {
            $epostaAlicilari = $alicilar
                ->filter(fn (Yonetici $yonetici) => filled($yonetici->eposta))
                ->map(fn (Yonetici $yonetici) => [
                    'eposta' => $yonetici->eposta,
                    'ad' => $yonetici->ad_soyad,
                ])
                ->values()
                ->all();

            if ($epostaAlicilari === []) {
                return;
            }

            app(ZeptomailService::class)->yoneticiAlertGonder($epostaAlicilari, $konu, $mesaj);
        } catch (Throwable $exception) {
            Log::error('Rol bazlı e-posta bildirimi gönderilemedi.', [
                'konu' => $konu,
                'hata' => $exception->getMessage(),
            ]);
        }
    }

    private function smsGonder(Collection $alicilar, string $mesaj, string $modul, int $kayitId): void
    {
        foreach ($alicilar as $alici) {
            try {
                $telefon = $this->telefonNormalize($alici->telefon);

                if ($telefon === '') {
                    continue;
                }

                $sonuc = app(HermesService::class)->sendSMS([$telefon], $mesaj);

                Log::info('Rol bazlı SMS bildirimi işlendi.', [
                    'modul' => $modul,
                    'kayit_id' => $kayitId,
                    'yonetici_id' => $alici->id,
                    'telefon' => $telefon,
                    'basarili' => (bool) ($sonuc['basarili'] ?? false),
                ]);
            } catch (Throwable $exception) {
                Log::error('Rol bazlı SMS bildirimi gönderilemedi.', [
                    'modul' => $modul,
                    'kayit_id' => $kayitId,
                    'yonetici_id' => $alici->id,
                    'hata' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function telefonNormalize(?string $telefon): string
    {
        $temizTelefon = preg_replace('/\D+/', '', (string) $telefon) ?: '';

        if (str_starts_with($temizTelefon, '0090')) {
            $temizTelefon = substr($temizTelefon, 4);
        } elseif (str_starts_with($temizTelefon, '90')) {
            $temizTelefon = substr($temizTelefon, 2);
        }

        if (str_starts_with($temizTelefon, '0')) {
            $temizTelefon = substr($temizTelefon, 1);
        }

        return $temizTelefon;
    }
}