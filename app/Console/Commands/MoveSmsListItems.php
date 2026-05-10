<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MoveSmsListItems extends Command
{
    protected $signature = 'sms:move-list-items {--dry-run}';
    protected $description = 'Liste 16 ve 17\'deki kişileri Liste 14\'e taşı, mükerrer oluşturma';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('SMS Liste Konsolidasyonu Başlıyor...');
        $this->info('---');

        // Mevcut durumu göster
        $count16 = DB::table('sms_liste_kisiler')->where('liste_id', 16)->count();
        $count17 = DB::table('sms_liste_kisiler')->where('liste_id', 17)->count();
        $count14 = DB::table('sms_liste_kisiler')->where('liste_id', 14)->count();

        $this->line("✓ Liste 16: {$count16} kişi");
        $this->line("✓ Liste 17: {$count17} kişi");
        $this->line("✓ Liste 14: {$count14} kişi (mevcut)");
        $this->info('---');

        if ($isDryRun) {
            $this->warn('DRY RUN MOD - Hiçbir değişiklik yapılmayacak');
            $this->info('---');
        }

        // Liste 16'dan taşı
        $this->line('Liste 16\'den taşınıyor...');
        $moved16 = $this->moveListItems(16, 14, $isDryRun);
        $this->line("  → {$moved16} kişi taşındı");

        // Liste 17'den taşı
        $this->line('Liste 17\'den taşınıyor...');
        $moved17 = $this->moveListItems(17, 14, $isDryRun);
        $this->line("  → {$moved17} kişi taşındı");

        $this->info('---');
        $this->info('✅ İşlem tamamlandı!');

        // Sonuç durumu göster
        if (!$isDryRun) {
            $newCount14 = DB::table('sms_liste_kisiler')->where('liste_id', 14)->count();
            $this->line("Liste 14 yeni toplam: {$newCount14} kişi");
            $this->line("(Önceki: {$count14} + Taşınan: " . ($moved16 + $moved17) . ")");
        }
    }

    private function moveListItems($fromListId, $toListId, $isDryRun = false)
    {
        // Taşınacak kişileri bul (hedef listede olmayan)
        $kisiIds = DB::table('sms_liste_kisiler')
            ->where('liste_id', $fromListId)
            ->whereNotIn('kisi_id', 
                DB::table('sms_liste_kisiler')
                    ->where('liste_id', $toListId)
                    ->pluck('kisi_id')
            )
            ->pluck('kisi_id')
            ->toArray();

        $count = count($kisiIds);

        if ($count === 0) {
            return 0;
        }

        if (!$isDryRun) {
            // Kişileri hedef listeye ekle
            $data = [];
            foreach ($kisiIds as $kisiId) {
                $data[] = [
                    'liste_id' => $toListId,
                    'kisi_id' => $kisiId,
                    'created_at' => now(),
                ];
            }

            DB::table('sms_liste_kisiler')->insert($data);

            // Eski listedeki kayıtları sil
            DB::table('sms_liste_kisiler')
                ->where('liste_id', $fromListId)
                ->whereIn('kisi_id', $kisiIds)
                ->delete();
        }

        return $count;
    }
}
