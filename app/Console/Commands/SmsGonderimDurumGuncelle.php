<?php

namespace App\Console\Commands;

use App\Models\SmsGonderim;
use App\Services\HermesService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SmsGonderimDurumGuncelle extends Command
{
    protected $signature = 'sms:durum-guncelle';

    protected $description = 'Hermes transaction durumlarını çekerek SMS gönderim alıcı durumlarını günceller.';

    public function handle(HermesService $hermesService): int
    {
        $gonderimler = SmsGonderim::query()
            ->whereIn('durum', ['gonderiliyor', 'beklemede'])
            ->whereNotNull('hermes_transaction_id')
            ->with('alicilar')
            ->get();

        foreach ($gonderimler as $gonderim) {
            $detaylar = $hermesService->getTransactionDetails((int) $gonderim->hermes_transaction_id);

            foreach ($detaylar as $index => $satir) {
                if ($index === 0) continue; // header satırı atla
                // index 2 ve üzeri metadata (mesaj içeriği, kullanıcı) — sadece index 1 veri
                if ($index > 1) continue;
                $cozulmus = $this->detaySatiriCoz($satir);

                if (! $cozulmus) {
                    continue;
                }

                $telefon = $this->telefonNormalize($cozulmus['telefon']);
                $durum = $this->durumuMaple($cozulmus['durum']);

                $gonderim->alicilar()
                    ->where('telefon', $telefon)
                    ->update([
                        'durum' => $durum,
                        'hata_kodu' => $durum === 'basarisiz' ? ($cozulmus['durum'] ?: 'HATA') : null,
                    ]);
            }

            $basarili = $gonderim->alicilar()->where('durum', 'basarili')->count();
            $basarisiz = $gonderim->alicilar()->where('durum', 'basarisiz')->count();
            $bekleyen = $gonderim->alicilar()->where('durum', 'beklemede')->count();

            $gonderim->update([
                'basarili' => $basarili,
                'basarisiz' => $basarisiz,
                'bekleyen' => $bekleyen,
                'durum' => $bekleyen === 0 ? 'tamamlandi' : 'gonderiliyor',
            ]);
        }

        $this->info('SMS gönderim durumları güncellendi.');

        return self::SUCCESS;
    }

    private function detaySatiriCoz(array $satir): ?array
    {
        $telefon = null;
        $durum = null;

        foreach ($satir as $key => $value) {
            $key = preg_replace('/[^0-9a-zA-Z ]/', '', (string) $key);
            $value = preg_replace('/[^0-9a-zA-Z ]/', '', (string) $value);

            // Telefon tespiti — key veya value'da 10-14 haneli ve 5 veya 905 ile başlayan
            foreach ([$key, $value] as $metin) {
                if ($telefon === null && preg_match('/(?:0090|90|0)?5\d{9}/', $metin, $eslesme)) {
                    $telefon = $eslesme[0];
                }
            }

            // Durum tespiti — key veya value'da
            foreach ([$key, $value] as $metin) {
                if ($durum === null && preg_match(
                    '/\b(SUCCESSFUL|DELIVERED|WAITING|FAILED|ERROR|UNDELIVERED|PENDING)\b/i',
                    $metin,
                    $eslesme
                )) {
                    $durum = strtoupper($eslesme[1]);
                }
            }
        }

        if ($telefon === null || $durum === null) {
            return null;
        }

        return ['telefon' => $telefon, 'durum' => $durum];
    }

    private function durumuMaple(string $durum): string
    {
        return match (Str::upper($durum)) {
            'SUCCESSFUL', 'DELIVERED' => 'basarili',
            'WAITING', 'PENDING' => 'beklemede',
            default => 'basarisiz',
        };
    }

    private function telefonNormalize(string $telefon): string
    {
        $temiz = preg_replace('/\D+/', '', $telefon) ?? '';

        if (Str::startsWith($temiz, '0090')) {
            $temiz = substr($temiz, 4);
        } elseif (Str::startsWith($temiz, '90') && strlen($temiz) === 12) {
            $temiz = substr($temiz, 2);
        } elseif (Str::startsWith($temiz, '0') && strlen($temiz) === 11) {
            $temiz = substr($temiz, 1);
        }

        return $temiz;
    }
}
