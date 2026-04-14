<?php

namespace App\Filament\Resources;

use App\Enums\EkayitDurumu;
use App\Exports\EkayitExport;
use App\Filament\Resources\EkayitKayitResource\Pages;
use App\Models\EkayitDonem;
use App\Models\EkayitKayit;
use App\Models\EkayitSinif;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EkayitKayitResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = EkayitKayit::class;
    protected static ?string $navigationIcon    = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel   = 'Kayıtlar';
    protected static ?string $modelLabel        = 'Kayıt';
    protected static ?string $pluralModelLabel  = 'Kayıtlar';
    protected static ?string $navigationGroup   = 'E-Kayıt';
    protected static ?int    $navigationSort    = 20;

    public static function canViewAny(): bool
    {
        return static::izinVarMi('ekayit.listele');
    }

    public static function canCreate(): bool
    {
        return static::izinVarMi('ekayit.listele');
    }

    public static function canEdit($record): bool
    {
        return static::izinlerdenBiriVarMi(['ekayit.durum_guncelle', 'ekayit.listele']);
    }

    public static function canDelete($record): bool
    {
        return static::izinVarMi('ekayit.sil');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                EkayitKayit::query()
                    ->with(['sinif.donem', 'ogrenciBilgisi', 'veliBilgisi'])
            )
            ->columns([
                TextColumn::make('ogrenciBilgisi.ad_soyad')
                    ->label('Öğrenci Adı')->searchable(query: function (Builder $q, string $s): Builder {
                        return $q->whereHas('ogrenciBilgisi', fn (Builder $oq) => $oq
                            ->where('ad_soyad', 'like', "%{$s}%")
                            ->orWhere('tc_kimlik', 'like', "%{$s}%"));
                    })->sortable(false),

                TextColumn::make('ogrenciBilgisi.tc_kimlik')
                    ->label('TC')
                    ->formatStateUsing(function (?string $state): string {
                        if (blank($state) || mb_strlen($state) < 6) return (string) $state;
                        return mb_substr($state, 0, 3).'****'.mb_substr($state, -3);
                    }),

                TextColumn::make('sinif.ad')
                    ->label('Sınıf')
                    ->badge()
                    ->color(fn (EkayitKayit $record): string => $record->sinif?->renk ?? 'gray')
                    ->sortable(),

                TextColumn::make('veliBilgisi.ad_soyad')
                    ->label('Veli Adı')
                    ->searchable(query: function (Builder $q, string $s): Builder {
                        return $q->whereHas('veliBilgisi', fn (Builder $vq) => $vq
                            ->where('ad_soyad', 'like', "%{$s}%")
                            ->orWhere('telefon_1', 'like', "%{$s}%"));
                    })->sortable(false),

                TextColumn::make('veliBilgisi.telefon_1')
                    ->label('Veli Tel')
                    ->formatStateUsing(fn (?string $state, EkayitKayit $record): string => collect([
                        match ($record->veliBilgisi?->telefon_1_sahibi) {
                            'anne' => 'Anne',
                            'baba' => 'Baba',
                            'yakini' => 'Yakını',
                            default => null,
                        },
                        $state,
                    ])->filter()->implode(': ')),

                TextColumn::make('durum')->label('Durum')->badge()
                    ->formatStateUsing(function ($state): string {
                        $d = $state instanceof EkayitDurumu ? $state : EkayitDurumu::tryFrom((string) $state);
                        return $d?->label() ?? (string) $state;
                    })
                    ->color(function ($state): string {
                        $d = $state instanceof EkayitDurumu ? $state : EkayitDurumu::tryFrom((string) $state);
                        return $d?->renk() ?? 'gray';
                    })->sortable(),

                TextColumn::make('created_at')->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('donem_id')->label('Dönem')
                    ->options(fn () => EkayitDonem::orderByDesc('baslangic')->pluck('ad', 'id')->all())
                    ->query(function (Builder $q, array $data): Builder {
                        $donemId = $data['value'] ?? null;
                        if (! $donemId) return $q;
                        return $q->whereHas('sinif', fn (Builder $s) => $s->where('donem_id', $donemId));
                    }),
                SelectFilter::make('sinif_id')->label('Sınıf')->multiple()
                    ->options(fn () => EkayitSinif::orderBy('ad')->pluck('ad', 'id')->all()),
                SelectFilter::make('durum')->label('Durum')->multiple()
                    ->options(EkayitDurumu::secenekler()),
                Filter::make('tarih_araligi')->label('Tarih Aralığı')
                    ->form([
                        DatePicker::make('baslangic')->label('Başlangıç'),
                        DatePicker::make('bitis')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['baslangic'] ?? null, fn (Builder $q2, $d) => $q2->whereDate('created_at', '>=', $d))
                        ->when($data['bitis'] ?? null, fn (Builder $q2, $d) => $q2->whereDate('created_at', '<=', $d))),
            ])
            ->headerActions([
                Action::make('excel_indir')->label('Excel İndir')
                    ->icon('heroicon-o-arrow-down-tray')->color('primary')
                    ->form([
                        Select::make('donem_id')->label('Dönem')->required()
                            ->options(fn () => EkayitDonem::orderByDesc('baslangic')->pluck('ad', 'id')->all()),
                        Select::make('sinif_ids')->label('Sınıf (çoklu)')->multiple()
                            ->options(fn () => EkayitSinif::orderBy('ad')->pluck('ad', 'id')->all()),
                        Select::make('durum')->label('Durum (çoklu)')->multiple()
                            ->options(EkayitDurumu::secenekler()),
                        DatePicker::make('baslangic')->label('Başlangıç Tarihi'),
                        DatePicker::make('bitis')->label('Bitiş Tarihi'),
                    ])
                    ->action(function (array $data): StreamedResponse {
                        $donemAd = EkayitDonem::find($data['donem_id'])?->ad ?? 'donem';
                        $dosyaAdi = 'ekayit-'.str($donemAd)->slug().'-'.now()->format('Ymd').'.xlsx';

                        $query = EkayitKayit::query()
                            ->with(['sinif.donem', 'ogrenciBilgisi', 'veliBilgisi'])
                            ->whereHas('sinif', fn (Builder $q) => $q->where('donem_id', $data['donem_id']));

                        if (! empty($data['sinif_ids'])) {
                            $query->whereIn('sinif_id', $data['sinif_ids']);
                        }
                        if (! empty($data['durum'])) {
                            $query->whereIn('durum', $data['durum']);
                        }
                        if (! empty($data['baslangic'])) {
                            $query->whereDate('created_at', '>=', $data['baslangic']);
                        }
                        if (! empty($data['bitis'])) {
                            $query->whereDate('created_at', '<=', $data['bitis']);
                        }

                        return (new EkayitExport($query->get()))->download($dosyaAdi);
                    }),
            ])
            ->actions([
                ViewAction::make(),
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEkayitKayit::route('/'),
            'create' => Pages\CreateEkayitKayit::route('/create'),
            'view'   => Pages\ViewEkayitKayit::route('/{record}'),
            'edit'   => Pages\EditEkayitKayit::route('/{record}/edit'),
        ];
    }
}
