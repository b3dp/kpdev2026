<?php

namespace App\Filament\Widgets;

use App\Enums\EtkinlikDurumu;
use App\Models\Etkinlik;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class EtkinlikWidget extends TableWidget
{
    protected static ?string $heading = 'Yaklaşan Etkinlikler';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Etkinlik::query()
                    ->whereDate('baslangic_tarihi', '>=', today())
                    ->whereIn('durum', [EtkinlikDurumu::Taslak->value, EtkinlikDurumu::Yayinda->value])
                    ->orderBy('baslangic_tarihi')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('baslik')
                    ->label('Başlık')
                    ->limit(70)
                    ->tooltip(fn (Etkinlik $record) => $record->baslik),
                TextColumn::make('baslangic_tarihi')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i'),
                TextColumn::make('konum')
                    ->label('Konum')
                    ->state(fn (Etkinlik $record) => $record->konum_ad ?: 'Online'),
                TextColumn::make('kontenjan')
                    ->label('Kontenjan')
                    ->state(fn (Etkinlik $record) => ($record->kayitli_kisi ?? 0) . '/' . ($record->kontenjan ?? '-')),
            ])
            ->paginated(false);
    }
}
