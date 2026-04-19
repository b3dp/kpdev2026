<?php

namespace App\Console\Commands;

use App\Models\Haber;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HaberYayinTarihiDuzelt extends Command
{
    protected $signature = 'haber:yayin-tarihi-duzelt
                            {--limit=0 : Islenecek maksimum kayit sayisi}
                            {--offset=0 : Baslangic offset}
                            {--son-ay=0 : created_at alanina gore son N ayda eklenen bos tarihli kayitlari isle}
                            {--base-url=https://kestanepazari.org.tr : Eski site base URL}
                            {--haber-idleri= : Virgulle ayrilmis haber id listesi}';

    protected $description = 'Yayin tarihi bos eski haberlerin tarihini eski sitedeki haber sayfasindan bularak gunceller.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $sonAy = max(0, (int) $this->option('son-ay'));
        $baseUrl = rtrim((string) $this->option('base-url'), '/');
        $haberIdleri = collect(explode(',', (string) $this->option('haber-idleri')))
            ->map(static fn ($id) => (int) trim($id))
            ->filter(static fn ($id) => $id > 0)
            ->values();

        $sorgu = Haber::query()
            ->whereNotNull('legacy_kaynak_id')
            ->whereNull('yayin_tarihi')
            ->when($sonAy > 0, fn ($query) => $query->where('created_at', '>=', now()->subMonths($sonAy)))
            ->when($haberIdleri->isNotEmpty(), fn ($query) => $query->whereIn('id', $haberIdleri->all()))
            ->orderBy('id');

        if ($offset > 0) {
            $sorgu->offset($offset);
        }

        if ($limit > 0) {
            $sorgu->limit($limit);
        }

        $haberler = $sorgu->get(['id', 'slug', 'baslik']);

        if ($haberler->isEmpty()) {
            $this->warn('Yayin tarihi duzeltilecek haber bulunamadi.');

            return self::SUCCESS;
        }

        $istatistik = [
            'toplam' => $haberler->count(),
            'guncellenen' => 0,
            'bulunamayan' => 0,
            'hata' => 0,
        ];

        $this->info('Toplam haber: ' . $istatistik['toplam']);

        foreach ($haberler as $haber) {
            try {
                $hamIcerik = $this->haberSayfasiniGetir($baseUrl, $haber->slug);

                if (! filled($hamIcerik)) {
                    $istatistik['bulunamayan']++;
                    $this->warn('#' . $haber->id . ' sayfa bulunamadi: ' . $haber->slug);

                    continue;
                }

                $yayinTarihi = $this->yayinTarihiniAyikla($hamIcerik);

                if (! $yayinTarihi) {
                    $istatistik['bulunamayan']++;
                    $this->warn('#' . $haber->id . ' tarih bulunamadi: ' . $haber->slug);

                    continue;
                }

                $haber->update(['yayin_tarihi' => $yayinTarihi]);
                $istatistik['guncellenen']++;
                $this->line('#' . $haber->id . ' guncellendi: ' . $haber->slug . ' -> ' . $yayinTarihi->format('Y-m-d H:i:s'));
            } catch (Throwable $exception) {
                $istatistik['hata']++;

                Log::error('HaberYayinTarihiDuzelt@handle kayit hatasi', [
                    'haber_id' => $haber->id,
                    'slug' => $haber->slug,
                    'mesaj' => $exception->getMessage(),
                    'satir' => $exception->getLine(),
                ]);

                $this->error('#' . $haber->id . ' hata: ' . $exception->getMessage());
            }
        }

        $this->newLine();
        $this->info('Yayin tarihi duzeltme tamamlandi.');
        $this->line('Guncellenen: ' . $istatistik['guncellenen']);
        $this->line('Bulunamayan: ' . $istatistik['bulunamayan']);
        $this->line('Hata: ' . $istatistik['hata']);

        return self::SUCCESS;
    }

    private function haberSayfasiniGetir(string $baseUrl, string $slug): ?string
    {
        $url = $baseUrl . '/haber/' . ltrim($slug, '/');
        $yanit = Http::timeout(20)->retry(1, 250)->get($url);

        if (! $yanit->successful()) {
            return null;
        }

        return (string) $yanit->body();
    }

    private function yayinTarihiniAyikla(string $html): ?Carbon
    {
        $desenler = [
            '/\|\s*(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s*</u',
            '/"datePublished"\s*:\s*"([^"]+)"/u',
            '/property="article:published_time"\s+content="([^"]+)"/u',
        ];

        foreach ($desenler as $desen) {
            if (preg_match($desen, $html, $eslesme) !== 1) {
                continue;
            }

            try {
                return Carbon::parse((string) $eslesme[1]);
            } catch (Throwable) {
                continue;
            }
        }

        $duzMetin = trim((string) preg_replace('/\s+/u', ' ', strip_tags($html)));

        if (preg_match('/\b(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\b/u', $duzMetin, $eslesme) === 1) {
            try {
                return Carbon::parse((string) $eslesme[1]);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}