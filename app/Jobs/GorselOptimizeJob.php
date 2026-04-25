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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        \Log::debug('[GorselOptimizeJob] Dosya yolu: ' . $geciciTamYol . ' - Var mı? ' . (file_exists($geciciTamYol) ? 'evet' : 'hayır'));
        try {
            $manager = ImageManager::imagick();
            \Log::debug('[GorselOptimizeJob] ImageManager oluşturuldu');
            $resim = $manager->read($geciciTamYol);
            \Log::debug('[GorselOptimizeJob] Görsel okundu: ' . $geciciTamYol);
        } catch (\Throwable $e) {
            \Log::error('[GorselOptimizeJob] Görsel okuma hatası: ' . $geciciTamYol . ' - ' . $e->getMessage());
            throw $e;
        }

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
            // SM crop işlemi iptal: Orijinalden orantılı küçültme (crop yok)
            $smResim = $resim->resize(320, 180, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::disk('spaces')->put($smYol, (string) $smResim->toWebp(quality: 80), 'public');
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

        $resim = $this->etkinlikGorseliniOku($this->geciciYol);

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

    private function etkinlikGorseliniOku(string $kaynak)
    {
        $manager = ImageManager::imagick();

        if (str_starts_with($kaynak, 'http://') || str_starts_with($kaynak, 'https://')) {
            $yanit = Http::timeout(15)->get($kaynak);

            if (! $yanit->ok() || blank($yanit->body())) {
                throw new \RuntimeException('Etkinlik görseli URL üzerinden okunamadı: ' . $kaynak);
            }

            return $manager->read($yanit->body());
        }

        $lokalTamYol = Storage::disk('local')->path($kaynak);
        if (is_file($lokalTamYol)) {
            return $manager->read($lokalTamYol);
        }

        if (Storage::disk('spaces')->exists($kaynak)) {
            $icerik = Storage::disk('spaces')->get($kaynak);

            if (blank($icerik)) {
                throw new \RuntimeException('Etkinlik görseli spaces diskten boş döndü: ' . $kaynak);
            }

            return $manager->read($icerik);
        }

        Log::warning('Etkinlik görsel kaynağı bulunamadı', [
            'model_id' => $this->modelId,
            'kaynak' => $kaynak,
            'lokal_tam_yol' => $lokalTamYol,
        ]);

        throw new \RuntimeException('Etkinlik görsel kaynağı bulunamadı: ' . $kaynak);
    }

    protected function kurumsalSayfaGorselleriniIsle(): void
    {
        $sayfa = KurumsalSayfa::query()->find($this->modelId);

        if (! $sayfa) {
            return;
        }

        $slug = $sayfa->slug ?: 'kurumsal-sayfa-' . $sayfa->id;
        $uzanti = strtolower(pathinfo($this->geciciYol, PATHINFO_EXTENSION) ?: 'jpeg');
        $orijinalIcerik = Storage::disk('local')->get($this->geciciYol);

        $manager = ImageManager::imagick();
        $resim = $manager->read($orijinalIcerik);

        $oriDizin = "img26/ori/kurumsal/{$sayfa->id}";
        $optDizin = "img26/opt/kurumsal/{$sayfa->id}";

        if ($this->gorselTipi === 'ana_gorsel') {
            $orijinalYol = "{$oriDizin}/{$slug}-orijinal.{$uzanti}";
            $lgYol = "{$optDizin}/{$slug}-lg.webp";
            $ogYol = "{$optDizin}/{$slug}-og.webp";
            $smYol = "{$optDizin}/{$slug}-sm.webp";

            Storage::disk('spaces')->put($orijinalYol, $orijinalIcerik, 'public');
            Storage::disk('spaces')->put($lgYol, (string) $resim->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($ogYol, (string) $resim->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($smYol, (string) $resim->toWebp(quality: 80), 'public');

            $sayfa->update([
                'gorsel_orijinal' => Storage::disk('spaces')->url($orijinalYol),
                'gorsel_lg' => Storage::disk('spaces')->url($lgYol),
                'gorsel_og' => Storage::disk('spaces')->url($ogYol),
                'gorsel_sm' => Storage::disk('spaces')->url($smYol),
            ]);

            return;
        }

        if ($this->gorselTipi === 'banner_masaustu') {
            $bannerOrijinalYol = "{$oriDizin}/{$slug}-banner-orijinal.{$uzanti}";
            $bannerMasaustuWebpYol = "{$optDizin}/{$slug}-banner-masaustu.webp";

            Storage::disk('spaces')->put($bannerOrijinalYol, $orijinalIcerik, 'public');
            Storage::disk('spaces')->put($bannerMasaustuWebpYol, (string) $resim->toWebp(quality: 85), 'public');

            $sayfa->update([
                'banner_orijinal' => Storage::disk('spaces')->url($bannerOrijinalYol),
                'banner_masaustu' => Storage::disk('spaces')->url($bannerMasaustuWebpYol),
                'banner_mobil' => $sayfa->banner_mobil ?: Storage::disk('spaces')->url($bannerMasaustuWebpYol),
            ]);

            return;
        }

        if ($this->gorselTipi === 'banner_mobil') {
            $bannerOrijinalYol = "{$oriDizin}/{$slug}-banner-mobil-orijinal.{$uzanti}";
            $bannerMobilWebpYol = "{$optDizin}/{$slug}-banner-mobil.webp";

            Storage::disk('spaces')->put($bannerOrijinalYol, $orijinalIcerik, 'public');
            Storage::disk('spaces')->put($bannerMobilWebpYol, (string) $resim->toWebp(quality: 85), 'public');

            $sayfa->update([
                'banner_orijinal' => Storage::disk('spaces')->url($bannerOrijinalYol),
                'banner_mobil' => Storage::disk('spaces')->url($bannerMobilWebpYol),
            ]);

            return;
        }

        if ($this->gorselTipi === 'og_gorsel') {
            $ogYol = "{$oriDizin}/{$slug}-ozel-og.{$uzanti}";
            $ogWebpYol = "{$optDizin}/{$slug}-ozel-og.webp";

            Storage::disk('spaces')->put($ogYol, $orijinalIcerik, 'public');
            Storage::disk('spaces')->put($ogWebpYol, (string) $resim->toWebp(quality: 85), 'public');

            $sayfa->update([
                'og_gorsel' => Storage::disk('spaces')->url($ogWebpYol),
            ]);

            return;
        }

        if ($this->gorselTipi === 'galeri_gorseli') {
            $siraNo = str_pad((string) $this->sira, 3, '0', STR_PAD_LEFT);

            $orijinalYol = "{$oriDizin}/{$slug}-galeri-{$siraNo}-orijinal.{$uzanti}";
            $lgYol = "{$optDizin}/{$slug}-galeri-{$siraNo}-lg.webp";
            $ogYol = "{$optDizin}/{$slug}-galeri-{$siraNo}-og.webp";
            $smYol = "{$optDizin}/{$slug}-galeri-{$siraNo}-sm.webp";

            Storage::disk('spaces')->put($orijinalYol, $orijinalIcerik, 'public');
            Storage::disk('spaces')->put($lgYol, (string) $resim->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($ogYol, (string) $resim->toWebp(quality: 85), 'public');
            Storage::disk('spaces')->put($smYol, (string) $resim->toWebp(quality: 80), 'public');

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

