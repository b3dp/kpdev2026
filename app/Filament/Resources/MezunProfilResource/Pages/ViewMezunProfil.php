<?php

namespace App\Filament\Resources\MezunProfilResource\Pages;

use App\Filament\Resources\MezunProfilResource;
use App\Models\Uye;
use App\Models\UyeRozet;
use App\Enums\RozetTipi;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewMezunProfil extends ViewRecord
{
    protected static string $resource = MezunProfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(2)
                    ->schema([
                        // Card 1 — Mezuniyet Bilgileri
                        Section::make('Mezuniyet Bilgileri')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('kurum.ad')
                                    ->label('Mezun Olunan Kurum')
                                    ->formatStateUsing(fn ($state, $record) => $state ?? $record->kurum_manuel ?? '—'),

                                TextEntry::make('mezuniyet_yili')
                                    ->label('Mezuniyet Yılı'),

                                IconEntry::make('hafiz')
                                    ->label('Hafızlık Durumu')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle'),

                                TextEntry::make('sinif.ad')
                                    ->label('E-Kayıt Sınıf Eşleşmesi')
                                    ->default('—'),
                            ]),

                        // Card 2 — Mevcut Durum
                        Section::make('Mevcut Durum')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('meslek')
                                    ->label('Meslek / Görev')
                                    ->default('—'),

                                TextEntry::make('gorev_il')
                                    ->label('Görev İli')
                                    ->default('—'),

                                TextEntry::make('gorev_ilce')
                                    ->label('Görev İlçesi')
                                    ->default('—'),

                                TextEntry::make('ikamet_il')
                                    ->label('İkamet İli')
                                    ->default('—'),

                                TextEntry::make('ikamet_ilce')
                                    ->label('İkamet İlçesi')
                                    ->default('—'),

                                TextEntry::make('acik_adres')
                                    ->label('Açık Adres')
                                    ->default('—')
                                    ->columnSpanFull(),

                                TextEntry::make('aciklama')
                                    ->label('Açıklama')
                                    ->default('—')
                                    ->columnSpanFull(),
                            ]),

                        // Card 3 — İletişim ve Sosyal Medya
                        Section::make('İletişim ve Sosyal Medya')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('uye.telefon')
                                    ->label('Telefon'),

                                TextEntry::make('uye.eposta')
                                    ->label('E-posta'),

                                TextEntry::make('nsosyal')
                                    ->label('NSosyal')
                                    ->default('—'),

                                TextEntry::make('facebook')
                                    ->label('Facebook')
                                    ->default('—'),

                                TextEntry::make('youtube')
                                    ->label('YouTube')
                                    ->default('—'),

                                TextEntry::make('linkedin')
                                    ->label('LinkedIn')
                                    ->url(fn ($state) => filled($state) ? $state : null)
                                    ->openUrlInNewTab()
                                    ->default('—'),

                                TextEntry::make('instagram')
                                    ->label('Instagram')
                                    ->default('—'),

                                TextEntry::make('twitter')
                                    ->label('Twitter/X')
                                    ->default('—'),
                            ]),

                        // Card 4 — Onay Durumu
                        Section::make('Onay Durumu')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('durum')
                                    ->label('Durum')
                                    ->badge()
                                    ->colors([
                                        'warning' => 'beklemede',
                                        'gray' => 'pasif',
                                        'success' => 'aktif',
                                        'danger' => 'reddedildi',
                                    ])
                                    ->formatStateUsing(fn ($state) => [
                                        'beklemede' => 'Beklemede',
                                        'pasif' => 'Pasif',
                                        'aktif' => 'Aktif',
                                        'reddedildi' => 'Reddedildi',
                                    ][$state] ?? $state),

                                TextEntry::make('onaylayan.ad_soyad')
                                    ->label('Onaylayan')
                                    ->default('—'),

                                TextEntry::make('onay_tarihi')
                                    ->label('Onay Tarihi')
                                    ->formatStateUsing(fn ($state) => $state
                                        ? \Carbon\Carbon::parse($state)->format('d.m.Y H:i') : '—'),

                                TextEntry::make('red_notu')
                                    ->label('Red Notu')
                                    ->default('—')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
