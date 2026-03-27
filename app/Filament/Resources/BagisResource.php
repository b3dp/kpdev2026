<?php

namespace App\Filament\Resources;

use App\Enums\BagisDurumu;
use App\Exports\BagisExport;
use App\Filament\Resources\BagisResource\Pages;
use App\Filament\Widgets\BagisIstatistikWidget;
use App\Models\Bagis;
use App\Models\BagisTuru;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('durum')
                ->label('Bağış Durumu')
                ->options([
                    BagisDurumu::Beklemede->value => BagisDurumu::Beklemede->label(),
                    BagisDurumu::Odendi->value => BagisDurumu::Odendi->label(),
                    BagisDurumu::Hatali->value => BagisDurumu::Hatali->label(),
                    BagisDurumu::Iptal->value => BagisDurumu::Iptal->label(),
                ])
                ->required(),
            TextInput::make('odeme_referans')
                ->label('Ödeme Referans Numarası')
                ->maxLength(255),
            Toggle::make('makbuz_gonderildi')
                ->label('Makbuz Gönderildi'),
            Toggle::make('kurban_aktarildi')
                ->label('Kurban Aktarıldı'),
        ]);
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
                    ->color(function ($state): string {
                        $durum = $state instanceof BagisDurumu ? $state : BagisDurumu::tryFrom((string) $state);

                        return match ($durum) {
                            BagisDurumu::Odendi => 'success',
                            BagisDurumu::Hatali => 'danger',
                            BagisDurumu::Iptal => 'gray',
                            BagisDurumu::TerkEdildi => 'warning',
                            default => 'primary',
                        };
                    })
                    ->formatStateUsing(function ($state): string {
                        $durum = $state instanceof BagisDurumu ? $state : BagisDurumu::tryFrom((string) $state);

                        return $durum?->label() ?? 'Bilinmiyor';
                    })
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
                            ->options(self::excelPeriyotSecenekleri())
                            ->default('bugun')
                            ->required()
                            ->live(),
                        DatePicker::make('ozel_baslangic')->label('Özel Başlangıç')->visible(fn ($get) => $get('periyot') === 'ozel'),
                        DatePicker::make('ozel_bitis')->label('Özel Bitiş')->visible(fn ($get) => $get('periyot') === 'ozel'),
                    ])
                    ->action(function (array $data): StreamedResponse {
                        [$baslangic, $bitis] = self::excelTarihAraligi($data);

                        $bagislar = Bagis::query()
                            ->with(['kalemler.bagisTuru', 'kisiler'])
                            ->where('durum', BagisDurumu::Odendi->value)
                            ->whereBetween('odeme_tarihi', [$baslangic, $bitis])
                            ->orderByDesc('odeme_tarihi')
                            ->get();

                        $dosyaAdi = sprintf('bagis-%s-%s.xlsx', $baslangic->format('dmY'), $bitis->format('dmY'));

                        return (new BagisExport($bagislar))->download($dosyaAdi);
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
            'edit' => Pages\EditBagis::route('/{record}/edit'),
        ];
    }

    protected static function excelPeriyotSecenekleri(): array
    {
        $simdi = Carbon::now();

        return [
            'bugun' => 'Bugün ('.$simdi->format('d/m/y D').')',
            'dun' => 'Dün ('.$simdi->copy()->subDay()->format('d/m/y D').')',
            'bu_ay' => 'Bu Ay ('.$simdi->translatedFormat('F y').')',
            'gecen_ay' => 'Geçen Ay ('.$simdi->copy()->subMonthNoOverflow()->translatedFormat('F y').')',
            'bu_yil' => 'Bu Yıl ('.$simdi->format('Y').')',
            'gecen_yil' => 'Geçtiğimiz Yıl ('.$simdi->copy()->subYear()->format('Y').')',
            'ozel' => 'Özel Tarih',
        ];
    }

    protected static function excelTarihAraligi(array $data): array
    {
        $simdi = Carbon::now();

        return match ($data['periyot'] ?? 'bugun') {
            'bugun' => [$simdi->copy()->startOfDay(), $simdi->copy()->endOfDay()],
            'dun' => [$simdi->copy()->subDay()->startOfDay(), $simdi->copy()->subDay()->endOfDay()],
            'bu_ay' => [$simdi->copy()->startOfMonth(), $simdi->copy()->endOfDay()],
            'gecen_ay' => [$simdi->copy()->subMonthNoOverflow()->startOfMonth(), $simdi->copy()->subMonthNoOverflow()->endOfMonth()],
            'bu_yil' => [$simdi->copy()->startOfYear(), $simdi->copy()->endOfDay()],
            'gecen_yil' => [$simdi->copy()->subYear()->startOfYear(), $simdi->copy()->subYear()->endOfYear()],
            'ozel' => [
                Carbon::parse($data['ozel_baslangic'] ?? $simdi->toDateString())->startOfDay(),
                Carbon::parse($data['ozel_bitis'] ?? $data['ozel_baslangic'] ?? $simdi->toDateString())->endOfDay(),
            ],
            default => [$simdi->copy()->startOfDay(), $simdi->copy()->endOfDay()],
        };
    }
}
