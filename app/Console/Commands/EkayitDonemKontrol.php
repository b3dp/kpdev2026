<?php

namespace App\Console\Commands;

use App\Models\EkayitDonem;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EkayitDonemKontrol extends Command
{
    protected $signature = 'ekayit:donem-kontrol';
    protected $description = 'E-Kayıt dönemlerini tarih kontrolüne göre otomatik aktif/pasif yapar.';

    public function handle(): int
    {
        $simdi = Carbon::now();

        // Başlangıç tarihi gelmiş, henüz aktif olmayan dönemleri aktif yap
        $aktifYapilan = EkayitDonem::query()
            ->where('aktif', false)
            ->where('baslangic', '<=', $simdi)
            ->where('bitis', '>=', $simdi)
            ->update(['aktif' => true]);

        // Bitiş tarihi geçmiş, hâlâ aktif olan dönemleri pasif yap
        $pasifYapilan = EkayitDonem::query()
            ->where('aktif', true)
            ->where('bitis', '<', $simdi)
            ->update(['aktif' => false]);

        $this->info("Aktif yapılan dönem: {$aktifYapilan} | Pasif yapılan dönem: {$pasifYapilan}");

        return self::SUCCESS;
    }
}
