<?php

namespace App\Filament\Resources\EkayitKayitResource\Pages;

use App\Filament\Resources\EkayitKayitResource;
use App\Models\EkayitDonem;
use App\Models\EkayitSinif;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEkayitKayit extends ListRecords
{
    protected static string $resource = EkayitKayitResource::class;

    public function getTabs(): array
    {
        $donem = EkayitDonem::aktifDonem() ?? EkayitDonem::orderByDesc('baslangic')->first();

        $tabs = ['tumu' => Tab::make('Tümü')];

        if ($donem) {
            $sinifler = EkayitSinif::where('donem_id', $donem->id)
                ->where('aktif', true)
                ->orderBy('ad')
                ->get();

            foreach ($sinifler as $sinif) {
                $sinifId = $sinif->id;
                $tabs["sinif-{$sinifId}"] = Tab::make($sinif->ad)
                    ->badge(fn () => \App\Models\EkayitKayit::where('sinif_id', $sinifId)->count())
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('sinif_id', $sinifId));
            }
        }

        return $tabs;
    }
}
