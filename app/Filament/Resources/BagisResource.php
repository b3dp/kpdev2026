<?php

namespace App\Filament\Resources;

use App\Enums\BagisDurumu;
use App\Filament\Resources\BagisResource\Pages;
use App\Filament\Widgets\BagisIstatistikWidget;
use App\Models\Bagis;
use App\Models\BagisTuru;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BagisResource extends Resource
{
    protected static ?string $model = Bagis::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Bağışlar';

    protected static ?string $modelLabel = 'Bağış';

    protected static ?string $pluralModelLabel = 'Bağışlar';

    protected static ?string $navigationGroup = 'Bağış Yönetimi';

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Muhasebe']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bagis_no')
                    ->label('Bağış No')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('bagis_no', 'like', "%{$search}%")
                            ->orWhereHas('kisiler', fn (Builder $q) => $q->where('ad_soyad', 'like', "%{$search}%"));
                    })
                    ->sortable(),
                TextColumn::make('bagis_turleri')
                    ->label('Bağış Türleri')
                    ->state(fn (Bagis $record) => $record->kalemler->pluck('bagisTuru.ad')->filter()->implode(', ')),
                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        BagisDurumu::Odendi->value => 'success',
                        BagisDurumu::Hatali->value => 'danger',
                        BagisDurumu::Iptal->value => 'gray',
                        BagisDurumu::TerkEdildi->value => 'warning',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state) => BagisDurumu::from($state)->label())
                    ->sortable(),
                TextColumn::make('toplam_tutar')->label('Tutar')->money('TRY')->sortable(),
                TextColumn::make('sahip_tipi')
                    ->label('Sahip Tipi')
                    ->state(fn (Bagis $record) => $record->kalemler->first()?->sahip_tipi === 'baskasi' ? 'Başkası Adına' : 'Kendi'),
                TextColumn::make('bagisci_adi')
                    ->label('Bağışçı Adı')
                    ->state(fn (Bagis $record) => $record->kisiler->firstWhere('ad_soyad')?->ad_soyad),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('bagis_turu_id')
                    ->label('Bağış Türü')
                    ->multiple()
                    ->options(fn () => BagisTuru::query()->orderBy('ad')->pluck('ad', 'id')->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $degerler = $data['values'] ?? [];

                        if ($degerler === []) {
                            return $query;
                        }

                        return $query->whereHas('kalemler', fn (Builder $q) => $q->whereIn('bagis_turu_id', $degerler));
                    }),
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->multiple()
                    ->options(BagisDurumu::secenekler()),
                SelectFilter::make('sahip_tipi')
                    ->label('Sahip Tipi')
                    ->options([
                        'kendi' => 'Kendi Adına',
                        'baskasi' => 'Başkası Adına',
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn (Builder $q, $deger) => $q->whereHas('kalemler', fn (Builder $k) => $k->where('sahip_tipi', $deger)))),
                Filter::make('tarih_araligi')
                    ->label('Tarih Aralığı')
                    ->form([
                        DatePicker::make('baslangic')->label('Başlangıç'),
                        DatePicker::make('bitis')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['baslangic'] ?? null, fn (Builder $q, $deger) => $q->whereDate('created_at', '>=', $deger))
                        ->when($data['bitis'] ?? null, fn (Builder $q, $deger) => $q->whereDate('created_at', '<=', $deger))),
            ])
            ->headerActions([
                Action::make('excel_raporu_al')
                    ->label('Excel Raporu Al')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->form([
                        Radio::make('periyot')
                            ->label('Periyot')
                            ->options([
                                'bugun' => 'Bugün',
                                'dun' => 'Dün',
                                'bu_ay' => 'Bu Ay',
                                'gecen_ay' => 'Geçen Ay',
                                'bu_yil' => 'Bu Yıl',
                                'gecen_yil' => 'Geçtiğimiz Yıl',
                                'ozel' => 'Özel Tarih',
                            ])->default('bugun')->required()->live(),
                        DatePicker::make('ozel_baslangic')->label('Özel Başlangıç')->visible(fn ($get) => $get('periyot') === 'ozel'),
                        DatePicker::make('ozel_bitis')->label('Özel Bitiş')->visible(fn ($get) => $get('periyot') === 'ozel'),
                    ])
                    ->action(function (): void {
                        Notification::make()
                            ->title('Excel raporu bu fazda servis entegrasyonu sonrası aktif edilecek.')
                            ->warning()
                            ->send();
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getWidgets(): array
    {
        return [
            BagisIstatistikWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBagis::route('/'),
            'view' => Pages\ViewBagis::route('/{record}'),
        ];
    }
}
