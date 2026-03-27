<?php

namespace App\Filament\Resources\BagisResource\Pages;

use App\Enums\BagisDurumu;
use App\Filament\Resources\BagisResource;
use App\Models\Bagis;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewBagis extends ViewRecord
{
    protected static string $resource = BagisResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'kalemler.bagisTuru',
            'kisiler',
            'odemeHatalari',
        ]);
    }

    protected function resolveRecord(int|string $key): Model
    {
        return Bagis::query()
            ->with([
                'kalemler.bagisTuru',
                'kisiler',
                'odemeHatalari',
            ])
            ->findOrFail($key);
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
                    Section::make('Bağış Özeti')
                        ->icon('heroicon-o-heart')
                        ->schema([
                            TextEntry::make('bagis_no')->label('Bağış No')->copyable(),
                            TextEntry::make('toplam_tutar')
                                ->label('Toplam Tutar')
                                ->money('TRY')
                                ->weight('bold')
                                ->size(TextEntrySize::Large),
                            TextEntry::make('durum')->label('Durum')->badge()->formatStateUsing(function ($state): string {
                                $durum = $state instanceof BagisDurumu ? $state : BagisDurumu::tryFrom((string) $state);

                                return $durum?->label() ?? 'Bilinmiyor';
                            })->color(function ($state): string {
                                $durum = $state instanceof BagisDurumu ? $state : BagisDurumu::tryFrom((string) $state);

                                return match ($durum) {
                                    BagisDurumu::Odendi => 'success',
                                    BagisDurumu::Beklemede => 'warning',
                                    BagisDurumu::Hatali => 'danger',
                                    BagisDurumu::Iptal => 'gray',
                                    BagisDurumu::TerkEdildi => 'orange',
                                    default => 'gray',
                                };
                            }),
                            TextEntry::make('created_at')->label('Bağış Tarihi')->dateTime('d.m.Y H:i'),
                        ])
                        ->columnSpan(1),

                    Section::make('Ödeyenin Bilgileri')
                        ->icon('heroicon-o-user')
                        ->schema([
                            TextEntry::make('odeyen_ad')
                                ->label('Ad Soyad')
                                ->state(fn () => $this->odeyen()?->ad_soyad)
                                ->weight('bold')
                                ->size(TextEntrySize::Large)
                                ->copyable(),
                            TextEntry::make('odeyen_tc')
                                ->label('TC')
                                ->state(fn () => $this->odeyen()?->tc_kimlik)
                                ->copyable()
                                ->extraAttributes(['class' => 'font-mono border-t border-gray-200 pt-2']),
                            TextEntry::make('odeyen_telefon')
                                ->label('Telefon')
                                ->state(fn () => $this->odeyen()?->telefon)
                                ->copyable(),
                            TextEntry::make('odeyen_eposta')
                                ->label('E-posta')
                                ->state(fn () => $this->odeyen()?->eposta)
                                ->copyable(),
                        ])
                        ->footerActions([
                            InfolistAction::make('odeyen_ara')
                                ->label('Ara')
                                ->icon('heroicon-o-phone')
                                ->color('gray')
                                ->outlined()
                                ->url(fn () => $this->telefonLinki($this->odeyen()?->telefon))
                                ->openUrlInNewTab()
                                ->visible(fn () => filled($this->odeyen()?->telefon)),
                            InfolistAction::make('odeyen_whatsapp')
                                ->label('WhatsApp')
                                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                ->color('success')
                                ->url(fn () => $this->whatsappLinki($this->odeyen()?->telefon))
                                ->openUrlInNewTab()
                                ->visible(fn () => filled($this->odeyen()?->telefon)),
                            InfolistAction::make('odeyen_eposta')
                                ->label('E-posta Gönder')
                                ->icon('heroicon-o-envelope')
                                ->color('info')
                                ->outlined()
                                ->url(fn () => $this->epostaLinki($this->odeyen()?->eposta))
                                ->openUrlInNewTab()
                                ->visible(fn () => filled($this->odeyen()?->eposta)),
                        ])
                        ->columnSpan(1),

                    Section::make('Bağış Sahibi Bilgileri')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            TextEntry::make('sahip_ad')
                                ->label('Ad Soyad')
                                ->state(fn () => $this->sahip()?->ad_soyad)
                                ->weight('bold')
                                ->size(TextEntrySize::Large)
                                ->copyable(),
                            TextEntry::make('sahip_tc')
                                ->label('TC')
                                ->state(fn () => $this->sahip()?->tc_kimlik)
                                ->copyable()
                                ->extraAttributes(['class' => 'font-mono border-t border-gray-200 pt-2']),
                            TextEntry::make('sahip_telefon')
                                ->label('Telefon')
                                ->state(fn () => $this->sahip()?->telefon)
                                ->copyable(),
                            TextEntry::make('sahip_eposta')
                                ->label('E-posta')
                                ->state(fn () => $this->sahip()?->eposta)
                                ->copyable(),
                        ])
                        ->footerActions([
                            InfolistAction::make('sahip_ara')
                                ->label('Ara')
                                ->icon('heroicon-o-phone')
                                ->color('gray')
                                ->outlined()
                                ->url(fn () => $this->telefonLinki($this->sahip()?->telefon))
                                ->openUrlInNewTab()
                                ->visible(fn () => filled($this->sahip()?->telefon)),
                            InfolistAction::make('sahip_whatsapp')
                                ->label('WhatsApp')
                                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                ->color('success')
                                ->url(fn () => $this->whatsappLinki($this->sahip()?->telefon))
                                ->openUrlInNewTab()
                                ->visible(fn () => filled($this->sahip()?->telefon)),
                            InfolistAction::make('sahip_eposta')
                                ->label('E-posta Gönder')
                                ->icon('heroicon-o-envelope')
                                ->color('info')
                                ->outlined()
                                ->url(fn () => $this->epostaLinki($this->sahip()?->eposta))
                                ->openUrlInNewTab()
                                ->visible(fn () => filled($this->sahip()?->eposta)),
                        ])
                        ->visible(fn () => $this->sahipVarMi())
                        ->columnSpan(1),

                    Section::make('Ödeme Bilgileri')
                        ->icon('heroicon-o-credit-card')
                        ->schema([
                            TextEntry::make('odeme_saglayici')->label('Ödeme Sağlayıcısı'),
                            TextEntry::make('odeme_referans')->label('Referans No'),
                            TextEntry::make('odeme_tarihi')->label('Ödeme Tarihi')->dateTime('d.m.Y H:i'),
                            TextEntry::make('makbuz_yol')->label('Makbuz Linki')->placeholder('-'),
                        ])
                        ->columnSpan(1),

                    Section::make('Vekalet Bilgileri')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            TextEntry::make('vekalet_ad_soyad')->label('Vekalet Veren')->state(fn () => $this->sahip()?->vekalet_ad_soyad),
                            TextEntry::make('vekalet_tc')->label('Vekalet TC')->state(fn () => $this->sahip()?->vekalet_tc),
                            TextEntry::make('vekalet_telefon')->label('Vekalet Telefon')->state(fn () => $this->sahip()?->vekalet_telefon),
                        ])
                        ->visible(fn () => $this->vekaletVarMi())
                        ->columnSpan(1),

                    Section::make('Hissedar Bilgileri')
                        ->icon('heroicon-o-users')
                        ->schema([
                            TextEntry::make('hissedarlar')
                                ->label('Hissedarlar')
                                ->state(fn (): array => $this->hissedarSatirlari())
                                ->listWithLineBreaks(),
                        ])
                        ->visible(fn () => $this->hissedarVarMi())
                        ->columnSpan(1),

                    Section::make('Kurban Aktarım Bilgileri')
                        ->icon('heroicon-o-arrow-path')
                        ->schema([
                            TextEntry::make('kurban_aktarildi')
                                ->label('Aktarım Durumu')
                                ->badge()
                                ->formatStateUsing(fn (bool $state) => $state ? 'Aktarıldı' : 'Bekliyor'),
                            TextEntry::make('kurban_aktarim_tarih')
                                ->label('Aktarım Tarihi')
                                ->state(fn () => $this->record->updated_at?->format('d.m.Y H:i')),
                        ])
                        ->visible(fn () => $this->kurbanModuluVarMi())
                        ->columnSpan(1),

                    Section::make('Hata Bilgileri')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->iconColor('danger')
                        ->schema([
                            IconEntry::make('hata_ikonu')
                                ->label('Uyarı')
                                ->icon('heroicon-o-exclamation-triangle')
                                ->state(true)
                                ->color('danger'),
                            TextEntry::make('hata_kodu')
                                ->label('Hata Kodu')
                                ->state(fn () => $this->record->odemeHatalari->first()?->hata_kodu)
                                ->badge()
                                ->color('danger'),
                            TextEntry::make('hata_mesaji')
                                ->label('Hata Mesajı')
                                ->state(fn () => $this->record->odemeHatalari->first()?->hata_mesaji)
                                ->extraAttributes(['class' => 'rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-700']),
                            TextEntry::make('kart_son_haneler')->label('Kart Son Haneler')->state(fn () => $this->record->odemeHatalari->first()?->kart_son_haneler)->color('danger'),
                            TextEntry::make('banka_adi')->label('Banka Adı')->state(fn () => $this->record->odemeHatalari->first()?->banka_adi)->color('danger'),
                        ])
                        ->visible(fn () => $this->record->odemeHatalari->isNotEmpty())
                        ->extraAttributes(['class' => 'border-l-4 border-red-600'])
                        ->columnSpan(1),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    private function odeyen(): ?\App\Models\BagisKisi
    {
        return $this->record->kisiler->first(fn ($kisi) => in_array('odeyen', $kisi->tipListesi(), true));
    }

    private function sahip(): ?\App\Models\BagisKisi
    {
        return $this->record->kisiler->first(fn ($kisi) => in_array('sahip', $kisi->tipListesi(), true));
    }

    private function sahipVarMi(): bool
    {
        return $this->sahip() !== null;
    }

    private function vekaletVarMi(): bool
    {
        return $this->record->kalemler->contains(fn ($kalem) => (bool) $kalem->vekalet_onay);
    }

    private function hissedarVarMi(): bool
    {
        return $this->record->kalemler->contains(function ($kalem) {
            return $kalem->sahip_tipi === 'buyukbas_kurban' || $kalem->bagisTuru?->ozellik?->value === 'buyukbas_kurban' || $kalem->bagisTuru?->ozellik === 'buyukbas_kurban';
        });
    }

    private function kurbanModuluVarMi(): bool
    {
        return $this->record->kalemler->contains(fn ($kalem) => (bool) ($kalem->bagisTuru?->kurban_modulu));
    }

    private function hissedarSatirlari(): array
    {
        return $this->record->kisiler
            ->filter(fn ($kisi) => in_array('hissedar', $kisi->tipListesi(), true))
            ->sortBy('hisse_no')
            ->map(fn ($kisi) => sprintf('%d. %s (%s)', (int) $kisi->hisse_no, $kisi->ad_soyad, (string) ($kisi->tc_kimlik ?? '-')))
            ->values()
            ->all();
    }

    private function telefonLinki(?string $telefon): ?string
    {
        if (blank($telefon)) {
            return null;
        }

        $numara = preg_replace('/\D+/', '', (string) $telefon) ?: '';

        return $numara !== '' ? 'tel:'.$numara : null;
    }

    private function whatsappLinki(?string $telefon): ?string
    {
        if (blank($telefon)) {
            return null;
        }

        $numara = preg_replace('/\D+/', '', (string) $telefon) ?: '';
        if ($numara === '') {
            return null;
        }

        if (str_starts_with($numara, '0')) {
            $numara = substr($numara, 1);
        }

        return 'https://wa.me/90'.$numara;
    }

    private function epostaLinki(?string $eposta): ?string
    {
        return filled($eposta) ? 'mailto:'.$eposta : null;
    }
}
