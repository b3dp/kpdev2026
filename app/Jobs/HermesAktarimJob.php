<?php

namespace App\Jobs;

use App\Models\SmsKisi;
use App\Models\SmsListe;
use App\Models\Yonetici;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

class HermesAktarimJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300;
    public $tries = 1;

    public function __construct(
        private string $dosyaYolu,
        private int $yoneticiId,
        private ?int $listeId = null,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            $yonetici = Yonetici::find($this->yoneticiId);
            if (! $yonetici) {
                Log::error('[HermesAktarim] Yönetici bulunamadı', [
                    'yonetici_id' => $this->yoneticiId,
                ]);

                return;
            }

            $sayaclar = [
                'toplam' => 0,
                'eklenen' => 0,
                'mukerrer_db' => 0,
                'mukerrer_excel' => 0,
                'hatali_format' => 0,
                'bos' => 0,
            ];

            $exceldeGorulenler = [];

            // Formdan liste seçildiyse onu kullan, aksi halde geriye dönük varsayılan listeyi aç.
            if ($this->listeId) {
                $liste = SmsListe::query()->find($this->listeId);

                if (! $liste) {
                    Log::warning('[HermesAktarim] Seçilen liste bulunamadı', [
                        'liste_id' => $this->listeId,
                        'yonetici_id' => $this->yoneticiId,
                    ]);

                    return;
                }

                $listeBuYoneticiyeAit = (int) $liste->sahip_yonetici_id === $this->yoneticiId;
                if (! $listeBuYoneticiyeAit && ! $yonetici->hasRole('Admin')) {
                    Log::warning('[HermesAktarim] Liste erişim yetkisi yok', [
                        'liste_id' => $this->listeId,
                        'yonetici_id' => $this->yoneticiId,
                    ]);

                    return;
                }
            } else {
                $liste = SmsListe::firstOrCreate([
                    'ad' => '2026NisanOncesi',
                    'sahip_yonetici_id' => $this->yoneticiId,
                ]);
            }

            // Excel oku — Filament FileUpload disk-relative yolunu çöz
            $gercekYol = null;
            $adaylar = [
                Storage::disk('public')->path($this->dosyaYolu),
                storage_path('app/public/' . $this->dosyaYolu),
                storage_path('app/' . $this->dosyaYolu),
                $this->dosyaYolu,
            ];
            foreach ($adaylar as $aday) {
                if (file_exists($aday)) {
                    $gercekYol = $aday;
                    break;
                }
            }
            if ($gercekYol === null) {
                Log::error('[HermesAktarim] Dosya bulunamadı', [
                    'dosya_yolu' => $this->dosyaYolu,
                    'denenen_yollar' => $adaylar,
                ]);
                return;
            }

            $reader = new Reader();
            $reader->open($gercekYol);

            foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
                $sheetSatirNo = 0;
                $telefonKolonIndex = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $sheetSatirNo++;
                    $cells = $row->getCells();

                    if ($sheetSatirNo === 1) {
                        $telefonKolonIndex = $this->telefonKolonunuBul($cells);

                        if ($this->satirHeaderMi($cells)) {
                            continue;
                        }
                    }

                    $hamTelefon = $this->hucreDegeriniStringeCevir($cells[$telefonKolonIndex]?->getValue() ?? null);

                    if ($hamTelefon === '') {
                        $sayaclar['bos']++;
                        continue;
                    }

                    $sayaclar['toplam']++;

                    $telefon = $this->telefonNormalize($hamTelefon);

                    if ($telefon === null) {
                        $sayaclar['hatali_format']++;
                        Log::info('[HermesAktarim] Hatalı format atlandı', [
                            'ham' => $hamTelefon,
                            'sheet' => $sheetIndex + 1,
                            'satir' => $sheetSatirNo,
                            'kolon' => $telefonKolonIndex + 1,
                        ]);
                        continue;
                    }

                    if (isset($exceldeGorulenler[$telefon])) {
                        $sayaclar['mukerrer_excel']++;
                        continue;
                    }
                    $exceldeGorulenler[$telefon] = true;

                    $mevcutKisi = SmsKisi::query()
                        ->where('telefon', $telefon)
                        ->orWhere('telefon_2', $telefon)
                        ->first();

                    if ($mevcutKisi) {
                        if ((int) $mevcutKisi->created_by === $this->yoneticiId) {
                            if (! $mevcutKisi->listeler()->where('sms_listeler.id', $liste->id)->exists()) {
                                $mevcutKisi->listeler()->attach($liste->id);
                            }
                        }

                        $sayaclar['mukerrer_db']++;
                        continue;
                    }

                    $yeniKisi = SmsKisi::create([
                        'telefon' => $telefon,
                        'ad_soyad' => null,
                        'notlar' => null,
                        'created_by' => $this->yoneticiId,
                    ]);
                    $yeniKisi->listeler()->attach($liste->id);
                    $sayaclar['eklenen']++;
                }
            }

            $reader->close();

            // Activity log kaydet
            activity('hermes_aktarim')
                ->causedBy($yonetici)
                ->withProperties($sayaclar)
                ->log('Hermes numara aktarımı tamamlandı');

            // Sonuç log
            Log::info('[HermesAktarim] Aktarım tamamlandı', [
                'toplam' => $sayaclar['toplam'],
                'eklenen' => $sayaclar['eklenen'],
                'mukerrer_db' => $sayaclar['mukerrer_db'],
                'mukerrer_excel' => $sayaclar['mukerrer_excel'],
                'hatali_format' => $sayaclar['hatali_format'],
                'bos' => $sayaclar['bos'],
                'liste_id' => $liste->id,
                'yonetici_id' => $this->yoneticiId,
            ]);

        } catch (Throwable $e) {
            Log::error('[HermesAktarim] Hata oluştu', [
                'hata' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);

            activity('hermes_aktarim_hata')
                ->causedBy($yonetici ?? null)
                ->withProperties(['hata' => $e->getMessage()])
                ->log('Hermes aktarımında hata oluştu');

            throw $e;
        } finally {
            // Dosya sil
            Storage::disk('public')->delete($this->dosyaYolu);
        }
    }

    private function telefonNormalize(string $telefon): ?string
    {
        // ="12345" formatını temizle (Hermes Excel export formatı)
        $telefon = trim($telefon);
        $telefon = preg_replace('/^="(.+)"$/', '$1', $telefon) ?? $telefon;

        // Excel kaynaklı bilimsel gösterimi düz metne çevir
        $telefon = str_replace(',', '.', $telefon);
        if (preg_match('/^[+-]?\d+(\.\d+)?[eE][+-]?\d+$/', $telefon) === 1) {
            $telefon = number_format((float) $telefon, 0, '', '');
        }

        // 5321234567.0 gibi formatları sadeleştir
        if (preg_match('/^\d+\.0+$/', $telefon) === 1) {
            $telefon = strstr($telefon, '.', true) ?: $telefon;
        }

        // Sadece rakam bırak
        $temiz = preg_replace('/\D+/', '', $telefon) ?? '';

        if (str_starts_with($temiz, '0090')) {
            $temiz = substr($temiz, 4);
        } elseif (str_starts_with($temiz, '90') && strlen($temiz) === 12) {
            $temiz = substr($temiz, 2);
        } elseif (str_starts_with($temiz, '0') && strlen($temiz) === 11) {
            $temiz = substr($temiz, 1);
        }

        // Bazı exportlarda sona ekstra 0 eklenebiliyor (11 hane ve 5 ile başlıyorsa)
        if (strlen($temiz) === 11 && str_starts_with($temiz, '5')) {
            $temiz = substr($temiz, 0, 10);
        }

        if (strlen($temiz) !== 10 || ! str_starts_with($temiz, '5')) {
            return null;
        }

        return $temiz;
    }

    /**
     * Başlık satırından telefon kolonunu bulur. Bulamazsa 1. kolona düşer.
     */
    private function telefonKolonunuBul(array $cells): int
    {
        foreach ($cells as $index => $cell) {
            $deger = mb_strtolower($this->hucreDegeriniStringeCevir($cell?->getValue() ?? null), 'UTF-8');
            if ($deger === '') {
                continue;
            }

            if (str_contains($deger, 'telefon') || str_contains($deger, 'gsm') || str_contains($deger, 'cep')) {
                return (int) $index;
            }
        }

        return 0;
    }

    /**
     * İlk satırın başlık olup olmadığını kaba olarak tespit eder.
     */
    private function satirHeaderMi(array $cells): bool
    {
        foreach ($cells as $cell) {
            $deger = mb_strtolower($this->hucreDegeriniStringeCevir($cell?->getValue() ?? null), 'UTF-8');
            if ($deger === '') {
                continue;
            }

            if (str_contains($deger, 'telefon') || str_contains($deger, 'gsm') || str_contains($deger, 'cep') || str_contains($deger, 'ad') || str_contains($deger, 'soyad')) {
                return true;
            }
        }

        return false;
    }

    private function hucreDegeriniStringeCevir(mixed $deger): string
    {
        if ($deger === null) {
            return '';
        }

        if (is_int($deger)) {
            return (string) $deger;
        }

        if (is_float($deger)) {
            return number_format($deger, 0, '', '');
        }

        if (is_bool($deger)) {
            return $deger ? '1' : '0';
        }

        return trim((string) $deger);
    }

    public function failed(Throwable $exception): void
    {
        activity('job_hata')
            ->withProperties(['job' => static::class, 'hata' => $exception->getMessage()])
            ->log('HermesAktarimJob başarısız oldu');

        Log::error('[HermesAktarim] Job failed', [
            'hata' => $exception->getMessage(),
        ]);
    }
}
