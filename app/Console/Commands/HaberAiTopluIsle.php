<?php

namespace App\Console\Commands;

use App\Jobs\AiHaberIsleJob;
use App\Models\Haber;
use App\Services\HaberKategoriEslestirmeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class HaberAiTopluIsle extends Command
{
    protected $signature = 'haber:ai-toplu-isle
                            {--haber-idleri= : Virgulle ayrilmis haber id listesi}
                            {--limit=0 : Islenecek maksimum kayit sayisi}
                            {--offset=0 : Baslangic offset}
                            {--force-ozet : Mevcut ozet, meta description ve seo basligini yeniden uret}
                            {--sadece-kategori : Sadece kategori eslestirmesi calistir}
                            {--yayinda : Sadece yayindaki haberleri isle}';

    protected $description = 'Haberler icin AI ozet, kisi, kurum ve kategori eslestirmelerini toplu olarak calistirir.';

    public function handle(HaberKategoriEslestirmeService $haberKategoriEslestirmeService): int
    {
        $haberIdleri = collect(explode(',', (string) $this->option('haber-idleri')))
            ->map(static fn ($id) => (int) trim($id))
            ->filter(static fn ($id) => $id > 0)
            ->values();

        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $forceOzet = (bool) $this->option('force-ozet');
        $sadeceKategori = (bool) $this->option('sadece-kategori');
        $sadeceYayinda = (bool) $this->option('yayinda');

        $sorgu = Haber::query()
            ->when($haberIdleri->isNotEmpty(), fn ($query) => $query->whereIn('id', $haberIdleri->all()))
            ->when($sadeceYayinda, fn ($query) => $query->where('durum', 'yayinda'))
            ->orderBy('id');

        if ($offset > 0) {
            $sorgu->offset($offset);
        }

        if ($limit > 0) {
            $sorgu->limit($limit);
        }

        $haberler = $sorgu->get(['id', 'baslik', 'ozet', 'meta_description', 'seo_baslik', 'durum', 'icerik', 'kategori_id']);

        if ($haberler->isEmpty()) {
            $this->warn('Islenecek haber bulunamadi.');

            return self::SUCCESS;
        }

        $istatistik = [
            'toplam' => $haberler->count(),
            'basarili' => 0,
            'hata' => 0,
            'kategori' => 0,
        ];

        $this->info('Toplam haber: ' . $istatistik['toplam']);

        foreach ($haberler as $haber) {
            try {
                if (! $sadeceKategori) {
                    if ($forceOzet) {
                        $haber->update([
                            'ozet' => null,
                            'meta_description' => null,
                            'seo_baslik' => null,
                            'ai_islendi' => false,
                        ]);
                    }

                    dispatch_sync(new AiHaberIsleJob($haber->id));
                }

                if ($sadeceKategori) {
                    $haber = $haber->fresh();
                    $kategoriSonuclari = $haberKategoriEslestirmeService->haberIcinKategorileriBelirle($haber);
                    $kaydedilenler = $haberKategoriEslestirmeService->haberIcinKategorileriKaydet($haber, $kategoriSonuclari, 'ai');
                    $istatistik['kategori'] += count($kaydedilenler);
                }

                $istatistik['basarili']++;
                $this->line('#' . $haber->id . ' işlendi: ' . $haber->baslik);
            } catch (Throwable $exception) {
                $istatistik['hata']++;

                Log::error('HaberAiTopluIsle@handle kayit hatasi', [
                    'haber_id' => $haber->id,
                    'mesaj' => $exception->getMessage(),
                    'satir' => $exception->getLine(),
                ]);

                $this->error('#' . $haber->id . ' hata: ' . $exception->getMessage());
            }
        }

        $this->newLine();
        $this->info('Toplu AI islemi tamamlandi.');
        $this->line('Basarili: ' . $istatistik['basarili']);
        $this->line('Hata: ' . $istatistik['hata']);
        $this->line('Kategori kaydi: ' . $istatistik['kategori']);

        return self::SUCCESS;
    }
}