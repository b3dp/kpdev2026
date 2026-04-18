<?php

namespace App\Filament\Resources\HaberResource\Pages;

use App\Enums\HaberDurumu;
use App\Filament\Resources\HaberResource;
use App\Jobs\GorselOptimizeJob;
use App\Jobs\OnayEpostasiGonderJob;
use App\Models\HaberKategorisi;
use App\Services\HaberKategoriEslestirmeService;
use Filament\Resources\Pages\CreateRecord;

class CreateHaber extends CreateRecord
{
    protected static string $resource = HaberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['ek_kategori_idleri']);

        $data['yonetici_id'] = auth()->id();

        if (auth()->user()?->hasAnyRole(['Yazar', 'Halkla İlişkiler'])) {
            $data['durum'] = HaberDurumu::Taslak->value;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $haber = $this->record;

        $this->kategorileriKaydet();

        // Ana görsel
        $anaGorsel = data_get($this->data, 'ana_gorsel_gecici');
        $anaGorsel = is_array($anaGorsel) ? (array_values($anaGorsel)[0] ?? null) : $anaGorsel;
        if (filled($anaGorsel) && is_string($anaGorsel)) {
            dispatch_sync(new GorselOptimizeJob($haber->id, 'haber', 'ana_gorsel', $anaGorsel, 1));
        }

        // Galeri görselleri
        $galeriGorseller = array_values(array_filter((array) data_get($this->data, 'galeri_gorseller', [])));
        foreach ($galeriGorseller as $sira => $geciciYol) {
            dispatch_sync(new GorselOptimizeJob($haber->id, 'haber', 'galeri_gorseli', $geciciYol, $sira + 1));
        }

        if ($haber->durum === HaberDurumu::Incelemede) {
            OnayEpostasiGonderJob::dispatch($haber->id);
        }
    }

    private function kategorileriKaydet(): void
    {
        $haber = $this->record->fresh();
        $kategoriIdleri = collect([(int) ($this->data['kategori_id'] ?? 0)])
            ->merge((array) ($this->data['ek_kategori_idleri'] ?? []))
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($kategoriIdleri->isEmpty()) {
            return;
        }

        $kategoriler = HaberKategorisi::query()
            ->whereIn('id', $kategoriIdleri->all())
            ->get(['id', 'ad', 'slug']);

        $sonuclar = $kategoriIdleri
            ->map(function (int $kategoriId, int $index) use ($kategoriler): ?array {
                $kategori = $kategoriler->firstWhere('id', $kategoriId);

                if (! $kategori) {
                    return null;
                }

                return [
                    'id' => $kategori->id,
                    'ad' => $kategori->ad,
                    'slug' => $kategori->slug,
                    'skor' => $index === 0 ? 100 : 95,
                ];
            })
            ->filter()
            ->values()
            ->all();

        app(HaberKategoriEslestirmeService::class)->haberIcinKategorileriKaydet($haber, $sonuclar, 'manuel');
    }
}
