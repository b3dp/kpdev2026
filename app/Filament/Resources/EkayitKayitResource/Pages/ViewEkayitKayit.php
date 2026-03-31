<?php

namespace App\Filament\Resources\EkayitKayitResource\Pages;

use App\Enums\EkayitDurumu;
use App\Filament\Resources\EkayitKayitResource;
use App\Jobs\EkayitDurumEpostasiJob;
use App\Jobs\EkayitSmsJob;
use App\Models\EkayitHazirMesaj;
use App\Models\EkayitKayit;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Actions as InfolistActions;
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
            Section::make()
                ->icon('heroicon-o-information-circle')
                ->iconColor('info')
                ->description('1. ONAY/RED SEBEBİ bölümünden durum notunu seçin veya yazın. 2. Bilgilendirme Kanallarından WhatsApp veya SMS butonuna tıklayın — seçtiğiniz tip (Onay / Red / Yedek) doğrultusunda kayıt durumu otomatik güncellenir ve ilgili uygulama açılır. 3. Gönderilen mesaj tipine göre Kayıt Bilgisi Durumu da değişmiş olur.')
                ->schema([])
                ->columnSpanFull(),

            Grid::make(4)->schema([
                Section::make('Onay/Red Sebebi')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('durum_notu')
                            ->label('Mevcut Durum Notu')
                            ->default('—')
                            ->columnSpanFull(),
                        TextEntry::make('hazir_mesaj_bilgi')
                            ->label('Hazır Mesaj')
                            ->state('Henüz hazır mesaj tanımlanmamış')
                            ->visible(fn (): bool => ! $this->hazirMesajVarMi()),
                    ])
                    ->footerActions([
                        InfolistAction::make('durum_notu_kaydet')
                            ->label('Durum Notu Ekle')
                            ->icon('heroicon-o-check-circle')
                            ->color('primary')
                            ->form([
                                Select::make('hazir_mesaj_id')
                                    ->label('Hazır Mesaj Seç')
                                    ->options(fn (): array => $this->hazirMesajSecenekleri())
                                    ->placeholder('Henüz hazır mesaj tanımlanmamış')
                                    ->disabled(fn (): bool => ! $this->hazirMesajVarMi())
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

                Section::make('Bilgilendirme Kanalı 01')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('veliBilgisi.telefon_1')
                            ->label('Telefon 1')
                            ->default('—'),
                        TextEntry::make('telefon_1_whatsapp_baslik')
                            ->label('')
                            ->state('WhatsApp'),
                        InfolistActions::make([
                            $this->durumAksiyonuOlustur(EkayitDurumu::Onaylandi, 'veliBilgisi.telefon_1', true)->size('sm'),
                            $this->durumAksiyonuOlustur(EkayitDurumu::Reddedildi, 'veliBilgisi.telefon_1', true)->size('sm'),
                            $this->durumAksiyonuOlustur(EkayitDurumu::Yedek, 'veliBilgisi.telefon_1', true)->size('sm'),
                        ])->columnSpanFull(),
                        TextEntry::make('telefon_1_sms_baslik')
                            ->label('')
                            ->state('SMS'),
                        InfolistActions::make([
                            $this->smsAksiyonuOlustur('tel1_sms_onay', 'Onay', 'success')->size('sm'),
                            $this->smsAksiyonuOlustur('tel1_sms_red', 'Red', 'danger')->size('sm'),
                            $this->smsAksiyonuOlustur('tel1_sms_yedek', 'Yedek', 'info')->size('sm'),
                        ])->columnSpanFull(),
                    ]),

                Section::make('Bilgilendirme Kanalı 02')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('veliBilgisi.telefon_2')
                            ->label('Telefon 2')
                            ->default('—'),
                        TextEntry::make('telefon_2_whatsapp_baslik')
                            ->label('')
                            ->state('WhatsApp'),
                        InfolistActions::make([
                            $this->durumAksiyonuOlustur(EkayitDurumu::Onaylandi, 'veliBilgisi.telefon_2', false)->size('sm'),
                            $this->durumAksiyonuOlustur(EkayitDurumu::Reddedildi, 'veliBilgisi.telefon_2', false)->size('sm'),
                            $this->durumAksiyonuOlustur(EkayitDurumu::Yedek, 'veliBilgisi.telefon_2', false)->size('sm'),
                        ])->columnSpanFull(),
                        TextEntry::make('telefon_2_sms_baslik')
                            ->label('')
                            ->state('SMS'),
                        InfolistActions::make([
                            $this->smsAksiyonuOlustur('tel2_sms_onay', 'Onay', 'success')->size('sm'),
                            $this->smsAksiyonuOlustur('tel2_sms_red', 'Red', 'danger')->size('sm'),
                            $this->smsAksiyonuOlustur('tel2_sms_yedek', 'Yedek', 'info')->size('sm'),
                        ])->columnSpanFull(),
                    ])
                    ->visible(fn (): bool => filled($this->record->veliBilgisi?->telefon_2)),

                Section::make('Dökümanlar')
                    ->columnSpan(1)
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

            Grid::make(2)->schema([
                Section::make('Kayıt Bilgisi')
                    ->columnSpan(1)
                    ->schema([
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
                    ])
                    ->headerActions([
                        $this->kayitBilgisiDuzenleAksiyonu(),
                    ]),

                Section::make('Öğrenci Bilgileri')
                    ->columnSpan(1)
                    ->schema([
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
                    ])
                    ->headerActions([
                        $this->ogrenciBilgisiDuzenleAksiyonu(),
                    ]),

                Section::make('Veli Bilgileri')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('veliBilgisi.ad_soyad')->label('Ad Soyad')->default('—'),
                        TextEntry::make('veliBilgisi.eposta')->label('E-posta')->default('—'),
                        TextEntry::make('veliBilgisi.telefon_1')->label('Telefon 1')->default('—'),
                        TextEntry::make('veliBilgisi.telefon_2')->label('Telefon 2')->default('—'),
                    ])
                    ->headerActions([
                        $this->veliBilgisiDuzenleAksiyonu(),
                    ]),

                Section::make('Okul Bilgileri')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('okulBilgisi.okul_adi')->label('Okul Adı')->default('—'),
                        TextEntry::make('okulBilgisi.okul_numarasi')->label('Okul Numarası')->default('—'),
                        TextEntry::make('okulBilgisi.sube')->label('Şube')->default('—'),
                        TextEntry::make('okulBilgisi.not')->label('Not')->default('—'),
                    ])
                    ->headerActions([
                        $this->okulBilgisiDuzenleAksiyonu(),
                    ]),

                Section::make('Kimlik Bilgileri')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('kimlikBilgisi.kayitli_il')->label('Kayıtlı İl')->default('—'),
                        TextEntry::make('kimlikBilgisi.kayitli_ilce')->label('Kayıtlı İlçe')->default('—'),
                        TextEntry::make('kimlikBilgisi.kayitli_mahalle_koy')->label('Mahalle/Köy')->default('—'),
                        TextEntry::make('kimlikBilgisi.cilt_no')->label('Cilt No')->default('—'),
                        TextEntry::make('kimlikBilgisi.aile_sira_no')->label('Aile Sıra No')->default('—'),
                        TextEntry::make('kimlikBilgisi.sira_no')->label('Sıra No')->default('—'),
                        TextEntry::make('kimlikBilgisi.cuzdanin_verildigi_yer')->label('Cüzdanın Verildiği Yer')->default('—'),
                        TextEntry::make('kimlikBilgisi.kimlik_seri_no')->label('Kimlik Seri No')->default('—'),
                        TextEntry::make('kimlikBilgisi.kan_grubu')->label('Kan Grubu')->default('—'),
                    ])
                    ->headerActions([
                        $this->kimlikBilgisiDuzenleAksiyonu(),
                    ]),

                Section::make('Baba Bilgileri')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('babaBilgisi.dogum_yeri')->label('Doğum Yeri')->default('—'),
                        TextEntry::make('babaBilgisi.nufus_il_ilce')->label('Nüfus İl/İlçe')->default('—'),
                    ])
                    ->headerActions([
                        $this->babaBilgisiDuzenleAksiyonu(),
                    ]),
            ]),
        ]);
    }

    private function kayitBilgisiDuzenleAksiyonu(): InfolistAction
    {
        return InfolistAction::make('kayit_bilgisi_duzenle')
            ->label('')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->outlined()
            ->form([
                Select::make('durum')
                    ->label('Durum')
                    ->options(EkayitDurumu::secenekler())
                    ->required(),
                Textarea::make('durum_notu')->label('Durum Notu')->rows(3),
                Textarea::make('genel_not')->label('Genel Not')->rows(3),
            ])
            ->fillForm(fn (): array => [
                'durum' => $this->record->durum?->value ?? $this->record->durum,
                'durum_notu' => $this->record->durum_notu,
                'genel_not' => $this->record->genel_not,
            ])
            ->action(function (array $data): void {
                $this->record->update([
                    'durum' => $data['durum'],
                    'durum_notu' => $data['durum_notu'] ?? null,
                    'genel_not' => $data['genel_not'] ?? null,
                ]);

                $this->kaydiYenile();
                $this->basariliBildirimGonder('Kayıt bilgisi güncellendi');
            });
    }

    private function ogrenciBilgisiDuzenleAksiyonu(): InfolistAction
    {
        return InfolistAction::make('ogrenci_bilgisi_duzenle')
            ->label('')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->outlined()
            ->form([
                TextInput::make('ad_soyad')->label('Ad Soyad')->required()->maxLength(255),
                TextInput::make('tc_kimlik')->label('TC Kimlik')->required()->maxLength(11),
                DatePicker::make('dogum_tarihi')->label('Doğum Tarihi')->required(),
                TextInput::make('dogum_yeri')->label('Doğum Yeri')->maxLength(255),
                TextInput::make('baba_adi')->label('Baba Adı')->maxLength(255),
                TextInput::make('anne_adi')->label('Anne Adı')->maxLength(255),
                TextInput::make('ikamet_il')->label('İkamet İl')->maxLength(100),
                Textarea::make('adres')->label('Adres')->rows(3)->columnSpanFull(),
            ])
            ->fillForm(fn (): array => [
                'ad_soyad' => $this->record->ogrenciBilgisi?->ad_soyad,
                'tc_kimlik' => $this->record->ogrenciBilgisi?->tc_kimlik,
                'dogum_tarihi' => $this->record->ogrenciBilgisi?->dogum_tarihi?->toDateString(),
                'dogum_yeri' => $this->record->ogrenciBilgisi?->dogum_yeri,
                'baba_adi' => $this->record->ogrenciBilgisi?->baba_adi,
                'anne_adi' => $this->record->ogrenciBilgisi?->anne_adi,
                'ikamet_il' => $this->record->ogrenciBilgisi?->ikamet_il,
                'adres' => $this->record->ogrenciBilgisi?->adres,
            ])
            ->action(function (array $data): void {
                $this->record->ogrenciBilgisi()->updateOrCreate(
                    ['kayit_id' => $this->record->id],
                    [
                        'ad_soyad' => $data['ad_soyad'],
                        'tc_kimlik' => $data['tc_kimlik'],
                        'dogum_tarihi' => $data['dogum_tarihi'],
                        'dogum_yeri' => $data['dogum_yeri'] ?? null,
                        'baba_adi' => $data['baba_adi'] ?? null,
                        'anne_adi' => $data['anne_adi'] ?? null,
                        'ikamet_il' => $data['ikamet_il'] ?? null,
                        'adres' => $data['adres'] ?? null,
                    ]
                );

                $this->kaydiYenile();
                $this->basariliBildirimGonder('Öğrenci bilgileri güncellendi');
            });
    }

    private function veliBilgisiDuzenleAksiyonu(): InfolistAction
    {
        return InfolistAction::make('veli_bilgisi_duzenle')
            ->label('')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->outlined()
            ->form([
                TextInput::make('ad_soyad')->label('Ad Soyad')->required()->maxLength(255),
                TextInput::make('eposta')->label('E-posta')->email()->maxLength(255),
                TextInput::make('telefon_1')->label('Telefon 1')->required()->maxLength(20),
                TextInput::make('telefon_2')->label('Telefon 2')->maxLength(20),
            ])
            ->fillForm(fn (): array => [
                'ad_soyad' => $this->record->veliBilgisi?->ad_soyad,
                'eposta' => $this->record->veliBilgisi?->eposta,
                'telefon_1' => $this->record->veliBilgisi?->telefon_1,
                'telefon_2' => $this->record->veliBilgisi?->telefon_2,
            ])
            ->action(function (array $data): void {
                $this->record->veliBilgisi()->updateOrCreate(
                    ['kayit_id' => $this->record->id],
                    [
                        'ad_soyad' => $data['ad_soyad'],
                        'eposta' => $data['eposta'] ?? null,
                        'telefon_1' => $data['telefon_1'],
                        'telefon_2' => $data['telefon_2'] ?? null,
                    ]
                );

                $this->kaydiYenile();
                $this->basariliBildirimGonder('Veli bilgileri güncellendi');
            });
    }

    private function okulBilgisiDuzenleAksiyonu(): InfolistAction
    {
        return InfolistAction::make('okul_bilgisi_duzenle')
            ->label('')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->outlined()
            ->form([
                TextInput::make('okul_adi')->label('Okul Adı')->maxLength(255),
                TextInput::make('okul_numarasi')->label('Okul Numarası')->maxLength(50),
                TextInput::make('sube')->label('Şube')->maxLength(10),
                Textarea::make('not')->label('Not')->rows(3)->columnSpanFull(),
            ])
            ->fillForm(fn (): array => [
                'okul_adi' => $this->record->okulBilgisi?->okul_adi,
                'okul_numarasi' => $this->record->okulBilgisi?->okul_numarasi,
                'sube' => $this->record->okulBilgisi?->sube,
                'not' => $this->record->okulBilgisi?->not,
            ])
            ->action(function (array $data): void {
                $this->record->okulBilgisi()->updateOrCreate(
                    ['kayit_id' => $this->record->id],
                    [
                        'okul_adi' => $data['okul_adi'] ?? null,
                        'okul_numarasi' => $data['okul_numarasi'] ?? null,
                        'sube' => $data['sube'] ?? null,
                        'not' => $data['not'] ?? null,
                    ]
                );

                $this->kaydiYenile();
                $this->basariliBildirimGonder('Okul bilgileri güncellendi');
            });
    }

    private function kimlikBilgisiDuzenleAksiyonu(): InfolistAction
    {
        return InfolistAction::make('kimlik_bilgisi_duzenle')
            ->label('')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->outlined()
            ->form([
                TextInput::make('kayitli_il')->label('Kayıtlı İl')->maxLength(100),
                TextInput::make('kayitli_ilce')->label('Kayıtlı İlçe')->maxLength(100),
                TextInput::make('kayitli_mahalle_koy')->label('Mahalle/Köy')->maxLength(255),
                TextInput::make('cilt_no')->label('Cilt No')->maxLength(50),
                TextInput::make('aile_sira_no')->label('Aile Sıra No')->maxLength(50),
                TextInput::make('sira_no')->label('Sıra No')->maxLength(50),
                TextInput::make('cuzdanin_verildigi_yer')->label('Cüzdanın Verildiği Yer')->maxLength(255),
                TextInput::make('kimlik_seri_no')->label('Kimlik Seri No')->maxLength(50),
                TextInput::make('kan_grubu')->label('Kan Grubu')->maxLength(10),
            ])
            ->fillForm(fn (): array => [
                'kayitli_il' => $this->record->kimlikBilgisi?->kayitli_il,
                'kayitli_ilce' => $this->record->kimlikBilgisi?->kayitli_ilce,
                'kayitli_mahalle_koy' => $this->record->kimlikBilgisi?->kayitli_mahalle_koy,
                'cilt_no' => $this->record->kimlikBilgisi?->cilt_no,
                'aile_sira_no' => $this->record->kimlikBilgisi?->aile_sira_no,
                'sira_no' => $this->record->kimlikBilgisi?->sira_no,
                'cuzdanin_verildigi_yer' => $this->record->kimlikBilgisi?->cuzdanin_verildigi_yer,
                'kimlik_seri_no' => $this->record->kimlikBilgisi?->kimlik_seri_no,
                'kan_grubu' => $this->record->kimlikBilgisi?->kan_grubu,
            ])
            ->action(function (array $data): void {
                $this->record->kimlikBilgisi()->updateOrCreate(
                    ['kayit_id' => $this->record->id],
                    [
                        'kayitli_il' => $data['kayitli_il'] ?? null,
                        'kayitli_ilce' => $data['kayitli_ilce'] ?? null,
                        'kayitli_mahalle_koy' => $data['kayitli_mahalle_koy'] ?? null,
                        'cilt_no' => $data['cilt_no'] ?? null,
                        'aile_sira_no' => $data['aile_sira_no'] ?? null,
                        'sira_no' => $data['sira_no'] ?? null,
                        'cuzdanin_verildigi_yer' => $data['cuzdanin_verildigi_yer'] ?? null,
                        'kimlik_seri_no' => $data['kimlik_seri_no'] ?? null,
                        'kan_grubu' => $data['kan_grubu'] ?? null,
                    ]
                );

                $this->kaydiYenile();
                $this->basariliBildirimGonder('Kimlik bilgileri güncellendi');
            });
    }

    private function babaBilgisiDuzenleAksiyonu(): InfolistAction
    {
        return InfolistAction::make('baba_bilgisi_duzenle')
            ->label('')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->outlined()
            ->form([
                TextInput::make('dogum_yeri')->label('Doğum Yeri')->maxLength(255),
                TextInput::make('nufus_il_ilce')->label('Nüfus İl/İlçe')->maxLength(255),
            ])
            ->fillForm(fn (): array => [
                'dogum_yeri' => $this->record->babaBilgisi?->dogum_yeri,
                'nufus_il_ilce' => $this->record->babaBilgisi?->nufus_il_ilce,
            ])
            ->action(function (array $data): void {
                $this->record->babaBilgisi()->updateOrCreate(
                    ['kayit_id' => $this->record->id],
                    [
                        'dogum_yeri' => $data['dogum_yeri'] ?? null,
                        'nufus_il_ilce' => $data['nufus_il_ilce'] ?? null,
                    ]
                );

                $this->kaydiYenile();
                $this->basariliBildirimGonder('Baba bilgileri güncellendi');
            });
    }

    private function kaydiYenile(): void
    {
        $this->record->refresh();
        $this->record->load([
            'sinif.donem',
            'sinif.kurum',
            'ogrenciBilgisi',
            'kimlikBilgisi',
            'okulBilgisi',
            'veliBilgisi',
            'babaBilgisi',
            'yonetici',
        ]);
    }

    private function basariliBildirimGonder(string $mesaj): void
    {
        Notification::make()
            ->title($mesaj)
            ->success()
            ->send();
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

    private function hazirMesajVarMi(): bool
    {
        return EkayitHazirMesaj::query()->exists();
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
            ->label(match ($durum) {
                EkayitDurumu::Onaylandi => 'Onay',
                EkayitDurumu::Reddedildi => 'Red',
                EkayitDurumu::Yedek => 'Yedek',
                default => $durum->label(),
            })
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

    private function smsAksiyonuOlustur(string $ad, string $etiket, string $renk): InfolistAction
    {
        // $ad formatı: tel1_sms_onay, tel1_sms_red, tel1_sms_yedek
        //              tel2_sms_onay, tel2_sms_red, tel2_sms_yedek
        $telefonAlan = str_starts_with($ad, 'tel1') ? 'veliBilgisi.telefon_1' : 'veliBilgisi.telefon_2';
        $tip = match (true) {
            str_ends_with($ad, 'onay') => 'onaylandi',
            str_ends_with($ad, 'red') => 'reddedildi',
            str_ends_with($ad, 'yedek') => 'yedek',
            default => 'onaylandi',
        };
        $durumGuncellensin = str_starts_with($ad, 'tel1');

        return InfolistAction::make($ad)
            ->label($etiket)
            ->color($renk)
            ->action(function () use ($telefonAlan, $tip, $durumGuncellensin): void {
                $telefon = data_get($this->record, $telefonAlan);

                if (blank($telefon)) {
                    Notification::make()
                        ->title('Telefon numarası bulunamadı')
                        ->danger()
                        ->send();

                    return;
                }

                // Durum güncelle (sadece tel1 için)
                if ($durumGuncellensin) {
                    $durum = match ($tip) {
                        'onaylandi' => EkayitDurumu::Onaylandi,
                        'reddedildi' => EkayitDurumu::Reddedildi,
                        'yedek' => EkayitDurumu::Yedek,
                    };

                    $this->record->update([
                        'durum' => $durum,
                        'durum_tarihi' => now(),
                        'yonetici_id' => auth()->id(),
                    ]);

                    if (filled($this->record->veliBilgisi?->eposta)) {
                        dispatch(new EkayitDurumEpostasiJob(
                            $this->record->id,
                            $durum->value
                        ));
                    }

                    $this->record->refresh();
                }

                // SMS gönder
                dispatch(new EkayitSmsJob(
                    $this->record->id,
                    $tip,
                    (string) $telefon,
                    $durumGuncellensin
                ));

                Notification::make()
                    ->title('SMS gönderildi')
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
