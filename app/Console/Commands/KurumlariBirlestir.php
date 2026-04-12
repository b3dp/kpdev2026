<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kurum;
use Illuminate\Support\Facades\DB;

class KurumlariBirlestir extends Command
{
    protected $signature = 'kurumlar:birlestir {ana_kurum_id} {--silinecek_kurum_ids=}';
    protected $description = 'Birden fazla kurum kaydını bir ana kuruma birleştirir ve geçişleri günceller';

    public function handle(): int
    {
        $anaKurumId = $this->argument('ana_kurum_id');
        $silinecekKurumIdsStr = $this->option('silinecek_kurum_ids');

        if (!$silinecekKurumIdsStr) {
            $this->error('--silinecek_kurum_ids parametre zorunludur. Örnek: --silinecek_kurum_ids=2,3,4');
            return 1;
        }

        $anaKurum = Kurum::find($anaKurumId);
        if (!$anaKurum) {
            $this->error("Ana kurum (ID: {$anaKurumId}) bulunamadı");
            return 1;
        }

        $silinecekKurumIds = array_map('intval', explode(',', $silinecekKurumIdsStr));
        $silinecekKurumlar = Kurum::whereIn('id', $silinecekKurumIds)->get();

        if ($silinecekKurumlar->isEmpty()) {
            $this->error("Silinecek kurum bulunamadı");
            return 1;
        }

        $this->info("Ana Kurum: {$anaKurum->ad} (ID: {$anaKurum->id})");
        $this->info("Birleştirilecek Kurumlar:");
        $silinecekKurumlar->each(fn($k) => $this->line("  - {$k->ad} (ID: {$k->id})"));

        if (!$this->confirm('Devam etmek istediğinizden emin misiniz?')) {
            return 0;
        }

        // Tüm referansları güncelle
        DB::table('haber_kurumlar')
            ->whereIn('kurum_id', $silinecekKurumIds)
            ->update(['kurum_id' => $anaKurumId]);

        $this->info("✓ haber_kurumlar tablosu güncellendi");

        // Çoğaltılan kayıtları temizle (aynı haberde aynı ana kurum birden fazla kez geçiyorsa 1'ini tut)
        $duplikatlar = DB::table('haber_kurumlar')
            ->select('haber_id', DB::raw('COUNT(*) as cnt'))
            ->where('kurum_id', $anaKurumId)
            ->groupBy('haber_id')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplikatlar->isNotEmpty()) {
            foreach ($duplikatlar as $dup) {
                $tutulacak = DB::table('haber_kurumlar')
                    ->where('haber_id', $dup->haber_id)
                    ->where('kurum_id', $anaKurumId)
                    ->orderBy('id')
                    ->first();

                DB::table('haber_kurumlar')
                    ->where('haber_id', $dup->haber_id)
                    ->where('kurum_id', $anaKurumId)
                    ->where('id', '!=', $tutulacak->id)
                    ->delete();
            }
            $this->info("✓ {$duplikatlar->count()} haberdeki çoğaltılmış kurum kaydı temizlendi");
        }

        // Eski kurumları soft delete et
        Kurum::whereIn('id', $silinecekKurumIds)->delete();
        $this->info("✓ Eski kurum kayıtları soft-delete edildi");

        // Özet
        $haber_sayisi = DB::table('haber_kurumlar')->where('kurum_id', $anaKurumId)->count();
        $this->info("✓ Tamamlandı! Ana kurum artık {$haber_sayisi} haberde referans ediliyor");

        return 0;
    }
}
