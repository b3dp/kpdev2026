<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MezunuRehbereEkle extends Command
{
    protected $signature = 'mezun:add-to-rehber {--dry-run}';
    protected $description = 'Mezunları SMS Rehberine ekle ve Mezunlar listesine (15 ID) atama yap';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Mezunları SMS Rehberine Ekleme');
        $this->info('===================================');

        if ($isDryRun) {
            $this->warn('DRY RUN MOD - Hiçbir değişiklik yapılmayacak');
            $this->info('---');
        }

        // Adım 1: Rehberde olan 457 mezunu "Mezunlar" listesine ekle
        $this->info("\n📝 ADIM 1: Mevcut 457 Mezunu Mezunlar Listesine Ekle");
        $this->line('─────────────────────────────');

        $mevcutMezunlar = $this->getMevcutMezunlar();
        $this->line("İşlenecek: " . count($mevcutMezunlar) . " mezun");

        if (!$isDryRun) {
            $eklenen = 0;
            $var = 0;

            foreach ($mevcutMezunlar as $mezun) {
                // Zaten liste 15'de mi?
                $exists = DB::table('sms_liste_kisiler')
                    ->where('liste_id', 15)
                    ->where('kisi_id', $mezun['kisi_id'])
                    ->exists();

                if (!$exists) {
                    DB::table('sms_liste_kisiler')->insert([
                        'liste_id' => 15,
                        'kisi_id' => $mezun['kisi_id'],
                        'created_at' => now(),
                    ]);
                    $eklenen++;
                } else {
                    $var++;
                }
            }

            $this->line("✓ Eklenen: $eklenen");
            $this->line("⚠️  Zaten var: $var");
        }

        // Adım 2: Rehberde olmayan 556 mezunu ekle
        $this->info("\n📝 ADIM 2: Yeni 556 Mezunu Rehbere ve Mezunlar Listesine Ekle");
        $this->line('─────────────────────────────');

        $yeniMezunlar = $this->getYeniMezunlar();
        $this->line("İşlenecek: " . count($yeniMezunlar) . " mezun");

        if (!$isDryRun) {
            $eklenen = 0;

            foreach ($yeniMezunlar as $mezun) {
                // SMS Rehberine ekle
                $smsKisi = DB::table('sms_kisiler')->insertGetId([
                    'telefon' => $this->normalizeTelefon($mezun['telefon']),
                    'ad_soyad' => $mezun['ad_soyad'],
                    'created_by' => auth()->id() ?? 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Mezunlar listesine ekle
                DB::table('sms_liste_kisiler')->insert([
                    'liste_id' => 15,
                    'kisi_id' => $smsKisi,
                    'created_at' => now(),
                ]);

                $eklenen++;
            }

            $this->line("✓ Eklenen: $eklenen");
        }

        // Sonuç
        $this->info("\n✅ İşlem Tamamlandı!");
        $this->line('─────────────────────────────');

        if (!$isDryRun) {
            // Kontrol
            $liste15Total = DB::table('sms_liste_kisiler')
                ->where('liste_id', 15)
                ->count();

            $this->line("Mezunlar Listesi (15 ID) Toplam: $liste15Total kişi");
            $this->line("(Önceki: 244 + Eklenen: " . (count($mevcutMezunlar) + count($yeniMezunlar)) . ")");
        }
    }

    private function getMevcutMezunlar()
    {
        $mezunlariBilgisi = DB::table('mezun_profiller')
            ->join('uyeler', 'mezun_profiller.uye_id', '=', 'uyeler.id')
            ->where('uyeler.telefon', '!=', '')
            ->where('uyeler.telefon', '!=', null)
            ->select('uyeler.id', 'uyeler.ad_soyad', 'uyeler.telefon')
            ->get();

        $eslestirme = [];

        foreach ($mezunlariBilgisi as $mezun) {
            $normalizedTel = $this->normalizeTelefon($mezun->telefon);

            // SMS Rehberinde ara
            $smsKisi = DB::table('sms_kisiler')
                ->where('telefon', $normalizedTel)
                ->first();

            if ($smsKisi) {
                $eslestirme[] = [
                    'mezun_ad' => $mezun->ad_soyad,
                    'telefon' => $mezun->telefon,
                    'kisi_id' => $smsKisi->id,
                ];
            }
        }

        return $eslestirme;
    }

    private function getYeniMezunlar()
    {
        $mezunlariBilgisi = DB::table('mezun_profiller')
            ->join('uyeler', 'mezun_profiller.uye_id', '=', 'uyeler.id')
            ->where('uyeler.telefon', '!=', '')
            ->where('uyeler.telefon', '!=', null)
            ->select('uyeler.id', 'uyeler.ad_soyad', 'uyeler.telefon')
            ->get();

        $yeni = [];

        foreach ($mezunlariBilgisi as $mezun) {
            $normalizedTel = $this->normalizeTelefon($mezun->telefon);

            // SMS Rehberinde ara
            $smsKisi = DB::table('sms_kisiler')
                ->where('telefon', $normalizedTel)
                ->first();

            if (!$smsKisi) {
                // Telefon zaten SMS'de yok mu?
                $exists = DB::table('sms_kisiler')
                    ->where('telefon', $normalizedTel)
                    ->exists();

                if (!$exists) {
                    $yeni[] = [
                        'mezun_id' => $mezun->id,
                        'ad_soyad' => $mezun->ad_soyad,
                        'telefon' => $mezun->telefon,
                    ];
                }
            }
        }

        return $yeni;
    }

    private function normalizeTelefon($telefon): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $telefon);

        if (str_starts_with($cleaned, '90')) {
            $cleaned = substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '0')) {
            $cleaned = substr($cleaned, 1);
        }

        return $cleaned;
    }
}
