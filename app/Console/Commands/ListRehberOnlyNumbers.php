<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListRehberOnlyNumbers extends Command
{
    protected $signature = 'mezun:list-rehber-only';
    protected $description = 'Mezunlar Listesinde olan ama Mezunlar Modülünde olmayan numaraları listele';

    public function handle()
    {
        $this->info('Rehber Sadece Kişiler Raporu');
        $this->info('===================================');

        // Mezunlar Listesindeki (15 ID) tüm kişileri al
        $rehberMezunlar = DB::table('sms_liste_kisiler')
            ->where('liste_id', 15)
            ->join('sms_kisiler', 'sms_liste_kisiler.kisi_id', '=', 'sms_kisiler.id')
            ->select('sms_kisiler.id', 'sms_kisiler.ad_soyad', 'sms_kisiler.telefon')
            ->get();

        $this->line("Mezunlar Listesi (15 ID) Toplam: " . count($rehberMezunlar));

        // Mezunlar Modülündeki (mezun_profilleri) telefon numaralarını al
        $mezunlarTelefonlar = DB::table('mezun_profiller')
            ->join('uyeler', 'mezun_profiller.uye_id', '=', 'uyeler.id')
            ->where('uyeler.telefon', '!=', '')
            ->where('uyeler.telefon', '!=', null)
            ->pluck('uyeler.telefon')
            ->map(fn($tel) => $this->normalizeTelefon($tel))
            ->toArray();

        $this->line("Mezunlar Modülü Toplam: " . count(array_unique($mezunlarTelefonlar)));

        // Rehberde olup Mezunlar'da olmayan kişileri bul
        $rehberSadece = [];

        foreach ($rehberMezunlar as $kisi) {
            $normalizedTel = $this->normalizeTelefon($kisi->telefon);

            if (!in_array($normalizedTel, $mezunlarTelefonlar)) {
                $rehberSadece[] = [
                    'ad_soyad' => $kisi->ad_soyad,
                    'telefon' => $kisi->telefon,
                    'kisi_id' => $kisi->id,
                ];
            }
        }

        // Sonuçları göster
        $this->info("\n❓ REHBERDE OLAN AMA MEZUNLARDA OLMAYAN");
        $this->line("─────────────────────────────────────");
        $this->line("Toplam: " . count($rehberSadece) . " kişi\n");

        if (count($rehberSadece) > 0) {
            if (count($rehberSadece) <= 100) {
                foreach ($rehberSadece as $index => $kisi) {
                    $this->line(($index + 1) . ". {$kisi['ad_soyad']} - {$kisi['telefon']}");
                }
            } else {
                for ($i = 0; $i < 50; $i++) {
                    $kisi = $rehberSadece[$i];
                    $this->line(($i + 1) . ". {$kisi['ad_soyad']} - {$kisi['telefon']}");
                }
                $this->line("\n... ve " . (count($rehberSadece) - 50) . " daha");
            }
        }

        // İstatistik
        $this->info("\n📊 İSTATİSTİK");
        $this->line("─────────────────────────────────────");
        $this->line("Mezunlar Listesi (15) Toplam: " . count($rehberMezunlar));
        $this->line("Mezunlar Modülüne Ait: " . (count($rehberMezunlar) - count($rehberSadece)));
        $this->line("Sadece Rehberde: " . count($rehberSadece));
        $this->line("Oran: " . number_format((count($rehberSadece) / count($rehberMezunlar)) * 100, 2) . "%");
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
