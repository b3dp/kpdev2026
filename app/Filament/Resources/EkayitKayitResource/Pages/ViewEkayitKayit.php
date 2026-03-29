<?php

namespace App\Filament\Resources\EkayitKayitResource\Pages;

use App\Enums\EkayitDurumu;
use App\Filament\Resources\EkayitKayitResource;
use App\Jobs\EkayitDurumEpostasiJob;
use App\Models\EkayitHazirMesaj;
use App\Models\EkayitKayit;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewEkayitKayit extends ViewRecord
{
    protected static string $resource = EkayitKayitResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return EkayitKayit::with([
            'sinif.donem', 'sinif.kurum',
            'ogrenciBilgisi', 'kimlikBilgisi',
            'okulBilgisi', 'veliBilgisi', 'babaBilgisi',
            'yonetici',
        ])->findOrFail($key);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->record($this->record)->schema([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make('Onay/Red Sebebi')
                    ->schema([
                        TextEntry::make('durum_notu')
                            ->label('Mevcut Durum Notu')
                            ->default('—')
                            ->columnSpanFull(),
                    ])
                    ->footerActions([
                        InfolistAction::make('durum_notu_kaydet')
                            ->label('Kaydet')
                            ->icon('heroicon-o-check-circle')
                            ->color('primary')
                            ->form([
                                Select::make('hazir_mesaj_id')
                                    ->label('Hazır Mesaj Seç')
                                    ->options(fn (): array => $this->hazirMesajSecenekleri())
                                    ->searchable(),
                                Textarea::make('durum_notu')
                                    ->label('Manuel Mesaj')
                                    ->rows(4),
                            ])
                            ->fillForm(fn (): array => [
                                'durum_notu' => $this->record->durum_notu,
                            ])
                            ->action(function (array $data): void {
                                $durumNotu = trim((string) ($data['durum_notu'] ?? ''));
                                $hazirMesajId = $data['hazir_mesaj_id'] ?? null;

                                if ($durumNotu === '' && filled($hazirMesajId)) {
                                    $durumNotu = (string) ($this->hazirMesajMetni((int) $hazirMesajId) ?? '');
                                }

                                $this->record->update([
                                    'durum_notu' => $durumNotu !== '' ? $durumNotu : null,
                                ]);

                                $this->record->refresh();

                                Notification::make()
                                    ->title('Durum notu kaydedildi')
                                    ->success()
                                    ->send();
                            }),
                    ]),

                Section::make('İletişim Telefonu 01')
                    ->schema([
                        TextEntry::make('veliBilgisi.telefon_1')
                            ->label('Telefon 1')
                            ->default('—'),
                    ])
                    ->footerActions([
                        $this->durumAksiyonuOlustur(EkayitDurumu::Onaylandi, 'veliBilgisi.telefon_1', true),
                        $this->durumAksiyonuOlustur(EkayitDurumu::Reddedildi, 'veliBilgisi.telefon_1', true),
                        $this->durumAksiyonuOlustur(EkayitDurumu::Yedek, 'veliBilgisi.telefon_1', true),
                    ]),

                Section::make('İletişim Telefonu 02')
                    ->schema([
                        TextEntry::make('veliBilgisi.telefon_2')
                            ->label('Telefon 2')
                            ->default('—'),
                    ])
                    ->footerActions([
                        $this->durumAksiyonuOlustur(EkayitDurumu::Onaylandi, 'veliBilgisi.telefon_2', false),
                        $this->durumAksiyonuOlustur(EkayitDurumu::Reddedildi, 'veliBilgisi.telefon_2', false),
                        $this->durumAksiyonuOlustur(EkayitDurumu::Yedek, 'veliBilgisi.telefon_2', false),
                    ])
                    ->visible(fn (): bool => filled($this->record->veliBilgisi?->telefon_2)),

                Section::make('Dökümanlar')
                    ->schema([
                        TextEntry::make('dokuman_bilgi')
                            ->label('Bilgi')
                            ->state('Henüz evrak şablonu tanımlanmamış')
                            ->columnSpanFull(),
                    ])
                    ->footerActions([
                        InfolistAction::make('dokumanlari_indir')
                            ->label('Dökümanları İndir')
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('primary')
                            ->action(function (): void {
                                Notification::make()
                                    ->title('Döküman altyapısı henüz hazır değil')
                                    ->body('Henüz evrak şablonu tanımlanmamış')
                                    ->info()
                                    ->send();
                            }),
                    ]),
            ]),

            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make('Kayıt Bilgisi')->schema([
                    TextEntry::make('durum')->label('Durum')->badge()
                        ->formatStateUsing(function ($state): string {
                            $durum = $state instanceof EkayitDurumu ? $state : EkayitDurumu::tryFrom((string) $state);

                            return $durum?->label() ?? (string) $state;
                        })
                        ->color(function ($state): string {
                            $durum = $state instanceof EkayitDurumu ? $state : EkayitDurumu::tryFrom((string) $state);

                            return $durum?->renk() ?? 'gray';
                        }),
                    TextEntry::make('sinif.ad')->label('Sınıf')->default('—'),
                    TextEntry::make('sinif.donem.ad')->label('Dönem')->default('—'),
                    TextEntry::make('sinif.kurum.ad')->label('Kurum')->default('—'),
                    TextEntry::make('created_at')->label('Kayıt Tarihi')
                        ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i') : '—'),
                    TextEntry::make('durum_tarihi')->label('Durum Tarihi')
                        ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i') : '—'),
                    TextEntry::make('durum_notu')->label('Durum Notu')->default('—')->columnSpanFull(),
                    TextEntry::make('genel_not')->label('Genel Not')->default('—')->columnSpanFull(),
                ]),

                Section::make('Öğrenci Bilgileri')->schema([
                    TextEntry::make('ogrenciBilgisi.ad_soyad')->label('Ad Soyad')->default('—'),
                    TextEntry::make('ogrenciBilgisi.tc_kimlik')
                        ->label('TC Kimlik')
                        ->formatStateUsing(function (?string $state): string {
                            if (blank($state) || mb_strlen($state) < 6) {
                                return (string) ($state ?? '—');
                            }

                            return mb_substr($state, 0, 3) . '****' . mb_substr($state, -3);
                        }),
                    TextEntry::make('ogrenciBilgisi.dogum_tarihi')->label('Doğum Tarihi')
                        ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y') : '—'),
                    TextEntry::make('ogrenciBilgisi.dogum_yeri')->label('Doğum Yeri')->default('—'),
                    TextEntry::make('ogrenciBilgisi.baba_adi')->label('Baba Adı')->default('—'),
                    TextEntry::make('ogrenciBilgisi.anne_adi')->label('Anne Adı')->default('—'),
                    TextEntry::make('ogrenciBilgisi.ikamet_il')->label('İkamet İl')->default('—'),
                    TextEntry::make('ogrenciBilgisi.adres')->label('Adres')->default('—')->columnSpanFull(),
                ]),

                Section::make('Veli Bilgileri')->schema([
                    TextEntry::make('veliBilgisi.ad_soyad')->label('Ad Soyad')->default('—'),
                    TextEntry::make('veliBilgisi.eposta')->label('E-posta')->default('—'),
                    TextEntry::make('veliBilgisi.telefon_1')->label('Telefon 1')->default('—'),
                    TextEntry::make('veliBilgisi.telefon_2')->label('Telefon 2')->default('—'),
                ]),

                Section::make('Okul Bilgileri')->schema([
                    TextEntry::make('okulBilgisi.okul_adi')->label('Okul Adı')->default('—'),
                    TextEntry::make('okulBilgisi.okul_numarasi')->label('Okul Numarası')->default('—'),
                    TextEntry::make('okulBilgisi.sube')->label('Şube')->default('—'),
                    TextEntry::make('okulBilgisi.not')->label('Not')->default('—'),
                ]),

                Section::make('Kimlik Bilgileri')->schema([
                    TextEntry::make('kimlikBilgisi.kayitli_il')->label('Kayıtlı İl')->default('—'),
                    TextEntry::make('kimlikBilgisi.kayitli_ilce')->label('Kayıtlı İlçe')->default('—'),
                    TextEntry::make('kimlikBilgisi.kayitli_mahalle_koy')->label('Mahalle/Köy')->default('—'),
                    TextEntry::make('kimlikBilgisi.cilt_no')->label('Cilt No')->default('—'),
                    TextEntry::make('kimlikBilgisi.aile_sira_no')->label('Aile Sıra No')->default('—'),
                    TextEntry::make('kimlikBilgisi.sira_no')->label('Sıra No')->default('—'),
                    TextEntry::make('kimlikBilgisi.cuzdanin_verildigi_yer')->label('Cüzdanın Verildiği Yer')->default('—'),
                    TextEntry::make('kimlikBilgisi.kimlik_seri_no')->label('Kimlik Seri No')->default('—'),
                    TextEntry::make('kimlikBilgisi.kan_grubu')->label('Kan Grubu')->default('—'),
                ]),

                Section::make('Baba Bilgileri')->schema([
                    TextEntry::make('babaBilgisi.dogum_yeri')->label('Doğum Yeri')->default('—'),
                    TextEntry::make('babaBilgisi.nufus_il_ilce')->label('Nüfus İl/İlçe')->default('—'),
                ]),
            ]),
        ]);
    }

    private function hazirMesajSecenekleri(): array
    {
        return EkayitHazirMesaj::query()
            ->orderByDesc('id')
            ->get(['id', 'baslik', 'tip'])
            ->mapWithKeys(fn (EkayitHazirMesaj $mesaj): array => [
                $mesaj->id => sprintf('%s (%s)', (string) $mesaj->baslik, strtoupper((string) $mesaj->tip)),
            ])
            ->all();
    }

    private function hazirMesajMetni(int $hazirMesajId): ?string
    {
        return EkayitHazirMesaj::query()
            ->whereKey($hazirMesajId)
            ->value('metin');
    }

    private function durumAksiyonuOlustur(EkayitDurumu $durum, string $telefonYolu, bool $durumGuncellensin): InfolistAction
    {
        return InfolistAction::make(($durumGuncellensin ? 'tel1_' : 'tel2_') . $durum->value)
            ->label($durum->label())
            ->color($durum->renk())
            ->action(function () use ($durum, $telefonYolu, $durumGuncellensin): void {
                $telefon = data_get($this->record, $telefonYolu);

                if (blank($telefon)) {
                    Notification::make()
                        ->title('Telefon numarası bulunamadı')
                        ->danger()
                        ->send();

                    return;
                }

                if ($durumGuncellensin) {
                    $this->record->update([
                        'durum' => $durum,
                        'durum_tarihi' => now(),
                        'yonetici_id' => auth()->id(),
                    ]);

                    if (filled($this->record->veliBilgisi?->eposta)) {
                        dispatch(new EkayitDurumEpostasiJob($this->record->id, $durum->value));
                    }

                    $this->record->refresh();
                }

                $mesaj = $this->mesajMetniHazirla($durum);
                $whatsappUrl = $this->whatsappUrlOlustur((string) $telefon, $mesaj);

                if (blank($whatsappUrl)) {
                    Notification::make()
                        ->title('WhatsApp linki oluşturulamadı')
                        ->danger()
                        ->send();

                    return;
                }

                $this->js("window.open('" . addslashes((string) $whatsappUrl) . "', '_blank')");

                Notification::make()
                    ->title('WhatsApp penceresi açıldı')
                    ->success()
                    ->send();
            });
    }

    private function mesajMetniHazirla(EkayitDurumu $durum): string
    {
        $hamMetin = trim((string) ($this->record->durum_notu ?? ''));

        if ($hamMetin === '') {
            $hamMetin = $this->varsayilanMesajMetni($this->durumTipiniBul($durum));
        }

        return $this->mesajDegiskenleriniDoldur($hamMetin, $durum);
    }

    private function varsayilanMesajMetni(string $tip): string
    {
        return (string) (EkayitHazirMesaj::query()
            ->where('tip', $tip)
            ->where('aktif', true)
            ->orderByDesc('id')
            ->value('metin') ?? '');
    }

    private function durumTipiniBul(EkayitDurumu $durum): string
    {
        return match ($durum) {
            EkayitDurumu::Onaylandi => 'onay',
            EkayitDurumu::Reddedildi => 'red',
            EkayitDurumu::Yedek => 'yedek',
            default => 'genel',
        };
    }

    private function mesajDegiskenleriniDoldur(string $metin, EkayitDurumu $durum): string
    {
        $degiskenler = [
            '{AD_SOYAD}' => (string) ($this->record->ogrenciBilgisi?->ad_soyad ?? ''),
            '{SINIF}' => (string) ($this->record->sinif?->ad ?? ''),
            '{KURUM}' => (string) ($this->record->sinif?->kurum?->ad ?? ''),
            '{DURUM}' => $durum->label(),
            '{TARIH}' => now()->format('d.m.Y H:i'),
        ];

        return strtr($metin, $degiskenler);
    }

    private function whatsappUrlOlustur(string $telefon, string $mesaj): ?string
    {
        $temizTelefon = preg_replace('/\D+/', '', $telefon) ?: '';
        if ($temizTelefon === '') {
            return null;
        }

        if (str_starts_with($temizTelefon, '90')) {
            $temizTelefon = substr($temizTelefon, 2);
        }

        if (str_starts_with($temizTelefon, '0')) {
            $temizTelefon = substr($temizTelefon, 1);
        }

        if ($temizTelefon === '') {
            return null;
        }

        return 'https://wa.me/90' . $temizTelefon . '?text=' . urlencode($mesaj);
    }
}
