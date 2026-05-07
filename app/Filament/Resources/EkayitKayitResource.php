<?php

namespace App\Filament\Resources;

use App\Enums\EkayitDurumu;
use App\Exports\EkayitExport;
use App\Filament\Resources\EkayitKayitResource\Pages;
use App\Models\EkayitDonem;
use App\Models\EkayitKayit;
use App\Models\EkayitSinif;
use App\Models\Kisi;
use App\Models\UyeBildirim;
use App\Models\UyeRozet;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
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
                    ->label('Öğrenci Adı')->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereExists(function ($sub) use ($search) {
                            $sub->select(\DB::raw(1))
                                ->from('ekayit_ogrenci_bilgileri')
                                ->whereColumn('ekayit_ogrenci_bilgileri.kayit_id', 'ekayit_kayitlar.id')
                                ->where(function ($s) use ($search) {
                                    $s->where('ad_soyad', 'like', "%{$search}%")
                                      ->orWhere('tc_kimlik', 'like', "%{$search}%");
                                });
                        });
                    })->sortable(false),

                TextColumn::make('sinif.ad')
                    ->label('Sınıf')
                    ->badge()
                    ->color(fn (EkayitKayit $record): string => $record->sinif?->renk ?? 'gray')
                    ->sortable(),

                TextColumn::make('veliBilgisi.ad_soyad')
                    ->label('Veli Adı')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereExists(function ($sub) use ($search) {
                            $sub->select(\DB::raw(1))
                                ->from('ekayit_veli_bilgileri')
                                ->whereColumn('ekayit_veli_bilgileri.kayit_id', 'ekayit_kayitlar.id')
                                ->where(function ($s) use ($search) {
                                    $s->where('ad_soyad', 'like', "%{$search}%")
                                      ->orWhere('telefon_1', 'like', "%{$search}%");
                                });
                        });
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
                ViewAction::make()->label('')->tooltip('Görüntüle'),
                \Filament\Tables\Actions\EditAction::make()->label('')->tooltip('Düzenle'),

                Action::make('kalici_sil')
                    ->label('')
                    ->tooltip('Kalıcı Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Kaydı Kalıcı Sil')
                    ->modalDescription('Bu kayıt, tüm bilgileri, bağlı üye hesabı ve kişi eşleştirmesi kalıcı olarak silinecek. Bu işlem geri alınamaz.')
                    ->modalSubmitActionLabel('Evet, Kalıcı Sil')
                    ->visible(fn () => static::izinVarMi('ekayit.sil'))
                    ->action(function (EkayitKayit $record): void {
                        try {
                            $record->loadMissing(['ogrenciBilgisi', 'kimlikBilgisi', 'okulBilgisi', 'veliBilgisi', 'babaBilgisi', 'olusturulanEvraklar', 'uye']);

                            // Alt kayıtları sil
                            $record->olusturulanEvraklar()->delete();
                            $record->ogrenciBilgisi?->delete();
                            $record->kimlikBilgisi?->delete();
                            $record->okulBilgisi?->delete();
                            $record->veliBilgisi?->delete();
                            $record->babaBilgisi?->delete();

                            // Üye ve ilişkileri
                            $uye = $record->uye;
                            if ($uye) {
                                $kisiId = $uye->kisi_id;

                                UyeRozet::where('uye_id', $uye->id)->delete();
                                UyeBildirim::where('uye_id', $uye->id)->delete();
                                $uye->forceDelete();

                                // Kişi kaydını başka üye kullanmıyorsa sil
                                if ($kisiId) {
                                    $baskaBagliUye = \App\Models\Uye::withTrashed()
                                        ->where('kisi_id', $kisiId)
                                        ->exists();

                                    if (! $baskaBagliUye) {
                                        Kisi::find($kisiId)?->delete();
                                    }
                                }
                            }

                            $record->forceDelete();

                            Notification::make()
                                ->title('Kayıt kalıcı olarak silindi')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Log::error('EkayitKayit kalici sil hatasi', [
                                'id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Silme hatası')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
