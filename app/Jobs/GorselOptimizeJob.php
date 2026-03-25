<?php

namespace App\Jobs;

use App\Models\Etkinlik;
use App\Models\EtkinlikGorseli;
use App\Models\Haber;
use App\Models\HaberGorseli;
use App\Models\KurumsalSayfa;
use App\Models\KurumsalSayfaGorseli;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

class GorselOptimizeJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    /**
     * @param  string  $gorselTipi  'ana_gorsel' | 'galeri_gorseli'
     * @param  string  $geciciYol   local disk relative path (tmp/haberler/...)
     */
    public function __construct(
        public readonly int $modelId,
        public readonly string $modelTipi,
        public readonly string $gorselTipi,
        public readonly string $geciciYol,
        public readonly int $sira = 1,
    ) {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function handle(): void
    {
        if ($this->modelTipi === 'haber') {
            $this->haberGorselleriniIsle();
            return;
        }

        if ($this->modelTipi === 'etkinlik') {
            $this->etkinlikGorselleriniIsle();
            return;
        }

        if ($this->modelTipi === 'kurumsal_sayfa') {
            $this->kurumsalSayfaGorselleriniIsle();
        }
    }

    protected function haberGorselleriniIsle(): void
    {
        $haber = Haber::query()->find($this->modelId);

        if (! $haber) {
            return;
        }

        $slug = $haber->slug ?: 'haber-' . $haber->id;
        $uzanti = pathinfo($this->geciciYol, PATHINFO_EXTENSION) ?: 'jpeg';
        $geciciTamYol = Storage::disk('local')->path($this->geciciYol);

        $manager = ImageManager::imagick();
        $resim = $manager->read($geciciTamYol);

        $oriDizin = "img26/ori/haberler/{$haber->id}";
        $optDizin = "img26/opt/haberler/{$haber->id}";

        if ($this->gorselTipi === 'ana_gorsel') {
            $orijinalYol  = "{$oriDizin}/{$slug}-ana-orijinal.{$uzanti}";
            $lgYol        = "{$optDizin}/{$slug}-ana-lg.webp";
            $ogYol        = "{$optDizin}/{$slug}-ana-og.webp";
            $smYol        = "{$optDizin}/{$slug}-ana-sm.webp";
            $mobilLgYol   = "{$optDizin}/{$slug}-ana-mobil-lg.webp";

            Storage::disk('spaces')->put($orijinalYol, Storage::disk('local')->get($this->geciciYol), 'public');
            Storage::disk('spaces')->put($lgYol, (string) $resim->cover(1280, 720)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($ogYol, (string) $resim->cover(1200, 675)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($smYol, (string) $resim->cover(320, 180)->toWebp(quality: 80), 'public');
            Storage::disk('spaces')->put($mobilLgYol, (string) $resim->cover(768, 432)->toWebp(quality: 85), 'public');

            $haber->update([
                'gorsel_orijinal'  => Storage::disk('spaces')->url($orijinalYol),
                'gorsel_lg'        => Storage::disk('spaces')->url($lgYol),
                'gorsel_og'        => Storage::disk('spaces')->url($ogYol),
                'gorsel_sm'        => Storage::disk('spaces')->url($smYol),
                'gorsel_mobil_lg'  => Storage::disk('spaces')->url($mobilLgYol),
            ]);

            return;
        }

        if ($this->gorselTipi === 'galeri_gorseli') {
            $siraNo = str_pad((string) $this->sira, 3, '0', STR_PAD_LEFT);

            $orijinalYol = "{$oriDizin}/{$slug}-{$siraNo}-orijinal.{$uzanti}";
            $lgYol       = "{$optDizin}/{$slug}-{$siraNo}-lg.webp";
            $ogYol       = "{$optDizin}/{$slug}-{$siraNo}-og.webp";
            $smYol       = "{$optDizin}/{$slug}-{$siraNo}-sm.webp";

            Storage::disk('spaces')->put($orijinalYol, Storage::disk('local')->get($this->geciciYol), 'public');
            Storage::disk('spaces')->put($lgYol, (string) $resim->cover(1280, 720)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($ogYol, (string) $resim->cover(1200, 675)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($smYol, (string) $resim->cover(320, 180)->toWebp(quality: 80), 'public');

            HaberGorseli::updateOrCreate(
                ['haber_id' => $haber->id, 'sira' => $this->sira],
                [
                    'orijinal_yol' => $orijinalYol,
                    'lg_yol'       => $lgYol,
                    'og_yol'       => $ogYol,
                    'sm_yol'       => $smYol,
                ]
            );
        }
    }

    protected function etkinlikGorselleriniIsle(): void
    {
        $etkinlik = Etkinlik::query()->find($this->modelId);

        if (! $etkinlik) {
            return;
        }

        $slug = $etkinlik->slug ?: 'etkinlik-' . $etkinlik->id;
        $geciciTamYol = Storage::disk('local')->path($this->geciciYol);

        $manager = ImageManager::imagick();
        $resim = $manager->read($geciciTamYol);

        $oriDizin = "img26/ori/etkinlikler/{$etkinlik->id}";
        $optDizin = "img26/opt/etkinlikler/{$etkinlik->id}";

        if ($this->gorselTipi === 'ana_gorsel') {
            $orijinalYol = "{$oriDizin}/{$slug}-ana-orijinal.jpeg";
            $lgYol = "{$optDizin}/{$slug}-ana-lg.webp";
            $ogYol = "{$optDizin}/{$slug}-ana-og.webp";
            $smYol = "{$optDizin}/{$slug}-ana-sm.webp";
            $mobilLgYol = "{$optDizin}/{$slug}-ana-mobil-lg.webp";

            Storage::disk('spaces')->put($orijinalYol, (string) $resim->toJpeg(quality: 90), 'public');
            Storage::disk('spaces')->put($lgYol, (string) $resim->cover(1280, 720)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($ogYol, (string) $resim->cover(1200, 675)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($smYol, (string) $resim->cover(320, 180)->toWebp(quality: 80), 'public');
            Storage::disk('spaces')->put($mobilLgYol, (string) $resim->cover(768, 432)->toWebp(quality: 85), 'public');

            $etkinlik->update([
                'gorsel_orijinal' => Storage::disk('spaces')->url($orijinalYol),
                'gorsel_lg' => Storage::disk('spaces')->url($lgYol),
                'gorsel_og' => Storage::disk('spaces')->url($ogYol),
                'gorsel_sm' => Storage::disk('spaces')->url($smYol),
                'gorsel_mobil_lg' => Storage::disk('spaces')->url($mobilLgYol),
            ]);

            return;
        }

        if ($this->gorselTipi === 'galeri_gorseli') {
            $siraNo = str_pad((string) $this->sira, 3, '0', STR_PAD_LEFT);

            $orijinalYol = "{$oriDizin}/galeri/{$slug}-{$siraNo}-orijinal.jpeg";
            $lgYol = "{$optDizin}/galeri/{$slug}-{$siraNo}-lg.webp";
            $ogYol = "{$optDizin}/galeri/{$slug}-{$siraNo}-og.webp";
            $smYol = "{$optDizin}/galeri/{$slug}-{$siraNo}-sm.webp";

            Storage::disk('spaces')->put($orijinalYol, (string) $resim->toJpeg(quality: 90), 'public');
            Storage::disk('spaces')->put($lgYol, (string) $resim->cover(1280, 720)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($ogYol, (string) $resim->cover(1200, 675)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($smYol, (string) $resim->cover(320, 180)->toWebp(quality: 80), 'public');

            EtkinlikGorseli::updateOrCreate(
                ['etkinlik_id' => $etkinlik->id, 'sira' => $this->sira],
                [
                    'orijinal_yol' => $orijinalYol,
                    'lg_yol' => $lgYol,
                    'og_yol' => $ogYol,
                    'sm_yol' => $smYol,
                ]
            );
        }
    }

    protected function kurumsalSayfaGorselleriniIsle(): void
    {
        $sayfa = KurumsalSayfa::query()->find($this->modelId);

        if (! $sayfa) {
            return;
        }

        $slug = $sayfa->slug ?: 'kurumsal-sayfa-' . $sayfa->id;
        $geciciTamYol = Storage::disk('local')->path($this->geciciYol);

        $manager = ImageManager::imagick();
        $resim = $manager->read($geciciTamYol);

        $oriDizin = "img26/ori/kurumsal/{$sayfa->id}";
        $optDizin = "img26/opt/kurumsal/{$sayfa->id}";

        if ($this->gorselTipi === 'ana_gorsel') {
            $orijinalYol = "{$oriDizin}/{$slug}-orijinal.jpeg";
            $lgYol = "{$optDizin}/{$slug}-lg.webp";
            $ogYol = "{$optDizin}/{$slug}-og.webp";
            $smYol = "{$optDizin}/{$slug}-sm.webp";

            Storage::disk('spaces')->put($orijinalYol, (string) $resim->toJpeg(quality: 90), 'public');
            Storage::disk('spaces')->put($lgYol, (string) $resim->cover(1280, 720)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($ogYol, (string) $resim->cover(1200, 675)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($smYol, (string) $resim->cover(320, 180)->toWebp(quality: 80), 'public');

            $sayfa->update([
                'gorsel_orijinal' => Storage::disk('spaces')->url($orijinalYol),
                'gorsel_lg' => Storage::disk('spaces')->url($lgYol),
                'gorsel_og' => Storage::disk('spaces')->url($ogYol),
                'gorsel_sm' => Storage::disk('spaces')->url($smYol),
            ]);

            return;
        }

        if ($this->gorselTipi === 'banner_masaustu') {
            $bannerOrijinalYol = "{$oriDizin}/{$slug}-banner-orijinal.jpeg";
            $bannerLgYol = "{$optDizin}/{$slug}-banner-lg.webp";

            Storage::disk('spaces')->put($bannerOrijinalYol, (string) $resim->toJpeg(quality: 90), 'public');
            Storage::disk('spaces')->put($bannerLgYol, (string) $resim->cover(1920, 1080)->toWebp(quality: 85), 'public');

            $sayfa->update([
                'banner_orijinal' => Storage::disk('spaces')->url($bannerOrijinalYol),
                'banner_masaustu' => Storage::disk('spaces')->url($bannerLgYol),
                'banner_mobil' => $sayfa->banner_mobil ?: Storage::disk('spaces')->url($bannerLgYol),
            ]);

            return;
        }

        if ($this->gorselTipi === 'banner_mobil') {
            $bannerOrijinalYol = "{$oriDizin}/{$slug}-banner-mobil-orijinal.jpeg";
            $bannerMobilYol = "{$optDizin}/{$slug}-banner-mobil.webp";

            Storage::disk('spaces')->put($bannerOrijinalYol, (string) $resim->toJpeg(quality: 90), 'public');
            Storage::disk('spaces')->put($bannerMobilYol, (string) $resim->cover(768, 432)->toWebp(quality: 85), 'public');

            $sayfa->update([
                'banner_orijinal' => Storage::disk('spaces')->url($bannerOrijinalYol),
                'banner_mobil' => Storage::disk('spaces')->url($bannerMobilYol),
            ]);

            return;
        }

        if ($this->gorselTipi === 'og_gorsel') {
            $ogYol = "{$optDizin}/{$slug}-ozel-og.webp";

            Storage::disk('spaces')->put($ogYol, (string) $resim->cover(1200, 675)->toWebp(quality: 85), 'public');

            $sayfa->update([
                'og_gorsel' => Storage::disk('spaces')->url($ogYol),
            ]);

            return;
        }

        if ($this->gorselTipi === 'galeri_gorseli') {
            $siraNo = str_pad((string) $this->sira, 3, '0', STR_PAD_LEFT);

            $orijinalYol = "{$oriDizin}/{$slug}-galeri-{$siraNo}-orijinal.jpeg";
            $lgYol = "{$optDizin}/{$slug}-galeri-{$siraNo}-lg.webp";
            $ogYol = "{$optDizin}/{$slug}-galeri-{$siraNo}-og.webp";
            $smYol = "{$optDizin}/{$slug}-galeri-{$siraNo}-sm.webp";

            Storage::disk('spaces')->put($orijinalYol, (string) $resim->toJpeg(quality: 90), 'public');
            Storage::disk('spaces')->put($lgYol, (string) $resim->cover(1280, 720)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($ogYol, (string) $resim->cover(1200, 675)->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($smYol, (string) $resim->cover(320, 180)->toWebp(quality: 80), 'public');

            KurumsalSayfaGorseli::updateOrCreate(
                ['sayfa_id' => $sayfa->id, 'sira' => $this->sira],
                [
                    'orijinal_yol' => $orijinalYol,
                    'lg_yol' => $lgYol,
                    'og_yol' => $ogYol,
                    'sm_yol' => $smYol,
                ]
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        activity('gorsel_optimizasyon_hata')
            ->withProperties([
                'model_id'    => $this->modelId,
                'model_tipi'  => $this->modelTipi,
                'gorsel_tipi' => $this->gorselTipi,
                'hata'        => $exception->getMessage(),
            ])
            ->log('Görsel optimize job başarısız oldu');
    }
}

