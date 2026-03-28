<?php

namespace App\Filament\Resources\EkayitSinifResource\Pages;

use App\Filament\Resources\EkayitSinifResource;
use App\Jobs\GorselOptimizeJob;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEkayitSinif extends EditRecord
{
    protected static string $resource = EkayitSinifResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        
        if ($record) {
            $cdnUrl = config('filesystems.disks.spaces.url');
            if ($record->gorsel_kare) {
                $data['gorsel_kare_display'] = "$cdnUrl/{$record->gorsel_kare}";
            }
            if ($record->gorsel_dikey) {
                $data['gorsel_dikey_display'] = "$cdnUrl/{$record->gorsel_dikey}";
            }
            if ($record->gorsel_yatay) {
                $data['gorsel_yatay_display'] = "$cdnUrl/{$record->gorsel_yatay}";
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;
        if (!$record) {
            return $data;
        }

        // tmp_gorsel_kare uploaded
        if (!empty($data['tmp_gorsel_kare'])) {
            $tmpYol = trim($data['tmp_gorsel_kare'], '/');
            GorselOptimizeJob::dispatch(
                modelId: $record->id,
                modelTipi: 'ekayit_sinif',
                gorselTipi: '1x1',
                geciciYol: $tmpYol
            );
            unset($data['tmp_gorsel_kare']);
        }

        // tmp_gorsel_dikey uploaded
        if (!empty($data['tmp_gorsel_dikey'])) {
            $tmpYol = trim($data['tmp_gorsel_dikey'], '/');
            GorselOptimizeJob::dispatch(
                modelId: $record->id,
                modelTipi: 'ekayit_sinif',
                gorselTipi: '9x16',
                geciciYol: $tmpYol
            );
            unset($data['tmp_gorsel_dikey']);
        }

        // tmp_gorsel_yatay uploaded
        if (!empty($data['tmp_gorsel_yatay'])) {
            $tmpYol = trim($data['tmp_gorsel_yatay'], '/');
            GorselOptimizeJob::dispatch(
                modelId: $record->id,
                modelTipi: 'ekayit_sinif',
                gorselTipi: '16x9',
                geciciYol: $tmpYol
            );
            unset($data['tmp_gorsel_yatay']);
        }

        return $data;
    }
}
