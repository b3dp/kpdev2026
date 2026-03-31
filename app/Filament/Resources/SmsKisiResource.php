<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsKisiResource\Pages;
use App\Models\SmsKisi;
use App\Models\SmsListe;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SmsKisiResource extends Resource
{
    protected static ?string $model = SmsKisi::class;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Rehber';

    protected static ?string $modelLabel = 'Kişi';

    protected static ?string $pluralModelLabel = 'Kişiler';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Kurs Yöneticisi']);
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('telefon')
                    ->label('Telefon')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ad_soyad')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => $state ?: '-'),

                TextColumn::make('listeler')
                    ->label('Listeler')
                    ->state(fn (SmsKisi $record): string => $record->listeler->pluck('ad')->implode(', '))
                    ->badge()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Kayıt')
                    ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d.m.Y H:i') : '-')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Action::make('excel_aktar')
                    ->label('Excel Aktar')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->action(function (): void {
                        Notification::make()
                            ->title('Excel aktarımı Faz 10A-3 ile eklenecek')
                            ->warning()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('telefon')
                ->label('Telefon')
                ->required()
                ->maxLength(20)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($set, ?string $state): void {
                    $set('telefon', self::telefonNormalize($state));
                }),

            TextInput::make('ad_soyad')
                ->label('Ad Soyad')
                ->maxLength(255)
                ->nullable(),

            Textarea::make('notlar')
                ->label('Notlar')
                ->rows(3)
                ->nullable(),

            CheckboxList::make('liste_idler')
                ->label('Listeler')
                ->options(fn (): array => self::erisebilirListeSecenekleri())
                ->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('listeler');

        if (! auth()->check() || auth()->user()->hasRole('Admin')) {
            return $query;
        }

        return $query
            ->whereHas('listeler', function (Builder $builder): void {
                $builder->where('sahip_yonetici_id', auth()->id());
            })
            ->distinct('sms_kisiler.id');
    }

    public static function telefonNormalize(?string $telefon): string
    {
        $temiz = preg_replace('/\D+/', '', (string) $telefon) ?? '';

        if (Str::startsWith($temiz, '90')) {
            $temiz = substr($temiz, 2);
        }

        if (Str::startsWith($temiz, '0')) {
            $temiz = substr($temiz, 1);
        }

        return $temiz;
    }

    public static function erisebilirListeSecenekleri(): array
    {
        $query = SmsListe::query()->orderBy('ad');

        if (auth()->check() && ! auth()->user()->hasRole('Admin')) {
            $query->where('sahip_yonetici_id', auth()->id());
        }

        return $query->pluck('ad', 'id')->toArray();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsKisiler::route('/'),
            'create' => Pages\CreateSmsKisi::route('/create'),
            'edit' => Pages\EditSmsKisi::route('/{record}/edit'),
        ];
    }
}
