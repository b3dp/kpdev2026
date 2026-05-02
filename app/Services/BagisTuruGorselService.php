<?php

namespace App\Services;

use App\Models\BagisTuru;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

class BagisTuruGorselService
{
    public function gorselYatayiniIsleVeKaydet(BagisTuru $bagisTuru, array $formVerisi, ?string $varsayilanKaynak = null): void
    {
        try {
            $kaynak = $this->tekDosyaYolu(data_get($formVerisi, 'gorsel_yatay_gecici'));

            if (! filled($kaynak)) {
                $kaynak = filled($varsayilanKaynak) ? trim((string) $varsayilanKaynak) : '';
            }

            if (! filled($kaynak)) {
                return;
            }

            if ($this->kaynakZatenSpacesMi($kaynak)) {
                $bagisTuru->update([
                    'gorsel_kare' => null,
                    'gorsel_dikey' => null,
                    'gorsel_orijinal' => null,
                ]);

                return;
            }

            $icerik = $this->gorselIceriginiGetir($kaynak);
            if ($icerik === null) {
                return;
            }

            $slug = Str::slug($bagisTuru->slug ?: $bagisTuru->ad ?: ('bagis-turu-' . $bagisTuru->id));
            $zamanDamgasi = now()->format('YmdHis');
            $hedefDizin = "img26/opt/bagis/turleri/{$bagisTuru->id}";
            $hedefYol = "{$hedefDizin}/{$slug}-16x9-{$zamanDamgasi}.webp";

            $manager = ImageManager::imagick();
            $resim = $manager->read($icerik);
            $webpIcerik = (string) $resim->cover(1920, 1080)->toWebp(quality: 85);

            if (! Storage::disk('spaces')->put($hedefYol, $webpIcerik, 'public')) {
                Log::error('BagisTuru 16:9 gorsel Spaces alanina yuklenemedi', [
                    'bagis_turu_id' => $bagisTuru->id,
                    'kaynak' => $kaynak,
                    'hedef_yol' => $hedefYol,
                ]);

                return;
            }

            $bagisTuru->update([
                'gorsel_yatay' => Storage::disk('spaces')->url($hedefYol),
                'gorsel_kare' => null,
                'gorsel_dikey' => null,
                'gorsel_orijinal' => null,
            ]);
        } catch (Throwable $e) {
            Log::error('BagisTuru gorseli islenemedi', [
                'bagis_turu_id' => $bagisTuru->id,
                'hata' => $e->getMessage(),
            ]);
        }
    }

    private function kaynakZatenSpacesMi(string $kaynak): bool
    {
        try {
            if (! str_starts_with($kaynak, 'http://') && ! str_starts_with($kaynak, 'https://')) {
                return Str::startsWith(ltrim($kaynak, '/'), 'img26/');
            }

            $kaynakHost = parse_url($kaynak, PHP_URL_HOST);
            if (! is_string($kaynakHost) || $kaynakHost === '') {
                return false;
            }

            $hosts = collect([
                (string) parse_url((string) config('filesystems.disks.spaces.cdn_url'), PHP_URL_HOST),
                (string) parse_url((string) config('filesystems.disks.spaces.url'), PHP_URL_HOST),
                (string) parse_url((string) config('filesystems.disks.spaces.endpoint'), PHP_URL_HOST),
            ])->filter()->unique()->values();

            return $hosts->contains($kaynakHost);
        } catch (Throwable $e) {
            Log::error('BagisTuru kaynak host kontrolu hatasi', [
                'kaynak' => $kaynak,
                'hata' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function gorselIceriginiGetir(string $kaynak): ?string
    {
        try {
            if (str_starts_with($kaynak, 'http://') || str_starts_with($kaynak, 'https://')) {
                $yanit = Http::timeout(20)->retry(2, 300)->get($kaynak);

                if (! $yanit->successful()) {
                    Log::warning('BagisTuru gorsel URL istegi basarisiz', [
                        'kaynak' => $kaynak,
                        'durum' => $yanit->status(),
                    ]);

                    return null;
                }

                return $yanit->body();
            }

            if (Storage::disk('local')->exists($kaynak)) {
                return Storage::disk('local')->get($kaynak);
            }

            Log::warning('BagisTuru gorsel kaynagi okunamadi', [
                'kaynak' => $kaynak,
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('BagisTuru gorsel icerigi getirilemedi', [
                'kaynak' => $kaynak,
                'hata' => $e->getMessage(),
            ]);

            return null;
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
            Log::error('BagisTuru gecici dosya yolu okunamadi', [
                'hata' => $e->getMessage(),
            ]);

            return null;
        }
    }
}