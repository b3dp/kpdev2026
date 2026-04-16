<?php

namespace App\Filament\Resources\UyeResource\Pages;

use App\Filament\Resources\UyeResource;
use App\Models\MezunProfil;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewUye extends ViewRecord
{
    protected static string $resource = UyeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $mezunProfili = MezunProfil::where('uye_id', $this->record->id)->first();

        $schema = [
            Section::make('Üye Bilgileri')
                ->schema([
                    TextEntry::make('ad_soyad')
                        ->label('Ad Soyad'),
                    TextEntry::make('telefon')
                        ->label('Telefon'),
                    TextEntry::make('eposta')
                        ->label('E-posta'),
                    TextEntry::make('durum')
                        ->label('Durum')
                        ->badge()
                        ->colors([
                            'success' => 'aktif',
                            'warning' => 'beklemede',
                            'danger' => 'yasakli',
                            'gray' => 'pasif',
                        ]),
                ]),
        ];

        if ($mezunProfili) {
            $schema[] = Section::make('Mezun Profili')
                ->schema([
                    TextEntry::make('kurum')
                        ->label('Mezun Olunan Kurum')
                        ->getStateUsing(fn () => $mezunProfili->kurum?->ad ?? $mezunProfili->kurum_manuel ?? '—'),
                    TextEntry::make('mezuniyet_yili')
                        ->label('Mezuniyet Yılı')
                        ->getStateUsing(fn () => $mezunProfili->mezuniyet_yili),
                    TextEntry::make('hafiz')
                        ->label('Hafız')
                        ->getStateUsing(fn () => $mezunProfili->hafiz ? 'Evet' : 'Hayır'),
                    TextEntry::make('acik_adres')
                        ->label('Açık Adres')
                        ->getStateUsing(fn () => $mezunProfili->acik_adres ?: '—')
                        ->columnSpanFull(),
                    TextEntry::make('aciklama')
                        ->label('Açıklama')
                        ->getStateUsing(fn () => $mezunProfili->aciklama ?: '—')
                        ->columnSpanFull(),
                    TextEntry::make('durum')
                        ->label('Mezun Profili Durumu')
                        ->badge()
                        ->colors([
                            'success' => 'aktif',
                            'warning' => 'beklemede',
                            'danger' => 'reddedildi',
                        ])
                        ->formatStateUsing(fn () => [
                            'beklemede' => 'Beklemede',
                            'aktif' => 'Aktif',
                            'reddedildi' => 'Reddedildi',
                        ][$mezunProfili->durum] ?? $mezunProfili->durum)
                        ->getStateUsing(fn () => $mezunProfili->durum),
                    TextEntry::make('link')
                        ->label('Detay')
                        ->getStateUsing(fn () => 'Mezun Profili Detay')
                        ->url(fn () => route('filament.admin.resources.mezun-profil-resource.view', $mezunProfili->id))
                        ->openUrlInNewTab(),
                ]);
        }

        return $infolist->schema($schema);
    }
}
