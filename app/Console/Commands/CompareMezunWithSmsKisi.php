<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompareMezunWithSmsKisi extends Command
{
    protected $signature = 'mezun:compare-sms-kisi';
    protected $description = 'Mezunların telefon numaralarını SMS Rehberi ile karşılaştır';

    public function handle()
    {
        $this->info('Mezunlar ↔ SMS Rehberi Karşılaştırması');
        $this->info('===================================');

        // Mezun listesini al - telefon numarası olan mezunlar
        $mezunlariBilgisi = DB::table('mezun_profiller')
            ->join('uyeler', 'mezun_profiller.uye_id', '=', 'uyeler.id')
            ->where('uyeler.telefon', '!=', '')
            ->where('uyeler.telefon', '!=', null)
            ->select('uyeler.id', 'uyeler.ad_soyad', 'uyeler.telefon')
            ->get();

        $this->line("\n📊 TEMEL BİLGİLER");
        $this->line("─────────────────────────────");
        $this->line("Mezun Sayısı (telefonu olan): " . $mezunlariBilgisi->count());

        // SMS Rehberine karşılaştır
        $eslestirme = [];
        $eslesmiyenler = [];
        $eslesmeler = [];

        foreach ($mezunlariBilgisi as $mezun) {
            $normalizedTel = $this->normalizeTelefon($mezun->telefon);

            // SMS Rehberinde ara
            $smsKisi = DB::table('sms_kisiler')
                ->where('telefon', $normalizedTel)
                ->first();

            if ($smsKisi) {
                // Listesini bul
                $listeler = DB::table('sms_liste_kisiler')
                    ->where('kisi_id', $smsKisi->id)
                    ->join('sms_listeler', 'sms_liste_kisiler.liste_id', '=', 'sms_listeler.id')
                    ->pluck('sms_listeler.ad')
                    ->toArray();

                $eslestirme[] = [
                    'mezun' => $mezun->ad_soyad,
                    'telefon' => $mezun->telefon,
                    'sms_kisi' => $smsKisi->ad_soyad,
                    'listeler' => implode(', ', $listeler) ?: '(List yok)',
                    'sms_kisi_id' => $smsKisi->id,
                ];

                $eslesmeler[] = $mezun->telefon;
            } else {
                $eslesmiyenler[] = [
                    'ad' => $mezun->ad_soyad,
                    'telefon' => $mezun->telefon,
                    'uye_id' => $mezun->id,
                ];
            }
        }

        // Sonuçları göster
        $this->info("\n✅ EŞLEŞENLER: " . count($eslestirme));
        $this->line("─────────────────────────────");

        if (count($eslestirme) > 0) {
            foreach ($eslestirme as $match) {
                $this->line("👤 {$match['mezun']}");
                $this->line("   Telefon: {$match['telefon']}");
                $this->line("   SMS Rehberi: {$match['sms_kisi']}");
                $this->line("   Listeler: {$match['listeler']}");
                $this->line("");
            }
        }

        // Grup istatistikleri
        $this->info("\n📈 İSTATİSTİKLER");
        $this->line("─────────────────────────────");
        $this->line("Toplam Mezun (telefonu olan): " . count($eslestirme) + count($eslesmiyenler));
        $this->line("✓ SMS Rehberinde var: " . count($eslestirme));
        $this->line("✗ SMS Rehberinde yok: " . count($eslesmiyenler));
        $this->line("Eşleşme Oranı: " . number_format((count($eslestirme) / (count($eslestirme) + count($eslesmiyenler))) * 100, 2) . "%");

        // Liste dağılımı
        if (count($eslestirme) > 0) {
            $this->info("\n📋 ESLEŞTİRİLEN MEZUNLARİN LİSTE DAĞILIMI");
            $this->line("─────────────────────────────");

            $listeDagilimi = [];
            foreach ($eslestirme as $match) {
                $listeler = explode(', ', $match['listeler']);
                foreach ($listeler as $liste) {
                    if ($liste !== '(List yok)') {
                        $listeDagilimi[$liste] = ($listeDagilimi[$liste] ?? 0) + 1;
                    }
                }
            }

            foreach ($listeDagilimi as $liste => $count) {
                $this->line("  • $liste: $count mezun");
            }
        }

        // Eşleşmeyenler
        if (count($eslesmiyenler) > 0) {
            $this->info("\n❌ EŞLEŞMEYENLER (" . count($eslesmiyenler) . " kişi)");
            $this->line("─────────────────────────────");

            if (count($eslesmiyenler) <= 50) {
                foreach ($eslesmiyenler as $kisi) {
                    $this->line("  • {$kisi['ad']} - {$kisi['telefon']}");
                }
            } else {
                $this->line("İlk 50 kayıt:");
                for ($i = 0; $i < 50; $i++) {
                    $kisi = $eslesmiyenler[$i];
                    $this->line("  • {$kisi['ad']} - {$kisi['telefon']}");
                }
                $this->line("  ... ve " . (count($eslesmiyenler) - 50) . " daha");
            }
        }
    }

    private function normalizeTelefon($telefon): string
    {
        // Tüm non-digit karakterleri temizle
        $cleaned = preg_replace('/[^0-9]/', '', $telefon);

        // +90 veya 90 başında ise 0'ı kaldır
        if (str_starts_with($cleaned, '90')) {
            $cleaned = substr($cleaned, 1);
        }

        // 0 ile başlıyorsa kaldır
        if (str_starts_with($cleaned, '0')) {
            $cleaned = substr($cleaned, 1);
        }

        return $cleaned;
    }
}
