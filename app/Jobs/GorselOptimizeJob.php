<?php

namespace App\Jobs;

use App\Models\Haber;
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

    public function __construct(
        public int $haberId,
        public array $kaynakYollar,
    ) {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function handle(): void
    {
        $haber = Haber::query()->find($this->haberId);

        if (! $haber) {
            return;
        }

        $slug = $haber->slug ?: 'haber-' . $haber->id;

        $oriDizin = 'img26/ori/haberler/' . $slug;
        $optDizin = 'img26/opt/haberler/' . $slug;

        $manager = ImageManager::imagick();
        $kaynaklar = array_values(array_filter($this->kaynakYollar));

        foreach ($kaynaklar as $sira => $kaynakYol) {
            $orijinalTamYol = Storage::disk('local')->path($kaynakYol);
            $resim = $manager->read($orijinalTamYol);

            $lg = (string) $resim->cover(1280, 720, 'center')->toWebp(quality: 85);
            $og = (string) $resim->cover(1200, 675, 'center')->toWebp(quality: 85);
            $sm = (string) $resim->cover(320, 180, 'center')->toWebp(quality: 80);
            $mobilLg = (string) $resim->cover(768, 432, 'center')->toWebp(quality: 85);

            $orijinalIcerik = Storage::disk('local')->get($kaynakYol);
            $uzanti = pathinfo($kaynakYol, PATHINFO_EXTENSION) ?: 'jpg';
            $siraNo = str_pad((string) ($sira + 1), 3, '0', STR_PAD_LEFT);

            $orijinalYol = $oriDizin . '/' . $siraNo . '-orijinal.' . $uzanti;
            $lgYol = $optDizin . '/' . $siraNo . '-lg.webp';
            $ogYol = $optDizin . '/' . $siraNo . '-og.webp';
            $smYol = $optDizin . '/' . $siraNo . '-sm.webp';
            $mobilLgYol = $optDizin . '/' . $siraNo . '-mobil-lg.webp';

            Storage::disk('spaces')->put($orijinalYol, $orijinalIcerik, 'public');
            Storage::disk('spaces')->put($lgYol, $lg, 'public');
            Storage::disk('spaces')->put($ogYol, $og, 'public');
            Storage::disk('spaces')->put($smYol, $sm, 'public');
            Storage::disk('spaces')->put($mobilLgYol, $mobilLg, 'public');

            if ($sira === 0) {
                // İlk yüklenen görsel haberin ana görseli olarak kaydedilir.
                $haber->update([
                    'gorsel_orijinal' => Storage::disk('spaces')->url($orijinalYol),
                    'gorsel_lg' => Storage::disk('spaces')->url($lgYol),
                    'gorsel_og' => Storage::disk('spaces')->url($ogYol),
                    'gorsel_sm' => Storage::disk('spaces')->url($smYol),
                    'gorsel_mobil_lg' => Storage::disk('spaces')->url($mobilLgYol),
                ]);
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        activity('gorsel_optimizasyon_hata')
            ->withProperties([
                'haber_id' => $this->haberId,
                'hata' => $exception->getMessage(),
            ])
            ->log('Görsel optimize job başarısız oldu');
    }
}
