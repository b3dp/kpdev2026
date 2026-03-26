<?php

namespace App\Filament\Resources;

use App\Enums\RozetTipi;
use App\Enums\UyeDurumu;
use App\Filament\Resources\UyeResource\Pages;
use App\Models\Kisi;
use App\Models\Uye;
use App\Services\RozetService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UyeResource extends Resource
{
    protected static ?string $model = Uye::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Üyeler';

    protected static ?string $modelLabel = 'Üye';

    protected static ?string $pluralModelLabel = 'Üyeler';

    protected static ?string $navigationGroup = 'Üye Yönetimi';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
    }

    public static function canView($record): bool
    {
        return self::canViewAny();
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

    public static function canDeleteAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Üye Bilgileri')
                ->schema([
                    TextInput::make('ad_soyad')
                        ->label('Ad Soyad')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('telefon')
                        ->label('Telefon')
                        ->tel()
                        ->unique(table: 'uyeler', column: 'telefon', ignoreRecord: true)
                        ->maxLength(20),

                    TextInput::make('eposta')
                        ->label('E-posta')
                        ->email()
                        ->unique(table: 'uyeler', column: 'eposta', ignoreRecord: true)
                        ->maxLength(255),

                    Select::make('kisi_id')
                        ->label('Kişi')
                        ->relationship(
                            name: 'kisi',
                            titleAttribute: 'ad',
                            modifyQueryUsing: fn (Builder $query) => $query->orderBy('ad')->orderBy('soyad'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Kisi $record) => $record->full_ad)
                        ->searchable(['ad', 'soyad', 'telefon', 'eposta'])
                        ->preload(),

                    ToggleButtons::make('durum')
                        ->label('Durum')
                        ->inline()
                        ->options(UyeDurumu::secenekler())
                        ->colors([
                            UyeDurumu::Aktif->value => 'success',
                            UyeDurumu::Pasif->value => 'gray',
                            UyeDurumu::Beklemede->value => 'warning',
                            UyeDurumu::Yasakli->value => 'danger',
                        ])
                        ->default(UyeDurumu::Aktif->value)
                        ->required(),
                ])
                ->columns(2),

            Section::make('Abonelikler ve Doğrulama')
                ->schema([
                    Toggle::make('sms_abonelik')
                        ->label('SMS Aboneliği')
                        ->default(true),

                    Toggle::make('eposta_abonelik')
                        ->label('E-posta Aboneliği')
                        ->default(true),

                    Toggle::make('telefon_dogrulandi')
                        ->label('Telefon Doğrulandı')
                        ->default(false),

                    Toggle::make('eposta_dogrulandi')
                        ->label('E-posta Doğrulandı')
                        ->default(false),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad_soyad')
                    ->label('Ad Soyad')
                    ->searchable(['ad_soyad', 'telefon', 'eposta'])
                    ->sortable(),

                TextColumn::make('telefon')
                    ->label('Telefon')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('eposta')
                    ->label('E-posta')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (UyeDurumu|string|null $state) => $state instanceof UyeDurumu
                        ? $state->label()
                        : UyeDurumu::tryFrom((string) $state)?->label() ?? $state)
                    ->color(fn (UyeDurumu|string|null $state) => match ($state instanceof UyeDurumu ? $state : UyeDurumu::tryFrom((string) $state)) {
                        UyeDurumu::Aktif => 'success',
                        UyeDurumu::Beklemede => 'warning',
                        UyeDurumu::Yasakli => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('rozetler.tip')
                    ->label('Rozetler')
                    ->badge()
                    ->getStateUsing(fn (Uye $record) => $record->rozetler
                        ->map(fn ($rozet) => $rozet->tip->label())
                        ->values()
                        ->all())
                    ->listWithLineBreaks(),

                TextColumn::make('son_giris')
                    ->label('Son Giriş')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                IconColumn::make('sms_abonelik')
                    ->label('SMS')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options(UyeDurumu::secenekler()),
                SelectFilter::make('rozet')
                    ->label('Rozet Tipi')
                    ->options(RozetTipi::secenekler())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('rozetler', fn (Builder $rozetSorgusu) => $rozetSorgusu->where('tip', $data['value']));
                    }),
                TernaryFilter::make('sms_abonelik')
                    ->label('SMS Aboneliği'),
                TernaryFilter::make('eposta_abonelik')
                    ->label('E-posta Aboneliği'),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('rozet_ekle')
                    ->label('Rozet Ekle')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->form([
                        Select::make('tip')
                            ->label('Rozet')
                            ->options(RozetTipi::secenekler())
                            ->required(),
                        TextInput::make('kaynak_tip')
                            ->label('Kaynak Tipi')
                            ->maxLength(100),
                        TextInput::make('kaynak_id')
                            ->label('Kaynak ID')
                            ->numeric(),
                    ])
                    ->action(function (Uye $record, array $data): void {
                        app(RozetService::class)->rozetEkle(
                            $record,
                            RozetTipi::from($data['tip']),
                            $data['kaynak_tip'] ?: null,
                            filled($data['kaynak_id'] ?? null) ? (int) $data['kaynak_id'] : null,
                        );

                        Notification::make()
                            ->title('Rozet eklendi.')
                            ->success()
                            ->send();
                    }),
                Action::make('rozet_kaldir')
                    ->label('Rozet Kaldır')
                    ->icon('heroicon-o-minus-circle')
                    ->color('warning')
                    ->form([
                        Select::make('tip')
                            ->label('Rozet')
                            ->options(fn (Uye $record) => $record->rozetler
                                ->pluck('tip')
                                ->mapWithKeys(fn ($tip) => [
                                    ($tip instanceof RozetTipi ? $tip : RozetTipi::from($tip))->value => ($tip instanceof RozetTipi ? $tip : RozetTipi::from($tip))->label(),
                                ])
                                ->all())
                            ->required(),
                    ])
                    ->action(function (Uye $record, array $data): void {
                        app(RozetService::class)->rozetKaldir($record, RozetTipi::from($data['tip']));

                        Notification::make()
                            ->title('Rozet kaldırıldı.')
                            ->success()
                            ->send();
                    }),
                Action::make('pasife_al')
                    ->label('Pasife Al')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->hidden(fn (Uye $record) => $record->durum === UyeDurumu::Pasif)
                    ->action(function (Uye $record): void {
                        $record->forceFill(['durum' => UyeDurumu::Pasif])->save();

                        Notification::make()
                            ->title('Üye pasife alındı.')
                            ->success()
                            ->send();
                    }),
                Action::make('sil')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Uye $record): void {
                        $record->forceDelete();

                        Notification::make()
                            ->title('Üye kalıcı olarak silindi.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['kisi', 'rozetler'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUyeler::route('/'),
            'create' => Pages\CreateUye::route('/create'),
            'edit' => Pages\EditUye::route('/{record}/edit'),
        ];
    }
}