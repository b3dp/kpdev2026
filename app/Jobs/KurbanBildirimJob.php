<?php

namespace App\Jobs;

use App\Enums\KurbanBildirimKanali;
use App\Enums\KurbanBildirimSonucu;
use App\Models\KurbanKayit;
use App\Models\KurbanKisi;
use App\Services\HermesService;
use App\Services\KurbanService;
use App\Services\ZeptomailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class KurbanBildirimJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $kurbanId,
        public ?int $kurbanKisiId = null,
    ) {
        $this->onQueue('default');
    }

    public function handle(HermesService $hermesService, ZeptomailService $zeptomailService): void
    {
        try {
            $kurban = KurbanKayit::query()
                ->with(['kisiler', 'bildirimler'])
                ->find($this->kurbanId);

            if (! $kurban) {
                throw new \RuntimeException('Kurban kaydı bulunamadı.');
            }

            $servis = app(KurbanService::class);
            $kisiler = $this->kurbanKisiId
                ? $kurban->kisiler->where('id', $this->kurbanKisiId)
                : $kurban->kisiler;

            foreach ($kisiler as $kisi) {
                $mesaj = $servis->bildirimMetniOlustur($kurban, $kisi);

                if (filled($kisi->telefon) && ! $this->kanalDahaOnceGonderildi($kurban, $kisi, KurbanBildirimKanali::Sms)) {
                    $telefon = $this->telefonNormalize((string) $kisi->telefon);

                    if ($telefon !== '') {
                        try {
                            $sonuc = $hermesService->sendSMS([$telefon], $mesaj);

                            $kurban->bildirimler()->create([
                                'kurban_kisi_id' => $kisi->id,
                                'kanal' => KurbanBildirimKanali::Sms->value,
                                'alici_ad' => $kisi->ad_soyad,
                                'alici_iletisim' => $telefon,
                                'durum' => ($sonuc['basarili'] ?? false)
                                    ? KurbanBildirimSonucu::Gonderildi->value
                                    : KurbanBildirimSonucu::Basarisiz->value,
                                'hata_mesaji' => ($sonuc['basarili'] ?? false) ? null : 'SMS gönderimi başarısız.',
                                'gonderim_tarihi' => now(),
                            ]);
                        } catch (Throwable $exception) {
                            $kurban->bildirimler()->create([
                                'kurban_kisi_id' => $kisi->id,
                                'kanal' => KurbanBildirimKanali::Sms->value,
                                'alici_ad' => $kisi->ad_soyad,
                                'alici_iletisim' => $telefon,
                                'durum' => KurbanBildirimSonucu::Basarisiz->value,
                                'hata_mesaji' => mb_substr($exception->getMessage(), 0, 500),
                                'gonderim_tarihi' => now(),
                            ]);
                        }
                    }
                }

                if (filled($kisi->eposta) && ! $this->kanalDahaOnceGonderildi($kurban, $kisi, KurbanBildirimKanali::Eposta)) {
                    try {
                        $basarili = $zeptomailService->kurbanBildirimGonder(
                            (string) $kisi->eposta,
                            (string) $kisi->ad_soyad,
                            (string) $kurban->kurban_no,
                            (string) optional($kurban->kesim_tarihi)->format('d.m.Y H:i')
                        );

                        $kurban->bildirimler()->create([
                            'kurban_kisi_id' => $kisi->id,
                            'kanal' => KurbanBildirimKanali::Eposta->value,
                            'alici_ad' => $kisi->ad_soyad,
                            'alici_iletisim' => $kisi->eposta,
                            'durum' => $basarili
                                ? KurbanBildirimSonucu::Gonderildi->value
                                : KurbanBildirimSonucu::Basarisiz->value,
                            'hata_mesaji' => $basarili ? null : 'E-posta gönderimi başarısız.',
                            'gonderim_tarihi' => now(),
                        ]);
                    } catch (Throwable $exception) {
                        $kurban->bildirimler()->create([
                            'kurban_kisi_id' => $kisi->id,
                            'kanal' => KurbanBildirimKanali::Eposta->value,
                            'alici_ad' => $kisi->ad_soyad,
                            'alici_iletisim' => $kisi->eposta,
                            'durum' => KurbanBildirimSonucu::Basarisiz->value,
                            'hata_mesaji' => mb_substr($exception->getMessage(), 0, 500),
                            'gonderim_tarihi' => now(),
                        ]);
                    }
                }
            }

            $servis->bildirimDurumunuGuncelle($kurban->fresh('bildirimler'));
        } catch (Throwable $exception) {
            Log::error('KurbanBildirimJob başarısız.', [
                'kurban_id' => $this->kurbanId,
                'kurban_kisi_id' => $this->kurbanKisiId,
                'hata' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function kanalDahaOnceGonderildi(KurbanKayit $kurban, KurbanKisi $kisi, KurbanBildirimKanali $kanal): bool
    {
        return $kurban->bildirimler
            ->where('kurban_kisi_id', $kisi->id)
            ->where('kanal', $kanal)
            ->contains(fn ($bildirim) => ($bildirim->durum?->value ?? $bildirim->durum) === KurbanBildirimSonucu::Gonderildi->value);
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