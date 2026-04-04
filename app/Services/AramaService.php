<?php

namespace App\Services;

use App\Models\AramaKaydi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AramaService
{
    private array $varsayilan_aramalar = [
        'burs',
        'etkinlik',
        'kurban',
        'kayıt',
        'zekat',
        'mezun',
        'yurt',
        'hafızlık',
    ];

    public function kaydetArama(string $arama_metni): void
    {
        try {
            $arama_metni = $this->aramaMetniTemizle($arama_metni);

            if ($arama_metni === null || ! Schema::hasTable('arama_kayitlari')) {
                return;
            }

            $kayit = AramaKaydi::query()->firstOrNew([
                'aranan_ifade' => $arama_metni,
            ]);

            $kayit->arama_sayisi = ((int) $kayit->arama_sayisi) + 1;
            $kayit->son_aranma_at = now();
            $kayit->save();

            Cache::forget('arama.populer.arama_listesi');
        } catch (Throwable $e) {
            Log::error('AramaService@kaydetArama hatasi', [
                'arama_metni' => $arama_metni,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);
        }
    }

    public function getirPopulerAramalar(int $limit = 8): array
    {
        try {
            $limit = max(1, min($limit, 12));

            if (! Schema::hasTable('arama_kayitlari')) {
                return array_slice($this->varsayilan_aramalar, 0, $limit);
            }

            $arama_listesi = Cache::remember('arama.populer.arama_listesi', now()->addMinutes(30), function () {
                $dinamik_aramalar = AramaKaydi::query()
                    ->whereNotNull('son_aranma_at')
                    ->where('son_aranma_at', '>=', now()->subDays(120))
                    ->orderByDesc('arama_sayisi')
                    ->orderByDesc('son_aranma_at')
                    ->take(12)
                    ->pluck('aranan_ifade')
                    ->all();

                return $this->benzersizAramaListesiOlustur([
                    ...$dinamik_aramalar,
                    ...$this->varsayilan_aramalar,
                ]);
            });

            return array_slice($arama_listesi, 0, $limit);
        } catch (Throwable $e) {
            Log::error('AramaService@getirPopulerAramalar hatasi', [
                'limit' => $limit,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);

            return array_slice($this->varsayilan_aramalar, 0, $limit);
        }
    }

    private function aramaMetniTemizle(?string $arama_metni): ?string
    {
        $arama_metni = Str::of((string) $arama_metni)->squish()->trim()->toString();

        return mb_strlen($arama_metni) >= 2 ? $arama_metni : null;
    }

    private function benzersizAramaListesiOlustur(array $aramalar): array
    {
        $liste = [];
        $eklenenler = [];

        foreach ($aramalar as $arama) {
            $arama = $this->aramaMetniTemizle((string) $arama);

            if ($arama === null) {
                continue;
            }

            $anahtar = mb_strtolower($arama, 'UTF-8');

            if (isset($eklenenler[$anahtar])) {
                continue;
            }

            $eklenenler[$anahtar] = true;
            $liste[] = $arama;
        }

        return $liste;
    }
}
