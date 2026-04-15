<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsKisiResource\Pages;
use App\Jobs\HermesAktarimJob;
use App\Models\SmsKisi;
use App\Models\SmsListe;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
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
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = SmsKisi::class;

    protected static ?string $navigationGroup = 'SMS Yönetimi';

    protected static ?string $navigationLabel = 'Rehber';

    protected static ?string $modelLabel = 'Kişi';

    protected static ?string $pluralModelLabel = 'Kişiler';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

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
        return static::izinVarMi('pazarlama_sms.kaydet');
    }

    public static function canDelete($record): bool
    {
        return static::izinVarMi('pazarlama_sms.sil');
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
                Action::make('hermes_aktar')
                    ->label('Hermes\'ten Aktar')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->form([
                        FileUpload::make('dosya')
                            ->label('Excel Dosyası')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required()
                            ->maxSize(10240), // 10MB
                    ])
                    ->action(function (array $data): void {
                        // Import job'ı dispatch et
                        HermesAktarimJob::dispatch($data['dosya'], auth()->id());

                        Notification::make()
                            ->title('Aktarım başlatıldı')
                            ->body('İşlem arka planda devam ediyor. Tamamlandığında log kayıtlarından sonucu görebilirsiniz.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => auth()->check() && auth()->user()->hasRole('Admin')),
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
