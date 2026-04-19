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
                            {--yil= : Sadece yayin_tarihi alanina gore yil filtresi}
                            {--son-ay=0 : Sadece yayin_tarihi son N ay icinde olan haberleri isle}
                            {--force-ozet : Mevcut ozet, meta description ve seo basligini yeniden uret}
                            {--sadece-kategori : Sadece kategori eslestirmesi calistir}
                            {--sadece-eslestirme : Sadece kisi, kurum ve kategori eslestirmesi calistir}
                            {--sadece-seo-ozet : Icerige dokunmadan AI revizyonunda ozet, seo basligi ve meta description uret}
                            {--kuyruga-ekle : Isleri queue uzerinden arka plana birakir}
                            {--yayinda : Sadece yayindaki haberleri isle}';

    protected $description = 'Haberler icin AI revizyonu, kisi, kurum ve kategori eslestirmelerini toplu olarak calistirir.';

    public function handle(HaberKategoriEslestirmeService $haberKategoriEslestirmeService): int
    {
        $haberIdleri = collect(explode(',', (string) $this->option('haber-idleri')))
            ->map(static fn ($id) => (int) trim($id))
            ->filter(static fn ($id) => $id > 0)
            ->values();

        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $yil = (int) $this->option('yil');
        $sonAy = max(0, (int) $this->option('son-ay'));
        $forceOzet = (bool) $this->option('force-ozet');
        $sadeceKategori = (bool) $this->option('sadece-kategori');
        $sadeceEslestirme = (bool) $this->option('sadece-eslestirme');
        $sadeceSeoOzet = (bool) $this->option('sadece-seo-ozet');
        $kuyrugaEkle = (bool) $this->option('kuyruga-ekle');
        $sadeceYayinda = (bool) $this->option('yayinda');

        $seciliModSayisi = collect([$sadeceKategori, $sadeceEslestirme, $sadeceSeoOzet])->filter()->count();

        if ($seciliModSayisi > 1) {
            $this->error('Ayni anda sadece bir ozel mod secilebilir: sadece-kategori, sadece-eslestirme veya sadece-seo-ozet.');

            return self::FAILURE;
        }

        $sorgu = Haber::query()
            ->when($haberIdleri->isNotEmpty(), fn ($query) => $query->whereIn('id', $haberIdleri->all()))
            ->when($sadeceYayinda, fn ($query) => $query->where('durum', 'yayinda'))
            ->when($yil > 0, fn ($query) => $query->whereNotNull('yayin_tarihi')->whereYear('yayin_tarihi', $yil))
            ->when($sonAy > 0, fn ($query) => $query->whereNotNull('yayin_tarihi')->where('yayin_tarihi', '>=', now()->subMonths($sonAy)))
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
            'kuyruga_eklendi' => 0,
        ];

        $this->info('Toplam haber: ' . $istatistik['toplam']);

        foreach ($haberler as $haber) {
            try {
                if ($sadeceKategori) {
                    $haber = $haber->fresh();
                    $kategoriSonuclari = $haberKategoriEslestirmeService->haberIcinKategorileriBelirle($haber);
                    $kaydedilenler = $haberKategoriEslestirmeService->haberIcinKategorileriKaydet($haber, $kategoriSonuclari, 'ai');
                    $istatistik['kategori'] += count($kaydedilenler);
                } else {
                    $islemModu = $sadeceEslestirme
                        ? 'sadece_eslestirme'
                        : ($sadeceSeoOzet ? 'sadece_seo_ozet' : 'tam');

                    $job = new AiHaberIsleJob($haber->id, $islemModu, $forceOzet);

                    if ($kuyrugaEkle) {
                        dispatch($job);
                        $istatistik['kuyruga_eklendi']++;
                    } else {
                        dispatch_sync($job);
                    }
                }

                $istatistik['basarili']++;
                $durumMetni = $kuyrugaEkle && ! $sadeceKategori ? 'kuyruga eklendi' : 'islendi';
                $this->line('#' . $haber->id . ' ' . $durumMetni . ': ' . $haber->baslik);
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
        $this->line('Kuyruga eklenen: ' . $istatistik['kuyruga_eklendi']);

        return self::SUCCESS;
    }
}