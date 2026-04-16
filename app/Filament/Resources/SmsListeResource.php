<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsListeResource\Pages;
use App\Models\SmsListe;
use App\Models\Yonetici;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SmsListeResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = SmsListe::class;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Listeler';

    protected static ?string $modelLabel = 'Liste';

    protected static ?string $pluralModelLabel = 'Listeler';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canViewAny(): bool
    {
        return static::izinlerdenBiriVarMi(['pazarlama_sms.listele', 'pazarlama_sms.goruntule']);
    }

    public static function canCreate(): bool
    {
        return static::izinVarMi('pazarlama_sms.kaydet');
    }

    public static function canEdit($record): bool
    {
        return static::izinVarMi('pazarlama_sms.kaydet')
            && static::kaydaErisimVarMi($record);
    }

    public static function canDelete($record): bool
    {
        return static::izinVarMi('pazarlama_sms.sil')
            && static::kaydaErisimVarMi($record);
    }

    public static function canView($record): bool
    {
        return static::izinlerdenBiriVarMi(['pazarlama_sms.listele', 'pazarlama_sms.goruntule'])
            && static::kaydaErisimVarMi($record);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('ad')
                ->label('Liste Adı')
                ->required()
                ->maxLength(255),

            Select::make('sahip_yonetici_id')
                ->label('Liste Sahibi')
                ->options(fn (): array => Yonetici::query()->orderBy('ad_soyad')->pluck('ad_soyad', 'id')->toArray())
                ->searchable()
                ->nullable()
                ->visible(fn (): bool => static::tumKayitlariGorebilirMi()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')
                    ->label('Liste')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kisiler_count')
                    ->counts('kisiler')
                    ->label('Kişi Sayısı')
                    ->sortable(),

                TextColumn::make('sahip.ad_soyad')
                    ->label('Sahip')
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'Genel Liste')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Oluşturma')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i') : '-')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (SmsListe $record): bool => static::canEdit($record)),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::tumKayitlariGorebilirMi()) {
            return $query;
        }

        return $query->where('sahip_yonetici_id', auth()->id());
    }

    protected static function tumKayitlariGorebilirMi(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    protected static function kaydaErisimVarMi($record): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (static::tumKayitlariGorebilirMi()) {
            return true;
        }

        return (int) $record->sahip_yonetici_id === (int) auth()->id();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsListeler::route('/'),
            'create' => Pages\CreateSmsListe::route('/create'),
            'view' => Pages\ViewSmsListe::route('/{record}'),
            'edit' => Pages\EditSmsListe::route('/{record}/edit'),
        ];
    }
}
