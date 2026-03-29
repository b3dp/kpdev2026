<?php

namespace App\Filament\Resources\EkayitSinifResource\Pages;

use App\Filament\Resources\EkayitSinifResource;
use App\Jobs\GorselOptimizeJob;
use App\Services\SinifRenkService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateEkayitSinif extends CreateRecord
{
    protected static string $resource = EkayitSinifResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Renk seçilmediyse veya default 'blue' ise otomatik ata
        if (blank($data['renk'] ?? null) || $data['renk'] === 'blue') {
            $donemId = (int) ($data['donem_id'] ?? 0);
            if ($donemId > 0) {
                $data['renk'] = app(SinifRenkService::class)->sonrakiRenk($donemId);
            }
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $this->yuklenenGorselleriIsle();
    }

    protected function yuklenenGorselleriIsle(): void
    {
        if (! $this->record) {
            return;
        }

        $this->tekGorselIsle('tmp_gorsel_kare', '1x1');
        $this->tekGorselIsle('tmp_gorsel_dikey', '9x16');
        $this->tekGorselIsle('tmp_gorsel_yatay', '16x9');
    }

    protected function tekGorselIsle(string $alan, string $gorselTipi): void
    {
        $geciciYol = data_get($this->data, $alan);

        if (is_array($geciciYol)) {
            $geciciYol = reset($geciciYol) ?: null;
        }

        if (! is_string($geciciYol) || blank($geciciYol)) {
            return;
        }

        $geciciYol = ltrim($geciciYol, '/');

        if (! Storage::disk('local')->exists($geciciYol)) {
            return;
        }

        $slug = Str::slug((string) $this->record->ad);
        $uzanti = strtolower(pathinfo($geciciYol, PATHINFO_EXTENSION) ?: 'jpg');
        $uzanti = in_array($uzanti, ['jpg', 'jpeg', 'png', 'webp'], true) ? $uzanti : 'jpg';

        $orijinalYol = "img26/ori/ekayit/{$this->record->id}/{$slug}-original.{$uzanti}";

        Storage::disk('spaces')->put(
            self::spacesYolunuNormalizeEt($orijinalYol),
            Storage::disk('local')->get($geciciYol),
            'public'
        );

        $this->record->forceFill([
            'gorsel_orijinal' => $orijinalYol,
        ])->save();

        GorselOptimizeJob::dispatch(
            modelId: (int) $this->record->id,
            modelTipi: 'ekayit_sinif',
            gorselTipi: $gorselTipi,
            geciciYol: $geciciYol,
        );
    }

    protected static function spacesYolunuNormalizeEt(string $yol): string
    {
        $yol = ltrim($yol, '/');
        $kok = trim((string) config('filesystems.disks.spaces.root', ''), '/');

        if ($kok !== '' && str_starts_with($yol, $kok . '/')) {
            return substr($yol, strlen($kok) + 1);
        }

        return $yol;
    }
}
