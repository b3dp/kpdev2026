<?php

namespace App\Filament\Resources\BagisTuruResource\Pages;

use App\Filament\Resources\BagisTuruResource;
use App\Services\BagisTuruGorselService;
use Filament\Resources\Pages\EditRecord;

class EditBagisTuru extends EditRecord
{
    protected static string $resource = BagisTuruResource::class;

    protected ?string $mevcutGorselKaynagi = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->mevcutGorselKaynagi = $this->record->gorsel_yatay
            ?? $this->record->gorsel_orijinal
            ?? $this->record->gorsel_kare
            ?? $this->record->gorsel_dikey;

        $data['gorsel_kare'] = null;
        $data['gorsel_dikey'] = null;
        $data['gorsel_orijinal'] = null;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        app(BagisTuruGorselService::class)->gorselYatayiniIsleVeKaydet($this->record, $this->data, $this->mevcutGorselKaynagi);
    }
}
