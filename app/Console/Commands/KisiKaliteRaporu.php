<?php

namespace App\Console\Commands;

use App\Models\Kisi;
use Illuminate\Console\Command;

class KisiKaliteRaporu extends Command
{
    protected $signature = 'kisi:kalite-raporu {--limit=50 : Supheli kayit liste limiti}';

    protected $description = 'Kisi sozlugundeki supheli ve yinelenen kayitlari raporlar';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $supheliAnahtarlar = [
            'yarışma', 'yarism', 'ortaokul', 'lise', 'anadolu', 'imam', 'hatip', 'müdürlüğ',
            'mudurlug', 'müftül', 'muftul', 'kurs', 'dernek', 'vakıf', 'vakif', 'bakan',
            'program', 'etkinlik', 'toplantı', 'toplanti', 'tören', 'toren',
        ];

        $tumKayitlar = Kisi::withTrashed()
            ->select(['id', 'ad', 'soyad', 'ai_onaylandi', 'created_at'])
            ->orderBy('id')
            ->get();

        $supheliler = $tumKayitlar->filter(function (Kisi $kisi) use ($supheliAnahtarlar) {
            $tamAd = mb_strtolower(trim($kisi->ad . ' ' . $kisi->soyad));
            foreach ($supheliAnahtarlar as $anahtar) {
                if (str_contains($tamAd, $anahtar)) {
                    return true;
                }
            }

            return false;
        })->take($limit)->values();

        $tekrarlar = $tumKayitlar
            ->groupBy(fn (Kisi $kisi) => $this->normalize(trim($kisi->ad . ' ' . $kisi->soyad)))
            ->filter(fn ($grup) => $grup->count() > 1)
            ->take($limit);

        $this->info('Kisi kalite raporu');
        $this->line('Toplam kisi: ' . $tumKayitlar->count());
        $this->line('Supheli kayit sayisi (limitli gosterim): ' . $supheliler->count());
        $this->line('Tekrar grup sayisi (limitli gosterim): ' . $tekrarlar->count());
        $this->newLine();

        if ($supheliler->isNotEmpty()) {
            $this->warn('Supheli kayitlar:');
            $this->table(
                ['ID', 'Ad Soyad', 'AI Onay', 'Kayit'],
                $supheliler->map(fn (Kisi $kisi) => [
                    $kisi->id,
                    trim($kisi->ad . ' ' . $kisi->soyad),
                    $kisi->ai_onaylandi ? 'Evet' : 'Hayir',
                    optional($kisi->created_at)->format('d.m.Y H:i'),
                ])->all()
            );
            $this->newLine();
        }

        if ($tekrarlar->isNotEmpty()) {
            $this->warn('Tekrarli gruplar:');

            foreach ($tekrarlar as $anahtar => $grup) {
                $this->line('Ad Soyad anahtari: ' . $anahtar . ' | adet: ' . $grup->count());
                $this->table(
                    ['ID', 'Ad Soyad', 'AI Onay'],
                    $grup->map(fn (Kisi $kisi) => [
                        $kisi->id,
                        trim($kisi->ad . ' ' . $kisi->soyad),
                        $kisi->ai_onaylandi ? 'Evet' : 'Hayir',
                    ])->all()
                );
            }
        }

        return self::SUCCESS;
    }

    private function normalize(string $metin): string
    {
        return (string) str($metin)
            ->replace(['ş', 'Ş', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ü', 'Ü', 'ç', 'Ç'], ['s', 's', 'g', 'g', 'i', 'i', 'o', 'o', 'u', 'u', 'c', 'c'])
            ->lower()
            ->squish();
    }
}
