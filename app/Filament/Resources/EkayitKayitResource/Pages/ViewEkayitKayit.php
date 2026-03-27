<?php

namespace App\Filament\Resources\EkayitKayitResource\Pages;

use App\Enums\EkayitDurumu;
use App\Filament\Resources\EkayitKayitResource;
use App\Models\EkayitKayit;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
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
                Section::make('Kayıt Durumu')->schema([
                    TextEntry::make('sinif.ad')->label('Sınıf'),
                    TextEntry::make('sinif.donem.ad')->label('Dönem'),
                    TextEntry::make('sinif.kurum.ad')->label('Kurum'),
                    TextEntry::make('durum')->label('Durum')->badge()
                        ->formatStateUsing(function ($state): string {
                            $d = $state instanceof EkayitDurumu ? $state : EkayitDurumu::tryFrom((string) $state);
                            return $d?->label() ?? (string) $state;
                        })
                        ->color(function ($state): string {
                            $d = $state instanceof EkayitDurumu ? $state : EkayitDurumu::tryFrom((string) $state);
                            return $d?->renk() ?? 'gray';
                        }),
                    TextEntry::make('durum_notu')->label('Durum Notu')->default('—'),
                    TextEntry::make('yonetici.ad_soyad')->label('İşlem Yapan Yönetici')->default('—'),
                    TextEntry::make('durum_tarihi')->label('Durum Tarihi')->dateTime('d.m.Y H:i')->default('—'),
                    TextEntry::make('genel_not')->label('Genel Not')->default('—'),
                    TextEntry::make('created_at')->label('Kayıt Tarihi')->dateTime('d.m.Y H:i'),
                ]),

                Section::make('Öğrenci Bilgileri')->schema([
                    TextEntry::make('ogrenciBilgisi.ad_soyad')->label('Ad Soyad'),
                    TextEntry::make('ogrenciBilgisi.tc_kimlik')->label('TC Kimlik'),
                    TextEntry::make('ogrenciBilgisi.dogum_tarihi')->label('Doğum Tarihi')->date('d.m.Y')->default('—'),
                    TextEntry::make('ogrenciBilgisi.dogum_yeri')->label('Doğum Yeri')->default('—'),
                    TextEntry::make('ogrenciBilgisi.baba_adi')->label('Baba Adı')->default('—'),
                    TextEntry::make('ogrenciBilgisi.anne_adi')->label('Anne Adı')->default('—'),
                    TextEntry::make('ogrenciBilgisi.ikamet_il')->label('İkamet İl')->default('—'),
                    TextEntry::make('ogrenciBilgisi.adres')->label('Adres')->default('—'),
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
                    TextEntry::make('kimlikBilgisi.cilt_no')->label('Cilt No')->default('—'),
                    TextEntry::make('kimlikBilgisi.aile_sira_no')->label('Aile Sıra No')->default('—'),
                    TextEntry::make('kimlikBilgisi.sira_no')->label('Sıra No')->default('—'),
                    TextEntry::make('kimlikBilgisi.kan_grubu')->label('Kan Grubu')->default('—'),
                ]),

                Section::make('Baba Bilgileri')->schema([
                    TextEntry::make('babaBilgisi.dogum_yeri')->label('Doğum Yeri')->default('—'),
                    TextEntry::make('babaBilgisi.nufus_il_ilce')->label('Nüfus İl/İlçe')->default('—'),
                ]),
            ]),
        ]);
    }
}
