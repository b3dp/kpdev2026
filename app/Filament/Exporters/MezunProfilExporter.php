<?php

namespace App\Filament\Exporters;

use App\Models\MezunProfil;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Tables\Columns;

class MezunProfilExporter extends Exporter
{
    protected static ?string $model = MezunProfil::class;

    public static function getColumns(): array
    {
        return [
            Columns\TextColumn::make('uye.ad_soyad')->label('Ad Soyad'),
            Columns\TextColumn::make('uye.telefon')->label('Telefon'),
            Columns\TextColumn::make('uye.eposta')->label('E-posta'),
            Columns\TextColumn::make('kurum.ad')->label('Kurum'),
            Columns\TextColumn::make('kurum_manuel')->label('Kurum (Manuel)'),
            Columns\TextColumn::make('mezuniyet_yili')->label('Mezuniyet Yılı'),
            Columns\IconColumn::make('hafiz')->label('Hafız'),
            Columns\TextColumn::make('meslek')->label('Meslek'),
            Columns\TextColumn::make('gorev_il')->label('Görev İli'),
            Columns\TextColumn::make('gorev_ilce')->label('Görev İlçesi'),
            Columns\TextColumn::make('ikamet_il')->label('İkamet İli'),
            Columns\TextColumn::make('ikamet_ilce')->label('İkamet İlçesi'),
            Columns\TextColumn::make('acik_adres')->label('Açık Adres'),
            Columns\TextColumn::make('aciklama')->label('Açıklama'),
            Columns\TextColumn::make('nsosyal')->label('NSosyal'),
            Columns\TextColumn::make('facebook')->label('Facebook'),
            Columns\TextColumn::make('youtube')->label('YouTube'),
            Columns\TextColumn::make('linkedin')->label('LinkedIn'),
            Columns\TextColumn::make('instagram')->label('Instagram'),
            Columns\TextColumn::make('twitter')->label('Twitter'),
            Columns\TextColumn::make('created_at')->label('Kayıt Tarihi')->dateTime('d.m.Y H:i'),
            Columns\TextColumn::make('durum')->label('Durum'),
        ];
    }

    public static function getFileName(Export $export): string
    {
        return "mezun-profiller-{$export->getKey()}";
    }
}

