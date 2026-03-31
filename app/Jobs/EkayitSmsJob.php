<?php

namespace App\Jobs;

use App\Models\EkayitKayit;
use App\Models\SmsGonderim;
use App\Models\SmsGonderimAlici;
use App\Services\HermesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EkayitSmsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $kayitId,
        public string $tip,
        public string $telefon,
        public bool $durumGuncellensin = false,
        public int $yoneticiId = 1,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            $telefon = $this->telefonNormalize($this->telefon);

            if ($telefon === '') {
                throw new \RuntimeException('Geçerli telefon numarası bulunamadı.');
            }

            $kayit = EkayitKayit::with(['ogrenciBilgisi', 'sinif'])->find($this->kayitId);
            if (! $kayit) {
                throw new \RuntimeException('E-Kayıt kaydı bulunamadı.');
            }

            $mesajlar = [
                'basvuru_alindi' => '{AD_SOYAD} öğrencinizin {SINIF} sınıfı başvurusu alındı. İnceleme sonucu tarafınıza bildirilecektir. Kestanepazarı',
                'onaylandi' => 'Sayın Veli, {AD_SOYAD} öğrencinizin kaydı onaylanmıştır. Kestanepazarı',
                'reddedildi' => 'Sayın Veli, {AD_SOYAD} öğrencinizin başvurusu değerlendirme sonucunda kabul edilememiştir. Kestanepazarı',
                'yedek' => 'Sayın Veli, {AD_SOYAD} öğrenciniz yedek listeye alınmıştır. Sıra geldiğinde bilgilendirileceksiniz. Kestanepazarı',
            ];

            $mesajSablonu = $mesajlar[$this->tip] ?? $mesajlar['basvuru_alindi'];
            $mesaj = strtr($mesajSablonu, [
                '{AD_SOYAD}' => (string) ($kayit->ogrenciBilgisi?->ad_soyad ?? ''),
                '{SINIF}' => (string) ($kayit->sinif?->ad ?? ''),
            ]);

            $sonuc = app(HermesService::class)->sendSMS([$telefon], $mesaj);

            $gonderim = SmsGonderim::create([
                'yonetici_id' => auth()->id() ?? $this->yoneticiId,
                'tip' => 'bildirim_ekayit',
                'mesaj' => $mesaj,
                'alici_sayisi' => 1,
                'basarili' => ($sonuc['basarili'] ?? false) ? 1 : 0,
                'basarisiz' => ($sonuc['basarili'] ?? false) ? 0 : 1,
                'bekleyen' => 0,
                'durum' => ($sonuc['basarili'] ?? false) ? 'tamamlandi' : 'basarisiz',
                'hermes_transaction_id' => $sonuc['transaction_id'] ?? null,
            ]);

            SmsGonderimAlici::create([
                'gonderim_id' => $gonderim->id,
                'telefon' => $telefon,
                'durum' => ($sonuc['basarili'] ?? false) ? 'basarili' : 'basarisiz',
            ]);

            Log::info('[EkayitSmsJob] SMS gönderildi', [
                'kayit_id' => $this->kayitId,
                'tip' => $this->tip,
                'telefon' => $telefon,
            ]);
        } catch (Throwable $e) {
            Log::error('[EkayitSmsJob] SMS gönderilemedi', [
                'kayit_id' => $this->kayitId,
                'tip' => $this->tip,
                'hata' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function telefonNormalize(string $telefon): string
    {
        $temizTelefon = preg_replace('/\D+/', '', $telefon) ?: '';

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