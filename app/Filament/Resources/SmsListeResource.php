<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsListeResource\Pages;
use App\Models\SmsGonderim;
use App\Models\SmsGonderimAlici;
use App\Models\SmsListe;
use App\Models\Yonetici;
use App\Services\HermesService;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SmsListeResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = SmsListe::class;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Listeler';

    protected static ?string $modelLabel = 'Liste';

    protected static ?string $pluralModelLabel = 'Listeler';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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

    public static function canView($record): bool
    {
        return static::izinlerdenBiriVarMi(['pazarlama_sms.listele', 'pazarlama_sms.goruntule'])
            && static::kaydaErisimVarMi($record);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('ad')
                ->label('Liste Adı')
                ->required()
                ->maxLength(255),

            Select::make('sahip_yonetici_id')
                ->label('Liste Sahibi')
                ->options(fn (): array => Yonetici::query()->orderBy('ad_soyad')->pluck('ad_soyad', 'id')->toArray())
                ->searchable()
                ->nullable()
                ->visible(fn (): bool => static::tumKayitlariGorebilirMi()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')
                    ->label('Liste')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kisiler_count')
                    ->counts('kisiler')
                    ->label('Kişi Sayısı')
                    ->sortable(),

                TextColumn::make('sahip.ad_soyad')
                    ->label('Sahip')
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'Genel Liste')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Oluşturma')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i') : '-')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('liste_sms_gonder')
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
                    ->action(function (SmsListe $record, array $data): void {
                        $mesaj = trim((string) ($data['mesaj'] ?? ''));

                        if ($mesaj === '') {
                            Notification::make()
                                ->title('Mesaj alanı zorunludur.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $telefonlar = $record->kisiler()
                            ->get(['sms_kisiler.telefon', 'sms_kisiler.telefon_2'])
                            ->flatMap(function ($kisi): array {
                                return [
                                    self::telefonNormalize((string) ($kisi->telefon ?? '')),
                                    self::telefonNormalize((string) ($kisi->telefon_2 ?? '')),
                                ];
                            })
                            ->filter(fn (string $telefon): bool => preg_match('/^5\d{9}$/', $telefon) === 1)
                            ->unique()
                            ->values()
                            ->all();

                        if ($telefonlar === []) {
                            Notification::make()
                                ->title('Listede geçerli telefon bulunamadı.')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            $sonuc = app(HermesService::class)->akilliGonder($telefonlar, $mesaj);
                            $async = (bool) ($sonuc['async'] ?? false);
                            $basariliMi = (bool) ($sonuc['basarili'] ?? false);

                            $gonderim = SmsGonderim::query()->create([
                                'yonetici_id' => auth()->id(),
                                'tip' => 'toplu',
                                'mesaj' => $mesaj,
                                'liste_idler' => [$record->id],
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
                                ->title('Listeye SMS gönderildi')
                                ->body($record->ad.' listesi için '.count($telefonlar).' numaraya gönderim başlatıldı.')
                                ->success()
                                ->send();
                        } catch (\Throwable $exception) {
                            Notification::make()
                                ->title('Gönderim başarısız: '.$exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (): bool => static::tumKayitlariGorebilirMi() && static::izinVarMi('pazarlama_sms.gonder')),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (SmsListe $record): bool => static::canEdit($record)),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::tumKayitlariGorebilirMi()) {
            return $query;
        }

        return $query->where('sahip_yonetici_id', auth()->id());
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

        return (int) $record->sahip_yonetici_id === (int) auth()->id();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsListeler::route('/'),
            'create' => Pages\CreateSmsListe::route('/create'),
            'view' => Pages\ViewSmsListe::route('/{record}'),
            'edit' => Pages\EditSmsListe::route('/{record}/edit'),
        ];
    }

    private static function telefonNormalize(string $telefon): string
    {
        $temiz = preg_replace('/\D+/', '', $telefon) ?? '';

        if (Str::startsWith($temiz, '90')) {
            $temiz = substr($temiz, 2);
        }

        if (Str::startsWith($temiz, '0')) {
            $temiz = substr($temiz, 1);
        }

        return $temiz;
    }
}
