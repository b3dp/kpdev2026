<?php

namespace App\Jobs;

use App\Models\SmsExcelGonderim;
use App\Models\SmsGonderim;
use App\Models\SmsGonderimAlici;
use App\Services\HermesService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

class ExcelSmsGonderimJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300;
    public $tries = 1;

    public function __construct(
        private string $dosyaYolu,
        private int $yoneticiId,
        private int $raporId,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $rapor = SmsExcelGonderim::query()->find($this->raporId);

        if (! $rapor) {
            Log::error('[ExcelSmsGonderim] Rapor kaydi bulunamadi', [
                'rapor_id' => $this->raporId,
            ]);

            return;
        }

        try {
            $rapor->update([
                'durum' => 'isleniyor',
                'basladi_at' => now(),
                'hata_mesaji' => null,
            ]);

            $gercekYol = $this->dosyaYolunuBul();

            if ($gercekYol === null) {
                $rapor->update([
                    'durum' => 'hatali',
                    'hata_mesaji' => 'Excel dosyasi bulunamadi.',
                    'tamamlandi_at' => now(),
                ]);

                return;
            }

            $sayaclar = [
                'toplam_satir' => 0,
                'gecerli_satir' => 0,
                'mukerrer' => 0,
                'hatali_format' => 0,
                'bos' => 0,
            ];

            $telefonlar = [];
            $exceldeGorulenler = [];

            $reader = new Reader();
            $reader->open($gercekYol);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = $row->getCells();
                    $hamTelefon = trim((string) ($cells[0]?->getValue() ?? ''));

                    if ($hamTelefon === '') {
                        $sayaclar['bos']++;
                        continue;
                    }

                    $sayaclar['toplam_satir']++;

                    $telefon = $this->telefonNormalize($hamTelefon);

                    if (! preg_match('/^5\d{9}$/', $telefon)) {
                        $sayaclar['hatali_format']++;
                        continue;
                    }

                    if (isset($exceldeGorulenler[$telefon])) {
                        $sayaclar['mukerrer']++;
                        continue;
                    }

                    $exceldeGorulenler[$telefon] = true;
                    $telefonlar[] = $telefon;
                    $sayaclar['gecerli_satir']++;
                }
            }

            $reader->close();

            if ($telefonlar === []) {
                $rapor->update(array_merge($sayaclar, [
                    'durum' => 'tamamlandi',
                    'alici_sayisi' => 0,
                    'basarili' => 0,
                    'basarisiz' => 0,
                    'bekleyen' => 0,
                    'tamamlandi_at' => now(),
                ]));

                return;
            }

            $sonuc = app(HermesService::class)->akilliGonder($telefonlar, (string) $rapor->mesaj);
            $async = (bool) ($sonuc['async'] ?? false);
            $basariliMi = (bool) ($sonuc['basarili'] ?? false);

            $gonderim = SmsGonderim::query()->create([
                'yonetici_id' => $this->yoneticiId,
                'tip' => 'excel',
                'mesaj' => (string) $rapor->mesaj,
                'liste_idler' => null,
                'alici_sayisi' => count($telefonlar),
                'basarili' => $async ? 0 : (int) ($sonuc['gecerli'] ?? 0),
                'basarisiz' => $async ? 0 : (int) ($sonuc['gecersiz'] ?? 0),
                'bekleyen' => $async ? count($telefonlar) : 0,
                'durum' => $async ? 'gonderiliyor' : ($basariliMi ? 'tamamlandi' : 'basarisiz'),
                'hermes_transaction_id' => isset($sonuc['transaction_id']) ? (string) $sonuc['transaction_id'] : null,
                'hermes_async_req_id' => isset($sonuc['req_log_id']) ? (string) $sonuc['req_log_id'] : null,
                'planli_tarih' => null,
            ]);

            foreach ($telefonlar as $telefon) {
                SmsGonderimAlici::query()->create([
                    'gonderim_id' => $gonderim->id,
                    'telefon' => $telefon,
                    'durum' => $async ? 'beklemede' : ($basariliMi ? 'basarili' : 'basarisiz'),
                    'created_at' => now(),
                ]);
            }

            $rapor->update(array_merge($sayaclar, [
                'durum' => 'tamamlandi',
                'alici_sayisi' => count($telefonlar),
                'basarili' => $async ? 0 : (int) ($sonuc['gecerli'] ?? 0),
                'basarisiz' => $async ? 0 : (int) ($sonuc['gecersiz'] ?? 0),
                'bekleyen' => $async ? count($telefonlar) : 0,
                'hermes_transaction_id' => isset($sonuc['transaction_id']) ? (string) $sonuc['transaction_id'] : null,
                'hermes_async_req_id' => isset($sonuc['req_log_id']) ? (string) $sonuc['req_log_id'] : null,
                'tamamlandi_at' => now(),
            ]));
        } catch (Throwable $e) {
            Log::error('[ExcelSmsGonderim] Hata olustu', [
                'rapor_id' => $this->raporId,
                'hata' => $e->getMessage(),
            ]);

            $rapor->update([
                'durum' => 'hatali',
                'hata_mesaji' => $e->getMessage(),
                'tamamlandi_at' => now(),
            ]);

            throw $e;
        } finally {
            Storage::disk('public')->delete($this->dosyaYolu);
        }
    }

    private function dosyaYolunuBul(): ?string
    {
        $adaylar = [
            Storage::disk('public')->path($this->dosyaYolu),
            storage_path('app/public/'.$this->dosyaYolu),
            storage_path('app/'.$this->dosyaYolu),
            $this->dosyaYolu,
        ];

        foreach ($adaylar as $aday) {
            if (file_exists($aday)) {
                return $aday;
            }
        }

        return null;
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
}
