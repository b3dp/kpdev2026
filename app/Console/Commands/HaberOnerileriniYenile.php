<?php

namespace App\Console\Commands;

use App\Models\Haber;
use App\Models\Kisi;
use App\Models\Kurum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HaberOnerileriniYenile extends Command
{
    protected $signature = 'haber:oneri-yenile
                            {--haber-idleri= : Virgulle ayrilmis haber id listesi}
                            {--temizle : Mevcut haber kisi/kurum onerilerini once temizler}';

    protected $description = 'Secili haberler icin kisi ve kurum onerilerini daha sik filtrelerle yeniden uretir';

    public function handle(): int
    {
        $haberIdleri = collect(explode(',', (string) $this->option('haber-idleri')))
            ->map(static fn ($id) => (int) trim($id))
            ->filter(static fn ($id) => $id > 0)
            ->values();

        if ($haberIdleri->isEmpty()) {
            $this->error('En az bir haber id vermelisin. Ornek: --haber-idleri=11,12,13');

            return self::FAILURE;
        }

        if ((bool) $this->option('temizle')) {
            DB::table('haber_kisiler')->whereIn('haber_id', $haberIdleri)->delete();
            DB::table('haber_kurumlar')->whereIn('haber_id', $haberIdleri)->delete();
        }

        $haberler = Haber::query()
            ->whereIn('id', $haberIdleri)
            ->get(['id', 'icerik']);

        $kisiSozlugu = $this->kisiSozlugunuHazirla();
        $kurumSozlugu = $this->kurumSozlugunuHazirla();

        $eklenenKisi = 0;
        $eklenenKurum = 0;

        foreach ($haberler as $haber) {
            $metin = ' ' . $this->metniNormalizeEt((string) $haber->icerik) . ' ';

            foreach ($kisiSozlugu as $kisiVerisi) {
                if (! str_contains($metin, ' ' . $kisiVerisi['anahtar'] . ' ')) {
                    continue;
                }

                DB::table('haber_kisiler')->updateOrInsert(
                    ['haber_id' => $haber->id, 'kisi_id' => $kisiVerisi['id']],
                    [
                        'rol' => null,
                        'onay_durumu' => $kisiVerisi['ai_onaylandi'] ? 'onaylandi' : 'beklemede',
                        'updated_at' => now(),
                        'created_at' => now(),
                        'deleted_at' => null,
                    ]
                );

                $eklenenKisi++;
            }

            foreach ($kurumSozlugu as $kurumVerisi) {
                if (! str_contains($metin, ' ' . $kurumVerisi['anahtar'] . ' ')) {
                    continue;
                }

                DB::table('haber_kurumlar')->updateOrInsert(
                    ['haber_id' => $haber->id, 'kurum_id' => $kurumVerisi['id']],
                    [
                        'onay_durumu' => $kurumVerisi['aktif'] ? 'onaylandi' : 'beklemede',
                        'updated_at' => now(),
                        'created_at' => now(),
                        'deleted_at' => null,
                    ]
                );

                $eklenenKurum++;
            }
        }

        $this->info('Oneri yenileme tamamlandi.');
        $this->line('Islenen haber: ' . $haberler->count());
        $this->line('Kisi oneri sayisi: ' . DB::table('haber_kisiler')->whereIn('haber_id', $haberIdleri)->count());
        $this->line('Kurum oneri sayisi: ' . DB::table('haber_kurumlar')->whereIn('haber_id', $haberIdleri)->count());
        $this->line('Kisi insert deneme: ' . $eklenenKisi);
        $this->line('Kurum insert deneme: ' . $eklenenKurum);

        return self::SUCCESS;
    }

    private function kisiSozlugunuHazirla(): array
    {
        $kayitlar = Kisi::query()
            ->withTrashed()
            ->get(['id', 'ad', 'soyad', 'ai_onaylandi', 'deleted_at']);

        return $kayitlar
            ->map(function (Kisi $kisi): ?array {
                $tamAd = trim($kisi->ad . ' ' . $kisi->soyad);
                $anahtar = $this->metniNormalizeEt($tamAd);

                if (! $this->kisiAnahtariGecerliMi($anahtar)) {
                    return null;
                }

                return [
                    'id' => $kisi->id,
                    'anahtar' => $anahtar,
                    'ai_onaylandi' => (bool) $kisi->ai_onaylandi,
                    'deleted_at' => $kisi->deleted_at,
                ];
            })
            ->filter()
            ->sortBy([
                fn (array $kayit) => $kayit['ai_onaylandi'] ? 0 : 1,
                fn (array $kayit) => $kayit['deleted_at'] ? 1 : 0,
                fn (array $kayit) => $kayit['id'],
            ])
            ->unique('anahtar')
            ->values()
            ->all();
    }

    private function kurumSozlugunuHazirla(): array
    {
        $kayitlar = Kurum::query()
            ->withTrashed()
            ->get(['id', 'ad', 'aktif', 'deleted_at']);

        return $kayitlar
            ->map(function (Kurum $kurum): ?array {
                $anahtar = $this->metniNormalizeEt((string) $kurum->ad);

                if (! $this->kurumAnahtariGecerliMi($anahtar)) {
                    return null;
                }

                return [
                    'id' => $kurum->id,
                    'anahtar' => $anahtar,
                    'aktif' => (bool) $kurum->aktif,
                    'deleted_at' => $kurum->deleted_at,
                ];
            })
            ->filter()
            ->sortBy([
                fn (array $kayit) => $kayit['aktif'] ? 0 : 1,
                fn (array $kayit) => $kayit['deleted_at'] ? 1 : 0,
                fn (array $kayit) => $kayit['id'],
            ])
            ->unique('anahtar')
            ->values()
            ->all();
    }

    private function kisiAnahtariGecerliMi(string $anahtar): bool
    {
        if ($anahtar === '' || mb_strlen($anahtar) < 8 || mb_strlen($anahtar) > 80) {
            return false;
        }

        $kelimeler = preg_split('/\s+/u', $anahtar, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($kelimeler) < 2 || count($kelimeler) > 4) {
            return false;
        }

        foreach ($kelimeler as $kelime) {
            if (mb_strlen($kelime) < 2) {
                return false;
            }
        }

        $yasakliKokler = [
            'kestanepazari', 'muftulug', 'mudurlug', 'bakanlig', 'universite', 'belediye',
            'derneg', 'vakf', 'holding', 'kurs', 'okul', 'ortaokul', 'lise', 'anadolu',
            'imam', 'hatip', 'kategori', 'bolge', 'yaris', 'program', 'etkinlik',
            'toplanti', 'toren', 'merkez', 'kampus', 'sube', 'genclik', 'hizmet',
            'kur ', ' kur', 'kuran', 'grubu', 'yas', 'muftusu', 'cami', 'namaz',
            'kardeslig', 'bulusma', 'mensuplar', 'kurumlar', 'egitim', 'guzelbahce',
            'izmir', 'hatay', 'aliaga', 'karabaglar', 'konak', 'bornova',
        ];

        foreach ($yasakliKokler as $kok) {
            if (str_contains($anahtar, $kok)) {
                return false;
            }
        }

        return true;
    }

    private function kurumAnahtariGecerliMi(string $anahtar): bool
    {
        if ($anahtar === '' || mb_strlen($anahtar) < 6) {
            return false;
        }

        $genelYasaklar = [
            'mensuplari dernegi',
            'egitim kurumlari',
        ];

        foreach ($genelYasaklar as $yasak) {
            if ($anahtar === $yasak) {
                return false;
            }
        }

        $ipucuKokler = [
            'muftulug', 'mudurlug', 'bakanlig', 'universite', 'belediye', 'derneg',
            'vakf', 'kurs', 'okul', 'lise', 'ortaokul', 'a s', 'ltd', 'cami', 'genel mudurlug',
        ];

        foreach ($ipucuKokler as $kok) {
            if (str_contains($anahtar, $kok)) {
                return true;
            }
        }

        return false;
    }

    private function metniNormalizeEt(string $metin): string
    {
        $metin = strip_tags($metin);
        $metin = html_entity_decode($metin, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $metin = strtr($metin, [
            'ş' => 's', 'Ş' => 's', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'İ' => 'i',
            'ö' => 'o', 'Ö' => 'o', 'ü' => 'u', 'Ü' => 'u', 'ç' => 'c', 'Ç' => 'c',
        ]);
        $metin = mb_strtolower($metin);
        $metin = preg_replace('/[^a-z0-9\s]/u', ' ', $metin) ?? $metin;
        $metin = preg_replace('/\s+/u', ' ', $metin) ?? $metin;

        return trim($metin);
    }
}
