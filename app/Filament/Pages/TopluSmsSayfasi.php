<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SmsGonderimResource;
use App\Models\SmsGonderim;
use App\Models\SmsGonderimAlici;
use App\Models\SmsListe;
use App\Services\HermesService;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TopluSmsSayfasi extends Page implements \Filament\Forms\Contracts\HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Toplu SMS Gönder';

    protected static ?int $navigationSort = 35;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static string $view = 'filament.pages.toplu-sms-sayfasi';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Kurs Yöneticisi']);
    }

    public function mount(): void
    {
        $this->form->fill([
            'liste_idler' => [],
            'mesaj' => null,
            'zamanlanmis' => false,
            'planli_tarih' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                CheckboxList::make('liste_idler')
                    ->label('Listeler')
                    ->required()
                    ->options(fn (): array => $this->erisebilirListeSorgu()
                        ->withCount('kisiler')
                        ->get()
                        ->mapWithKeys(fn (SmsListe $liste): array => [
                            $liste->id => $liste->ad.' ('.$liste->kisiler_count.' kişi)',
                        ])->toArray())
                    ->columns(2),

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

                Toggle::make('zamanlanmis')
                    ->label('Zamanlanmış Gönderim')
                    ->default(false)
                    ->live(),

                DateTimePicker::make('planli_tarih')
                    ->label('Planlı Tarih')
                    ->seconds(false)
                    ->visible(fn ($get): bool => (bool) ($get('zamanlanmis') ?? false)),
            ])
            ->statePath('data');
    }

    public function onizle(): void
    {
        $state = $this->form->getState();
        $telefonlar = $this->listeTelefonlariniHazirla($state['liste_idler'] ?? []);

        if ($telefonlar === []) {
            Notification::make()
                ->title('Seçilen listelerde alıcı bulunamadı.')
                ->warning()
                ->send();

            return;
        }

        $maliyet = app(HermesService::class)->calculateCost($telefonlar, (string) ($state['mesaj'] ?? ''));

        Notification::make()
            ->title('Önizleme')
            ->body("Net alıcı: ".count($telefonlar)." kişi\nToplam SMS: ".($maliyet['toplam_mesaj'] ?? 0)." adet\nTahmini maliyet bilgisi için Hermes panelini kontrol edin.")
            ->success()
            ->send();
    }

    public function gonder(): void
    {
        $state = $this->form->getState();
        $mesaj = (string) ($state['mesaj'] ?? '');
        $listeIdler = $state['liste_idler'] ?? [];
        $telefonlar = $this->listeTelefonlariniHazirla($listeIdler);

        if ($mesaj === '' || $telefonlar === []) {
            Notification::make()
                ->title('Liste ve mesaj alanlarını kontrol edin.')
                ->danger()
                ->send();

            return;
        }

        $sendDate = null;
        if (($state['zamanlanmis'] ?? false) === true) {
            if (blank($state['planli_tarih'] ?? null)) {
                Notification::make()
                    ->title('Zamanlanmış gönderim için planlı tarih seçmelisiniz.')
                    ->danger()
                    ->send();

                return;
            }

            $sendDate = Carbon::parse((string) $state['planli_tarih'])->format('Y-m-d H:i:s');
        }

        try {
            $sonuc = app(HermesService::class)->akilliGonder($telefonlar, $mesaj, $sendDate);
            $async = (bool) ($sonuc['async'] ?? false);
            $basariliMi = (bool) ($sonuc['basarili'] ?? false);

            $durum = 'basarisiz';
            if ($sendDate !== null) {
                $durum = 'beklemede';
            } elseif ($async) {
                $durum = 'gonderiliyor';
            } elseif ($basariliMi) {
                $durum = 'tamamlandi';
            }

            $gonderim = SmsGonderim::query()->create([
                'yonetici_id' => auth()->id(),
                'tip' => 'toplu',
                'mesaj' => $mesaj,
                'liste_idler' => array_values($listeIdler),
                'alici_sayisi' => count($telefonlar),
                'basarili' => ($sendDate || $async) ? 0 : (int) ($sonuc['gecerli'] ?? 0),
                'basarisiz' => ($sendDate || $async) ? 0 : (int) ($sonuc['gecersiz'] ?? 0),
                'bekleyen' => ($sendDate || $async) ? count($telefonlar) : 0,
                'durum' => $durum,
                'hermes_transaction_id' => isset($sonuc['transaction_id']) ? (string) $sonuc['transaction_id'] : null,
                'hermes_async_req_id' => isset($sonuc['req_log_id']) ? (string) $sonuc['req_log_id'] : null,
                'planli_tarih' => $state['zamanlanmis'] ? Carbon::parse((string) $state['planli_tarih']) : null,
            ]);

            foreach ($telefonlar as $telefon) {
                SmsGonderimAlici::query()->create([
                    'gonderim_id' => $gonderim->id,
                    'telefon' => $telefon,
                    'durum' => ($sendDate || $async) ? 'beklemede' : ($basariliMi ? 'basarili' : 'basarisiz'),
                    'created_at' => now(),
                ]);
            }

            Notification::make()
                ->title('Toplu SMS gönderimi kaydedildi.')
                ->success()
                ->send();

            $this->redirect(SmsGonderimResource::getUrl('index'));
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Gönderim başarısız: '.$exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function erisebilirListeSorgu(): Builder
    {
        $query = SmsListe::query()->orderBy('ad');

        if (! auth()->user()->hasRole('Admin')) {
            $query->where(function (Builder $builder): void {
                $builder
                    ->where('sahip_yonetici_id', auth()->id())
                    ->orWhere('ad', '2026NisanOncesi');
            });
        }

        return $query;
    }

    private function listeTelefonlariniHazirla(array $listeIdler): array
    {
        if ($listeIdler === []) {
            return [];
        }

        $telefonlar = $this->erisebilirListeSorgu()
            ->whereIn('sms_listeler.id', $listeIdler)
            ->with('kisiler:id,telefon')
            ->get()
            ->flatMap(fn (SmsListe $liste) => $liste->kisiler->pluck('telefon'))
            ->map(fn (string $telefon): string => self::telefonNormalize($telefon))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $telefonlar;
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
