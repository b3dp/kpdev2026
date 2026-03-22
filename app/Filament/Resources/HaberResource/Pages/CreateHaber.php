<?php

namespace App\Filament\Resources\HaberResource\Pages;

use App\Enums\HaberDurumu;
use App\Filament\Resources\HaberResource;
use App\Jobs\AiHaberIsleJob;
use App\Jobs\GorselOptimizeJob;
use App\Jobs\OnayEpostasiGonderJob;
use Filament\Resources\Pages\CreateRecord;

class CreateHaber extends CreateRecord
{
    protected static string $resource = HaberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['yonetici_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $haber = $this->record;

        if (filled($haber->gorsel_orijinal) && str_starts_with((string) $haber->gorsel_orijinal, 'tmp/haberler/')) {
            GorselOptimizeJob::dispatch($haber->id, $haber->gorsel_orijinal);
        }

        if (data_get($this->data, 'ai_otomatik_tetikle')) {
            AiHaberIsleJob::dispatch($haber->id);
        }

        if ($haber->durum === HaberDurumu::Incelemede) {
            OnayEpostasiGonderJob::dispatch($haber->id);
        }
    }
}
