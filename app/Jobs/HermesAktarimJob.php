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

            $satirNo = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $satirNo++;

                    // İlk satır (header) atla
                    if ($satirNo === 1) {
                        continue;
                    }

                    $cells = $row->getCells();

                    // Kolon 1: Cep Telefonu
                    $hamTelefon = trim((string) ($cells[0]?->getValue() ?? ''));

                    // Boş satır kontrol
                    if (empty($hamTelefon)) {
                        $sayaclar['bos']++;
                        continue;
                    }

                    $sayaclar['toplam']++;

                    // Telefon normalize et
                    $telefon = $this->telefonNormalize($hamTelefon);

                    // Hatalı format
                    if ($telefon === null) {
                        $sayaclar['hatali_format']++;
                        Log::info('[HermesAktarim] Hatalı format atlandı', ['ham' => $hamTelefon, 'satir' => $satirNo]);
                        continue;
                    }

                    // Excel içi mükerrer kontrol
                    if (isset($exceldeGorulenler[$telefon])) {
                        $sayaclar['mukerrer_excel']++;
                        continue;
                    }
                    $exceldeGorulenler[$telefon] = true;

                    // DB mükerrer kontrol
                    $mevcutKisi = SmsKisi::query()
                        ->where('telefon', $telefon)
                        ->orWhere('telefon_2', $telefon)
                        ->first();

                    if ($mevcutKisi) {
                        // Farklı kullanıcıya ait rehber kaydı paylaşılmaz.
                        if ((int) $mevcutKisi->created_by === $this->yoneticiId) {
                            if (! $mevcutKisi->listeler()->where('sms_listeler.id', $liste->id)->exists()) {
                                $mevcutKisi->listeler()->attach($liste->id);
                            }
                        }

                        $sayaclar['mukerrer_db']++;
                        continue;
                    }

                    // Yeni kişi oluştur ve listeye ekle
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
        $telefon = preg_replace('/^="(.+)"$/', '$1', $telefon);

        // Sadece rakam bırak
        $temiz = preg_replace('/\D/', '', $telefon);

        // 0090 prefix kaldır
        if (str_starts_with($temiz, '0090')) {
            $temiz = substr($temiz, 4);
        }
        // 90 prefix kaldır (10 haneden fazlaysa)
        elseif (str_starts_with($temiz, '90') && strlen($temiz) === 12) {
            $temiz = substr($temiz, 2);
        }
        // 0 prefix kaldır
        elseif (str_starts_with($temiz, '0') && strlen($temiz) === 11) {
            $temiz = substr($temiz, 1);
        }

        // 11 hane ve 5 ile başlıyorsa son haneyi at
        if (strlen($temiz) === 11 && str_starts_with($temiz, '5')) {
            $temiz = substr($temiz, 0, 10);
        }

        // Geçerlilik: tam 10 hane ve 5 ile başlamalı
        if (strlen($temiz) !== 10 || ! str_starts_with($temiz, '5')) {
            return null;
        }

        return $temiz;
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
