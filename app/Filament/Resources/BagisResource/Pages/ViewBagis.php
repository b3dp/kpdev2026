<?php

namespace App\Filament\Resources\BagisResource\Pages;

use App\Filament\Resources\BagisResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBagis extends ViewRecord
{
    protected static string $resource = BagisResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Card 1 - Bağış Özeti')
                    ->schema([
                        TextEntry::make('bagis_no')->label('Bağış No'),
                        TextEntry::make('toplam_tutar')->label('Toplam Tutar')->money('TRY'),
                        TextEntry::make('durum')->label('Durum'),
                        TextEntry::make('created_at')->label('Bağış Tarihi')->dateTime('d.m.Y H:i'),
                    ])->columns(2),
                Section::make('Card 2 - Ödeyenin Bilgileri')
                    ->schema([
                        TextEntry::make('odeyen_ad')->label('Ad Soyad')->state(fn () => $this->record->kisiler->first()?->ad_soyad),
                        TextEntry::make('odeyen_telefon')->label('Telefon')->state(fn () => $this->record->kisiler->first()?->telefon),
                        TextEntry::make('odeyen_eposta')->label('E-posta')->state(fn () => $this->record->kisiler->first()?->eposta),
                    ])->columns(3),
                Section::make('Card 3 - Bağış Sahibi Bilgileri')
                    ->schema([
                        TextEntry::make('sahip_ad')->label('Ad Soyad')->state(fn () => $this->record->kisiler->first()?->ad_soyad),
                        TextEntry::make('sahip_tc')->label('TC')->state(fn () => $this->record->kisiler->first()?->tc_kimlik),
                    ])->columns(2),
                Section::make('Card 4 - Vekalet Bilgileri')
                    ->schema([
                        TextEntry::make('vekalet_ad_soyad')->label('Vekalet Veren')->state(fn () => $this->record->kisiler->first()?->vekalet_ad_soyad),
                        TextEntry::make('vekalet_tc')->label('Vekalet TC')->state(fn () => $this->record->kisiler->first()?->vekalet_tc),
                    ])->columns(2),
                Section::make('Card 5 - Hissedar Bilgileri')
                    ->schema([
                        TextEntry::make('hissedarlar')->label('Hissedarlar')->state(fn () => $this->record->kisiler->whereNotNull('hisse_no')->pluck('ad_soyad')->implode(', ')),
                    ]),
                Section::make('Card 6 - Ödeme Bilgileri')
                    ->schema([
                        TextEntry::make('odeme_saglayici')->label('Sağlayıcı'),
                        TextEntry::make('odeme_referans')->label('Referans No'),
                        TextEntry::make('odeme_tarihi')->label('Ödeme Tarihi')->dateTime('d.m.Y H:i'),
                        TextEntry::make('makbuz_yol')->label('Makbuz'),
                    ])->columns(2),
                Section::make('Card 7 - Kurban Aktarım Bilgileri')
                    ->schema([
                        TextEntry::make('kurban_aktarildi')->label('Aktarım')->badge()
                            ->formatStateUsing(fn (bool $state) => $state ? 'Aktarıldı' : 'Bekliyor'),
                    ]),
                Section::make('Card 8 - Hata Bilgileri')
                    ->schema([
                        TextEntry::make('hata')->label('Hata')->state(fn () => $this->record->odemeHatalari->first()?->hata_mesaji),
                        TextEntry::make('hata_kodu')->label('Hata Kodu')->state(fn () => $this->record->odemeHatalari->first()?->hata_kodu),
                    ])->visible(fn () => $this->record->odemeHatalari->isNotEmpty()),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
