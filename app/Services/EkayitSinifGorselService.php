<?php

namespace App\Services;

use App\Models\EkayitSinif;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

class EkayitSinifGorselService
{
    public function gorselleriIsleVeKaydet(EkayitSinif $sinif, array $formVerisi): void
    {
        try {
            $alanHaritasi = [
                'gorsel_kare' => 'gorsel_kare_gecici',
                'gorsel_dikey' => 'gorsel_dikey_gecici',
                'gorsel_yatay' => 'gorsel_yatay_gecici',
                'gorsel_orijinal' => 'gorsel_orijinal_gecici',
            ];

            $guncellenecekAlanlar = [];

            foreach ($alanHaritasi as $hedefAlan => $geciciAlan) {
                $kaynakYol = $this->tekDosyaYolu(data_get($formVerisi, $geciciAlan));

                if (! filled($kaynakYol)) {
                    continue;
                }

                $islenmisUrl = $this->tekGorseliIsle($sinif, $hedefAlan, $kaynakYol);

                if (filled($islenmisUrl)) {
                    $guncellenecekAlanlar[$hedefAlan] = $islenmisUrl;
                }
            }

            if ($guncellenecekAlanlar !== []) {
                $sinif->update($guncellenecekAlanlar);
            }
        } catch (Throwable $e) {
            Log::error('EkayitSinif gorselleri islenemedi', [
                'sinif_id' => $sinif->id,
                'hata' => $e->getMessage(),
            ]);
        }
    }

    private function tekGorseliIsle(EkayitSinif $sinif, string $hedefAlan, string $kaynakYol): ?string
    {
        try {
            if (! Storage::disk('local')->exists($kaynakYol)) {
                Log::warning('EkayitSinif gorsel kaynagi local diskte bulunamadi', [
                    'sinif_id' => $sinif->id,
                    'alan' => $hedefAlan,
                    'kaynak' => $kaynakYol,
                ]);

                return null;
            }

            $icerik = Storage::disk('local')->get($kaynakYol);
            $uzanti = strtolower(pathinfo($kaynakYol, PATHINFO_EXTENSION) ?: 'jpg');
            $uzanti = in_array($uzanti, ['jpg', 'jpeg', 'png', 'webp'], true) ? $uzanti : 'jpg';

            $slug = Str::slug($sinif->ad ?: ('sinif-' . $sinif->id));
            $zamanDamgasi = now()->format('YmdHis');

            $oriDizin = "img26/ori/ekayit/siniflar/{$sinif->id}";
            $optDizin = "img26/opt/ekayit/siniflar/{$sinif->id}";

            $orijinalYol = "{$oriDizin}/{$slug}-{$hedefAlan}-{$zamanDamgasi}.{$uzanti}";
            $optimizeYol = "{$optDizin}/{$slug}-{$hedefAlan}-{$zamanDamgasi}.webp";

            Storage::disk('spaces')->put($orijinalYol, $icerik, 'public');

            $manager = ImageManager::imagick();
            $resim = $manager->read($icerik);

            [$genislik, $yukseklik] = $this->boyutGetir($hedefAlan);

            $optimizeIcerik = (string) $resim->cover($genislik, $yukseklik)->toWebp(quality: 85);
            Storage::disk('spaces')->put($optimizeYol, $optimizeIcerik, 'public');

            // Orijinal alan kaynak dosyayi, diger alanlar optimize versiyonu tutar.
            if ($hedefAlan === 'gorsel_orijinal') {
                return Storage::disk('spaces')->url($orijinalYol);
            }

            return Storage::disk('spaces')->url($optimizeYol);
        } catch (Throwable $e) {
            Log::error('EkayitSinif tek gorsel isleme hatasi', [
                'sinif_id' => $sinif->id,
                'alan' => $hedefAlan,
                'kaynak' => $kaynakYol,
                'hata' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function boyutGetir(string $hedefAlan): array
    {
        try {
            return match ($hedefAlan) {
                'gorsel_kare' => [1080, 1080],
                'gorsel_dikey' => [1080, 1920],
                'gorsel_yatay' => [1920, 1080],
                default => [1920, 1080],
            };
        } catch (Throwable $e) {
            Log::error('EkayitSinif boyut belirleme hatasi', [
                'alan' => $hedefAlan,
                'hata' => $e->getMessage(),
            ]);

            return [1920, 1080];
        }
    }

    private function tekDosyaYolu(mixed $deger): ?string
    {
        try {
            if (is_string($deger) && filled($deger)) {
                return $deger;
            }

            if (is_array($deger)) {
                foreach ($deger as $oge) {
                    if (is_string($oge) && filled($oge)) {
                        return $oge;
                    }
                }
            }

            return null;
        } catch (Throwable $e) {
            Log::error('EkayitSinif dosya yolu okunamadi', [
                'hata' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
