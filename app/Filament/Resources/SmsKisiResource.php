<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsKisiResource\Pages;
use App\Jobs\HermesAktarimJob;
use App\Models\SmsAktarim;
use App\Models\SmsGonderim;
use App\Models\SmsGonderimAlici;
use App\Models\SmsKisi;
use App\Models\SmsListe;
use App\Services\HermesService;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SmsKisiResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = SmsKisi::class;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Rehber';

    protected static ?string $modelLabel = 'Kişi';

    protected static ?string $pluralModelLabel = 'Kişiler';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function canViewAny(): bool
    {
        return static::izinlerdenBiriVarMi(['pazarlama_sms.listele', 'pazarlama_sms.goruntule']);
    }

    public static function canCreate(): bool
    {
        return static::izinVarMi('pazarlama_sms.kaydet');
    }

    public static function canEdit($record): bool
    {
        return static::izinVarMi('pazarlama_sms.kaydet')
            && static::kaydaErisimVarMi($record);
    }

    public static function canDelete($record): bool
    {
        return static::izinVarMi('pazarlama_sms.sil')
            && static::kaydaErisimVarMi($record);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('telefon')
                    ->label('Telefon')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telefon_2')
                    ->label('Telefon 2')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => $state ?: '-'),

                TextColumn::make('ad_soyad')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => $state ?: '-'),

                TextColumn::make('listeler')
                    ->label('Listeler')
                    ->state(fn (SmsKisi $record): string => $record->listeler->pluck('ad')->implode(', '))
                    ->badge()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Kayıt')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i') : '-')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Action::make('hermes_aktar')
                    ->label('Excel\'den Aktar')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->form([
                        Select::make('liste_id')
                            ->label('Hedef Liste')
                            ->required()
                            ->searchable()
                            ->options(fn (): array => self::erisebilirListeSecenekleri()),

                        FileUpload::make('dosya')
                            ->label('Excel Dosyası')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required()
                            ->maxSize(10240), // 10MB
                    ])
                    ->action(function (array $data): void {
                        $aktarim = SmsAktarim::create([
                            'yonetici_id' => (int) auth()->id(),
                            'liste_id' => (int) $data['liste_id'],
                            'dosya' => (string) $data['dosya'],
                            'durum' => 'bekliyor',
                        ]);

                        HermesAktarimJob::dispatch(
                            $data['dosya'],
                            auth()->id(),
                            (int) $data['liste_id'],
                            (int) $aktarim->id,
                        );

                        Notification::make()
                            ->title('Aktarım başlatıldı')
                            ->body('İşlem arka planda devam ediyor. Sonucu SMS Yönetimi > Aktarım Geçmişi ekranından takip edebilirsiniz.')
                            ->success()
                            ->send();
                    })
                        ->visible(fn (): bool => static::izinVarMi('pazarlama_sms.kaydet')),
            ])
            ->actions([
                Action::make('kisi_sms_gonder')
                    ->label('SMS')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->form([
                        Textarea::make('mesaj')
                            ->label('Mesaj')
                            ->required()
                            ->rows(5)
                            ->maxLength(1000),
                    ])
                    ->action(function (SmsKisi $record, array $data): void {
                        $telefonlar = collect([
                            self::telefonNormalize((string) ($record->telefon ?? '')),
                            self::telefonNormalize((string) ($record->telefon_2 ?? '')),
                        ])
                            ->filter(fn (string $telefon): bool => preg_match('/^5\d{9}$/', $telefon) === 1)
                            ->unique()
                            ->values()
                            ->all();

                        if ($telefonlar === []) {
                            Notification::make()
                                ->title('Bu kişi için geçerli telefon bulunamadı.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $mesaj = trim((string) ($data['mesaj'] ?? ''));

                        if ($mesaj === '') {
                            Notification::make()
                                ->title('Mesaj alanı zorunludur.')
                                ->warning()
                                ->send();

                            return;
                        }

                        try {
                            $sonuc = app(HermesService::class)->akilliGonder($telefonlar, $mesaj);
                            $async = (bool) ($sonuc['async'] ?? false);
                            $basariliMi = (bool) ($sonuc['basarili'] ?? false);

                            $gonderim = SmsGonderim::query()->create([
                                'yonetici_id' => auth()->id(),
                                'tip' => 'hizli',
                                'mesaj' => $mesaj,
                                'liste_idler' => null,
                                'alici_sayisi' => count($telefonlar),
                                'basarili' => $async ? 0 : (int) ($sonuc['gecerli'] ?? 0),
                                'basarisiz' => $async ? 0 : (int) ($sonuc['gecersiz'] ?? 0),
                                'bekleyen' => $async ? count($telefonlar) : 0,
                                'durum' => $async ? 'gonderiliyor' : ($basariliMi ? 'tamamlandi' : 'basarisiz'),
                                'hermes_transaction_id' => isset($sonuc['transaction_id']) ? (string) $sonuc['transaction_id'] : null,
                                'hermes_async_req_id' => isset($sonuc['req_log_id']) ? (string) $sonuc['req_log_id'] : null,
                                'planli_tarih' => null,
                            ]);

                            foreach ($telefonlar as $telefon) {
                                SmsGonderimAlici::query()->create([
                                    'gonderim_id' => $gonderim->id,
                                    'telefon' => $telefon,
                                    'durum' => $async ? 'beklemede' : ($basariliMi ? 'basarili' : 'basarisiz'),
                                    'created_at' => now(),
                                ]);
                            }

                            Notification::make()
                                ->title('SMS gönderildi')
                                ->body('Kişi için '.count($telefonlar).' numaraya gönderim başlatıldı.')
                                ->success()
                                ->send();
                        } catch (\Throwable $exception) {
                            Notification::make()
                                ->title('Gönderim başarısız: '.$exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (): bool => static::izinVarMi('pazarlama_sms.gonder')),

                Tables\Actions\EditAction::make()->label('')->tooltip('Düzenle')
                    ->visible(fn (SmsKisi $record): bool => static::canEdit($record)),

                Tables\Actions\Action::make('kalici_sil')
                    ->label('')
                    ->tooltip('Kalıcı Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Kişiyi Kalıcı Sil')
                    ->modalDescription('Bu kişi tüm liste bağlantılarıyla birlikte kalıcı olarak silinecek. Bu işlem geri alınamaz.')
                    ->modalSubmitActionLabel('Evet, Kalıcı Sil')
                    ->visible(fn (SmsKisi $record): bool => static::canDelete($record))
                    ->action(function (SmsKisi $record): void {
                        try {
                            $record->forceDelete();

                            Notification::make()
                                ->title('Kişi kalıcı olarak silindi')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::error('SmsKisi kalici sil hatasi', [
                                'id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Silme hatası: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('toplu_sil')
                        ->label('Seçilenleri Sil')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Seçilen Kişileri Kalıcı Sil')
                        ->modalDescription('Seçilen tüm kişiler ve liste bağlantıları kalıcı olarak silinecek. Bu işlem geri alınamaz.')
                        ->modalSubmitActionLabel('Evet, Kalıcı Sil')
                        ->visible(fn (): bool => static::izinVarMi('pazarlama_sms.sil'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            try {
                                $records->each(fn (SmsKisi $kisi) => $kisi->forceDelete());

                                Notification::make()
                                    ->title($records->count().' kişi kalıcı olarak silindi')
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::error('SmsKisi toplu sil hatasi', [
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Silme hatası: '.$e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    BulkAction::make('listeye_ekle')
                        ->label('Listeye Ekle')
                        ->icon('heroicon-o-rectangle-stack')
                        ->color('primary')
                        ->form([
                            Select::make('liste_id')
                                ->label('Hedef Liste')
                                ->required()
                                ->searchable()
                                ->options(fn (): array => self::erisebilirListeSecenekleri()),
                        ])
                        ->visible(fn (): bool => static::izinVarMi('pazarlama_sms.kaydet'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data): void {
                            try {
                                $listeId = (int) $data['liste_id'];
                                $liste = SmsListe::find($listeId);

                                if (! $liste) {
                                    Notification::make()
                                        ->title('Liste bulunamadı.')
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                $records->each(fn (SmsKisi $kisi) => $liste->kisiler()->syncWithoutDetaching([$kisi->id]));

                                Notification::make()
                                    ->title($records->count().' kişi "'.$liste->ad.'" listesine eklendi')
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::error('SmsKisi listeye ekle hatasi', [
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Ekleme hatası: '.$e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('telefon')
                ->label('Telefon')
                ->required()
                ->maxLength(20)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($set, ?string $state): void {
                    $set('telefon', self::telefonNormalize($state));
                })
                ->rule('regex:/^5\d{9}$/')
                ->validationMessages([
                    'regex' => 'Telefon numarasını 5321234567 formatında giriniz.',
                ]),

            TextInput::make('telefon_2')
                ->label('Telefon 2')
                ->maxLength(20)
                ->nullable()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($set, ?string $state): void {
                    $set('telefon_2', self::telefonNormalize($state));
                })
                ->rule('regex:/^5\d{9}$/')
                ->different('telefon')
                ->validationMessages([
                    'regex' => 'Telefon 2 numarasını 5321234567 formatında giriniz.',
                    'different' => 'Telefon 2 numarası Telefon 1 ile aynı olamaz.',
                ]),

            TextInput::make('ad_soyad')
                ->label('Ad Soyad')
                ->maxLength(255)
                ->nullable(),

            Textarea::make('notlar')
                ->label('Notlar')
                ->rows(3)
                ->nullable(),

            CheckboxList::make('liste_idler')
                ->label('Listeler')
                ->options(fn (): array => self::erisebilirListeSecenekleri())
                ->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('listeler');

        if (static::tumKayitlariGorebilirMi()) {
            return $query;
        }

        return $query->where('created_by', auth()->id());
    }

    public static function telefonNormalize(?string $telefon): string
    {
        $temiz = preg_replace('/\D+/', '', (string) $telefon) ?? '';

        if (Str::startsWith($temiz, '90')) {
            $temiz = substr($temiz, 2);
        }

        if (Str::startsWith($temiz, '0')) {
            $temiz = substr($temiz, 1);
        }

        return $temiz;
    }

    public static function erisebilirListeSecenekleri(): array
    {
        $query = SmsListe::query()->orderBy('ad');

        if (! static::tumKayitlariGorebilirMi()) {
            $query->where('sahip_yonetici_id', auth()->id());
        }

        return $query->pluck('ad', 'id')->toArray();
    }

    protected static function tumKayitlariGorebilirMi(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    protected static function kaydaErisimVarMi($record): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (static::tumKayitlariGorebilirMi()) {
            return true;
        }

        return (int) $record->created_by === (int) auth()->id();
    }

    public static function telefonKaydiVarMi(string $telefon, ?int $haricKisiId = null): bool
    {
        $query = SmsKisi::query()->where(function (Builder $builder) use ($telefon): void {
            $builder->where('telefon', $telefon)
                ->orWhere('telefon_2', $telefon);
        });

        if ($haricKisiId !== null) {
            $query->whereKeyNot($haricKisiId);
        }

        return $query->exists();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsKisiler::route('/'),
            'create' => Pages\CreateSmsKisi::route('/create'),
            'edit' => Pages\EditSmsKisi::route('/{record}/edit'),
        ];
    }
}
