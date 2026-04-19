<?php

namespace App\Filament\Resources\KurbanKayitResource\Pages;

use App\Enums\KurbanBildirimDurumu;
use App\Enums\KurbanBildirimSonucu;
use App\Enums\KurbanDurumu;
use App\Filament\Resources\BagisResource;
use App\Filament\Resources\KurbanKayitResource;
use App\Jobs\KurbanBildirimJob;
use App\Models\KurbanKayit;
use App\Services\KurbanService;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewKurbanKayit extends ViewRecord
{
    protected static string $resource = KurbanKayitResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'bagis',
            'bagisKalemi.bagisTuru',
            'kisiler',
            'bildirimler.kurbanKisi',
        ]);
    }

    protected function resolveRecord(int|string $key): Model
    {
        return KurbanKayit::query()
            ->with(['bagis', 'bagisKalemi.bagisTuru', 'kisiler', 'bildirimler.kurbanKisi'])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Grid::make([
                    'default' => 1,
                    'md' => 3,
                ])->schema([
                    Section::make('Kurban Özeti')
                        ->schema([
                            TextEntry::make('kurban_no')->label('Kurban No')->copyable(),
                            TextEntry::make('bagis_turu_adi')->label('Bağış Türü')->badge(),
                            TextEntry::make('durum')
                                ->label('Durum')
                                ->badge()
                                ->formatStateUsing(fn ($state) => ($state instanceof KurbanDurumu ? $state : KurbanDurumu::tryFrom((string) $state))?->label() ?? (string) $state)
                                ->color(fn ($state) => ($state instanceof KurbanDurumu ? $state : KurbanDurumu::tryFrom((string) $state))?->renk() ?? 'gray'),
                            TextEntry::make('kesim_tarihi')
                                ->label('Kesim Tarihi')
                                ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y H:i') : '—'),
                            TextEntry::make('hisse_sayisi')->label('Hisse Sayısı')->placeholder('—'),
                            TextEntry::make('bagis_no')
                                ->label('Bağış No')
                                ->state(fn () => $this->record->bagis?->bagis_no)
                                ->url(fn () => BagisResource::getUrl('view', ['record' => $this->record->bagis_id]))
                                ->openUrlInNewTab(),
                        ])
                        ->footerActions([
                            InfolistAction::make('kesildi_olarak_isaretle')
                                ->label('Kesildi Olarak İşaretle')
                                ->icon('heroicon-o-check-badge')
                                ->color('success')
                                ->form([
                                    \Filament\Forms\Components\TextInput::make('kesim_yeri')->label('Kesim Yeri')->maxLength(500),
                                    \Filament\Forms\Components\TextInput::make('kesim_gorevlisi')->label('Kesim Görevlisi')->maxLength(255),
                                    \Filament\Forms\Components\Textarea::make('not')->label('Not')->rows(3),
                                ])
                                ->action(function (array $data): void {
                                    $basarili = app(KurbanService::class)->kesildiOlarakIsaretle($this->record, $data);

                                    $this->record->refresh();

                                    Notification::make()
                                        ->title($basarili ? 'Kurban kesildi olarak işlendi' : 'Kurban güncellenemedi')
                                        ->{$basarili ? 'success' : 'danger'}()
                                        ->send();
                                })
                                ->requiresConfirmation()
                                ->visible(fn (): bool => $this->duzenlemeYetkisiVar() && ($this->record->durum?->value ?? $this->record->durum) === KurbanDurumu::Bekliyor->value),
                        ])
                        ->columnSpan(1),

                    Section::make('Sahip / Hissedar Bilgileri')
                        ->schema([
                            TextEntry::make('kisi_satirlari')
                                ->label('Kişiler')
                                ->state(fn (): array => $this->kisiSatirlari())
                                ->listWithLineBreaks(),
                        ])
                        ->columnSpan(2),

                    Section::make('Bildirim Durumu')
                        ->schema([
                            TextEntry::make('bildirim_durumu')
                                ->label('Genel Durum')
                                ->badge()
                                ->formatStateUsing(fn ($state) => ($state instanceof KurbanBildirimDurumu ? $state : KurbanBildirimDurumu::tryFrom((string) $state))?->label() ?? (string) $state)
                                ->color(fn ($state) => ($state instanceof KurbanBildirimDurumu ? $state : KurbanBildirimDurumu::tryFrom((string) $state))?->renk() ?? 'gray'),
                            TextEntry::make('bildirim_satirlari')
                                ->label('Detay')
                                ->state(fn (): array => $this->bildirimSatirlari())
                                ->listWithLineBreaks(),
                        ])
                        ->footerActions([
                            InfolistAction::make('bildirimleri_tekrar_dene')
                                ->label('Başarısızları Tekrar Dene')
                                ->icon('heroicon-o-arrow-path')
                                ->color('warning')
                                ->action(function (): void {
                                    KurbanBildirimJob::dispatch($this->record->id)->onQueue('default');

                                    Notification::make()
                                        ->title('Bildirim tekrar kuyruğa alındı')
                                        ->success()
                                        ->send();
                                })
                                ->visible(fn (): bool => $this->basarisizBildirimVarMi()),
                        ])
                        ->columnSpan(2),

                    Section::make('Kesim Bilgileri')
                        ->schema([
                            TextEntry::make('kesim_yeri')->label('Kesim Yeri')->placeholder('—'),
                            TextEntry::make('kesim_gorevlisi')->label('Kesim Görevlisi')->placeholder('—'),
                        ])
                        ->footerActions([
                            InfolistAction::make('kesim_bilgileri_duzenle')
                                ->label('Düzenle')
                                ->icon('heroicon-o-pencil-square')
                                ->color('primary')
                                ->form([
                                    \Filament\Forms\Components\TextInput::make('kesim_yeri')->label('Kesim Yeri')->default(fn () => $this->record->kesim_yeri)->maxLength(500),
                                    \Filament\Forms\Components\TextInput::make('kesim_gorevlisi')->label('Kesim Görevlisi')->default(fn () => $this->record->kesim_gorevlisi)->maxLength(255),
                                ])
                                ->action(function (array $data): void {
                                    $this->record->update([
                                        'kesim_yeri' => filled($data['kesim_yeri'] ?? null) ? trim((string) $data['kesim_yeri']) : null,
                                        'kesim_gorevlisi' => filled($data['kesim_gorevlisi'] ?? null) ? trim((string) $data['kesim_gorevlisi']) : null,
                                    ]);

                                    $this->record->refresh();

                                    Notification::make()
                                        ->title('Kesim bilgileri güncellendi')
                                        ->success()
                                        ->send();
                                })
                                ->visible(fn (): bool => $this->duzenlemeYetkisiVar()),
                        ])
                        ->columnSpan(1),

                    Section::make('Not')
                        ->schema([
                            TextEntry::make('not')->label('Not')->placeholder('—'),
                        ])
                        ->footerActions([
                            InfolistAction::make('not_duzenle')
                                ->label('Notu Kaydet')
                                ->icon('heroicon-o-pencil')
                                ->color('primary')
                                ->form([
                                    \Filament\Forms\Components\Textarea::make('not')
                                        ->label('Not')
                                        ->default(fn () => $this->record->not)
                                        ->rows(4),
                                ])
                                ->action(function (array $data): void {
                                    $this->record->update([
                                        'not' => filled($data['not'] ?? null) ? trim((string) $data['not']) : null,
                                    ]);

                                    $this->record->refresh();

                                    Notification::make()
                                        ->title('Not kaydedildi')
                                        ->success()
                                        ->send();
                                })
                                ->visible(fn (): bool => $this->duzenlemeYetkisiVar()),
                        ])
                        ->columnSpan(2),
                ]),
            ]);
    }

    private function duzenlemeYetkisiVar(): bool
    {
        return auth()->check() && auth()->user()->can('kurban.duzenle');
    }

    private function kisiSatirlari(): array
    {
        return $this->record->kisiler
            ->sortBy('hisse_no')
            ->map(function ($kisi): string {
                $etiket = $kisi->hisse_no ? $kisi->hisse_no.'. hisse' : 'Sahip';
                $telefon = $kisi->telefon ?: '—';
                $eposta = $kisi->eposta ?: '—';

                return sprintf('%s: %s | Tel: %s | E-posta: %s', $etiket, $kisi->ad_soyad, $telefon, $eposta);
            })
            ->values()
            ->all();
    }

    private function bildirimSatirlari(): array
    {
        return $this->record->kisiler
            ->map(function ($kisi): string {
                $sms = $this->sonBildirimDurumu($kisi->id, 'sms');
                $eposta = $this->sonBildirimDurumu($kisi->id, 'eposta');

                return sprintf('%s -> SMS %s | E-posta %s', $kisi->ad_soyad, $sms, $eposta);
            })
            ->values()
            ->all();
    }

    private function sonBildirimDurumu(int $kurbanKisiId, string $kanal): string
    {
        $bildirim = $this->record->bildirimler
            ->where('kurban_kisi_id', $kurbanKisiId)
            ->where('kanal', $kanal)
            ->sortByDesc('gonderim_tarihi')
            ->first();

        if (! $bildirim) {
            return '—';
        }

        return (($bildirim->durum?->value ?? $bildirim->durum) === KurbanBildirimSonucu::Gonderildi->value)
            ? '✅'
            : '❌';
    }

    private function basarisizBildirimVarMi(): bool
    {
        return $this->record->bildirimler
            ->contains(fn ($bildirim) => ($bildirim->durum?->value ?? $bildirim->durum) === KurbanBildirimSonucu::Basarisiz->value);
    }
}