<?php

namespace App\Filament\Resources;

use App\Enums\KurbanBildirimDurumu;
use App\Enums\KurbanDurumu;
use App\Filament\Resources\BagisResource;
use App\Filament\Resources\KurbanKayitResource\Pages;
use App\Models\KurbanKayit;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KurbanKayitResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = KurbanKayit::class;

    protected static ?string $navigationIcon = 'heroicon-o-scissors';

    protected static ?string $navigationLabel = 'Kurbanlar';

    protected static ?string $modelLabel = 'Kurban';

    protected static ?string $pluralModelLabel = 'Kurbanlar';

    protected static ?string $navigationGroup = 'Bağış Yönetimi';

    protected static ?int $navigationSort = 30;

    public static function canViewAny(): bool
    {
        return static::izinVarMi('kurban.listele');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return static::izinVarMi('kurban.duzenle');
    }

    public static function canDelete($record): bool
    {
        return static::izinVarMi('kurban.sil');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kurban_no')
                    ->label('Kurban No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bagis_turu_adi')
                    ->label('Bağış Türü')
                    ->badge()
                    ->sortable(),
                TextColumn::make('sahip_ozeti')
                    ->label('Sahip / Hissedarlar')
                    ->state(fn (KurbanKayit $record) => $record->sahiplerOzeti())
                    ->wrap(),
                TextColumn::make('hisse_sayisi')
                    ->label('Hisse')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ($state instanceof KurbanDurumu ? $state : KurbanDurumu::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->color(fn ($state) => ($state instanceof KurbanDurumu ? $state : KurbanDurumu::tryFrom((string) $state))?->renk() ?? 'gray'),
                TextColumn::make('bildirim_durumu')
                    ->label('Bildirim')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ($state instanceof KurbanBildirimDurumu ? $state : KurbanBildirimDurumu::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->color(fn ($state) => ($state instanceof KurbanBildirimDurumu ? $state : KurbanBildirimDurumu::tryFrom((string) $state))?->renk() ?? 'gray'),
                TextColumn::make('bagis.bagis_no')
                    ->label('Bağış No')
                    ->searchable()
                    ->url(fn (KurbanKayit $record) => BagisResource::getUrl('view', ['record' => $record->bagis_id]))
                    ->openUrlInNewTab(),
            ])
            ->filters([
                SelectFilter::make('bagis_ozelligi')
                    ->label('Özellik')
                    ->options([
                        'kucukbas_kurban' => 'Küçükbaş',
                        'buyukbas_kurban' => 'Büyükbaş',
                    ]),
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options([
                        KurbanDurumu::Bekliyor->value => KurbanDurumu::Bekliyor->label(),
                        KurbanDurumu::Kesildi->value => KurbanDurumu::Kesildi->label(),
                    ]),
                SelectFilter::make('bildirim_durumu')
                    ->label('Bildirim Durumu')
                    ->options([
                        KurbanBildirimDurumu::Gonderilmedi->value => KurbanBildirimDurumu::Gonderilmedi->label(),
                        KurbanBildirimDurumu::Kismi->value => KurbanBildirimDurumu::Kismi->label(),
                        KurbanBildirimDurumu::Tamamlandi->value => KurbanBildirimDurumu::Tamamlandi->label(),
                    ]),
                Filter::make('tarih_araligi')
                    ->label('Bugün')
                    ->query(fn ($query) => $query->whereDate('created_at', now()->toDateString())),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKurbanKayitlar::route('/'),
            'view' => Pages\ViewKurbanKayit::route('/{record}'),
        ];
    }
}