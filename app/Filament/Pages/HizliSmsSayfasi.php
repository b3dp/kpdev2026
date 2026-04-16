<?php

namespace App\Filament\Pages;

use App\Models\SmsGonderim;
use App\Models\SmsGonderimAlici;
use App\Models\SmsKisi;
use App\Services\HermesService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class HizliSmsSayfasi extends Page implements \Filament\Forms\Contracts\HasForms
{
    use \App\Support\PanelYetkiKontrolu;
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Hızlı SMS Gönder';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string $view = 'filament.pages.hizli-sms-sayfasi';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return static::izinVarMi('pazarlama_sms.gonder');
    }

    public function mount(): void
    {
        $this->form->fill([
            'manuel_telefon' => null,
            'kisi_idler' => [],
            'mesaj' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('manuel_telefon')
                    ->label('Telefon Numarası')
                    ->placeholder('5xxxxxxxxx')
                    ->hint('Rehberden seçmek yerine direkt numara girebilirsiniz'),

                Select::make('kisi_idler')
                    ->label('Rehberden Seç')
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => $this->erisebilirKisiSorgu()
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (SmsKisi $kisi): array => [
                            $kisi->id => trim(($kisi->ad_soyad ?: 'İsimsiz').' - '.$kisi->telefon),
                        ])->toArray())
                    ->getSearchResultsUsing(function (string $search): array {
                        return $this->erisebilirKisiSorgu()
                            ->where(function (Builder $builder) use ($search): void {
                                $builder
                                    ->where('telefon', 'like', "%{$search}%")
                                    ->orWhere('ad_soyad', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (SmsKisi $kisi): array => [
                                $kisi->id => trim(($kisi->ad_soyad ?: 'İsimsiz').' - '.$kisi->telefon),
                            ])->toArray();
                    }),

                Textarea::make('mesaj')
                    ->label('Mesaj')
                    ->required()
                    ->rows(6)
                    ->live()
                    ->helperText(function ($get): string {
                        $mesaj = (string) ($get('mesaj') ?? '');
                        $karakter = mb_strlen($mesaj, 'UTF-8');
                        $smsAdedi = $this->smsAdediHesapla($mesaj);

                        return "{$karakter} karakter / {$smsAdedi} SMS";
                    }),
            ])
            ->statePath('data');
    }

    public function onizle(): void
    {
        $state = $this->form->getState();
        $telefonlar = $this->telefonlariHazirla($state);

        if ($telefonlar === []) {
            Notification::make()
                ->title('En az bir alıcı seçmelisiniz.')
                ->warning()
                ->send();

            return;
        }

        if (blank($state['mesaj'] ?? null)) {
            Notification::make()
                ->title('Mesaj alanı zorunludur.')
                ->warning()
                ->send();

            return;
        }

        $maliyet = app(HermesService::class)->calculateCost($telefonlar, (string) $state['mesaj']);

        Notification::make()
            ->title(count($telefonlar).' alıcı, '.($maliyet['toplam_mesaj'] ?? 0).' SMS')
            ->success()
            ->send();
    }

    public function gonder(): void
    {
        $state = $this->form->getState();
        $telefonlar = $this->telefonlariHazirla($state);
        $mesaj = (string) ($state['mesaj'] ?? '');

        if ($telefonlar === [] || $mesaj === '') {
            Notification::make()
                ->title('Telefon ve mesaj alanlarını kontrol edin.')
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
                ->success()
                ->send();

            $this->form->fill([
                'manuel_telefon' => null,
                'kisi_idler' => [],
                'mesaj' => null,
            ]);
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Gönderim başarısız: '.$exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function erisebilirKisiSorgu(): Builder
    {
        $query = SmsKisi::query()->orderBy('ad_soyad');

        if (! auth()->user()->hasAnyRole(['Admin', 'Halkla İlişkiler'])) {
            $query->whereHas('listeler', function (Builder $builder): void {
                $builder->where('sahip_yonetici_id', auth()->id());
            });
        }

        return $query;
    }

    private function telefonlariHazirla(array $state): array
    {
        $telefonlar = [];

        $manuel = self::telefonNormalize((string) ($state['manuel_telefon'] ?? ''));
        if ($manuel !== '') {
            $telefonlar[] = $manuel;
        }

        $kisiIdler = $state['kisi_idler'] ?? [];
        if ($kisiIdler !== []) {
            $rehberTelefonlari = $this->erisebilirKisiSorgu()
                ->whereIn('id', $kisiIdler)
                ->pluck('telefon')
                ->all();

            foreach ($rehberTelefonlari as $telefon) {
                $telefonlar[] = self::telefonNormalize((string) $telefon);
            }
        }

        $telefonlar = array_filter($telefonlar, fn (string $telefon): bool => $telefon !== '');

        return array_values(array_unique($telefonlar));
    }

    private function smsAdediHesapla(string $mesaj): int
    {
        $karakter = mb_strlen($mesaj, 'UTF-8');

        if ($karakter === 0) {
            return 0;
        }

        return (int) ceil($karakter / 70);
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
