<?php

namespace App\Console\Commands;

use App\Enums\BagisDurumu;
use App\Jobs\MakbuzOlusturJob;
use App\Models\Bagis;
use Illuminate\Console\Command;
use Throwable;

class MakbuzTopluOlustur extends Command
{
    protected $signature = 'bagis:makbuz-toplu-olustur';

    protected $description = 'Makbuzu olmayan ödendi durumlu bağışlar için makbuz oluşturur.';

    public function handle(): int
    {
        $bagislar = Bagis::query()
            ->where('durum', BagisDurumu::Odendi->value)
            ->whereNull('makbuz_yol')
            ->get(['id', 'bagis_no']);

        $this->info('İşlem başlatılıyor: '.$bagislar->count().' bağış bulundu.');

        $basarili = 0;
        $hatali = 0;

        foreach ($bagislar as $bagis) {
            try {
                dispatch_sync(new MakbuzOlusturJob($bagis));
                $this->info('OK: '.$bagis->bagis_no);
                $basarili++;
            } catch (Throwable $e) {
                $this->error('HATA '.$bagis->bagis_no.': '.$e->getMessage());
                $hatali++;
            }
        }

        $this->info("Tamamlandı. Başarılı: {$basarili} / Hatalı: {$hatali}");

        return self::SUCCESS;
    }
}
