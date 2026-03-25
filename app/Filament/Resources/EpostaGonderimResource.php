<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EpostaGonderimResource\Pages;
use App\Models\EpostaGonderim;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EpostaGonderimResource extends Resource
{
    protected static ?string $model = EpostaGonderim::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'E-posta Geçmişi';

    protected static ?string $modelLabel = 'Gönderim';

    protected static ?string $pluralModelLabel = 'E-posta Geçmişi';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 51;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sablon_kodu')
                    ->label('Şablon')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('alici_eposta')
                    ->label('Alıcı')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('alici_ad')
                    ->label('Ad')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('konu')
                    ->label('Konu')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (EpostaGonderim $record): string => $record->konu),

                TextColumn::make('durum')
                    ->label('Durum')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'gonderildi' => 'success',
                        'basarisiz'  => 'danger',
                        'beklemede'  => 'warning',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'gonderildi' => 'Gönderildi',
                        'basarisiz'  => 'Başarısız',
                        'beklemede'  => 'Beklemede',
                        default      => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options([
                        'beklemede'  => 'Beklemede',
                        'gonderildi' => 'Gönderildi',
                        'basarisiz'  => 'Başarısız',
                    ]),

                SelectFilter::make('sablon_kodu')
                    ->label('Şablon')
                    ->options(fn () => EpostaGonderim::query()
                        ->distinct()
                        ->pluck('sablon_kodu', 'sablon_kodu')
                        ->toArray()
                    ),

                Filter::make('tarih_araligi')
                    ->label('Tarih Aralığı')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('baslangic')->label('Başlangıç'),
                        \Filament\Forms\Components\DatePicker::make('bitis')->label('Bitiş'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['baslangic'], fn ($q) => $q->whereDate('created_at', '>=', $data['baslangic']))
                            ->when($data['bitis'], fn ($q) => $q->whereDate('created_at', '<=', $data['bitis']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEpostaGonderimleri::route('/'),
        ];
    }
}
