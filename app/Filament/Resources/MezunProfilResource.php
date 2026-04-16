<?php

namespace App\Filament\Resources;

use App\Data\TurkiyeIlceler;
use App\Data\TurkiyeIller;
use App\Enums\RozetTipi;
use App\Filament\Resources\MezunProfilResource\Pages;
use App\Models\EkayitSinif;
use App\Models\Kurum;
use App\Models\MezunProfil;
use App\Models\Uye;
use App\Models\UyeRozet;
use App\Models\Yonetici;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MezunProfilResource extends Resource
{
    protected static ?string $model = MezunProfil::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Mezunlar';

    protected static ?string $modelLabel = 'Mezun';

    protected static ?string $pluralModelLabel = 'Mezunlar';

    protected static ?string $navigationGroup = 'Üye Yönetimi';

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Halkla İlişkiler']);
    }

    public static function canView($record): bool
    {
        return self::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Halkla İlişkiler']);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Halkla İlişkiler']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Mezuniyet Bilgileri')
                ->schema([
                    Select::make('kurum_id')
                        ->label('Mezun Olunan Kurum')
                        ->relationship('kurum', 'ad')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    TextInput::make('kurum_manuel')
                        ->label('Kurum (Manuel Giriş)')
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make('mezuniyet_yili')
                        ->label('Mezuniyet Yılı')
                        ->numeric()
                        ->nullable()
                        ->minValue(1900)
                        ->maxValue(2099),

                    Select::make('sinif_id')
                        ->label('E-Kayıt Sınıf Eşleşmesi')
                        ->relationship('sinif', 'ad')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->nullable(),

                    Toggle::make('hafiz')
                        ->label('Hafız Mısınız?')
                        ->default(false),
                ])
                ->columns(2),

            Section::make('Mevcut Durum')
                ->schema([
                    TextInput::make('meslek')
                        ->label('Meslek / Görev')
                        ->maxLength(255)
                        ->nullable(),

                    Select::make('gorev_il')
                        ->label('Görev İli')
                        ->options(fn () => collect(TurkiyeIller::tumu())->mapWithKeys(fn ($il) => [$il => $il]))
                        ->searchable()
                        ->native(false)
                        ->nullable(),

                    Select::make('gorev_ilce')
                        ->label('Görev İlçesi')
                        ->options(fn ($get) => TurkiyeIlceler::ilceSecenekleri($get('gorev_il')))
                        ->searchable()
                        ->native(false)
                        ->nullable(),

                    Select::make('ikamet_il')
                        ->label('İkamet İli')
                        ->options(fn () => collect(TurkiyeIller::tumu())->mapWithKeys(fn ($il) => [$il => $il]))
                        ->searchable()
                        ->native(false)
                        ->nullable(),

                    Select::make('ikamet_ilce')
                        ->label('İkamet İlçesi')
                        ->options(fn ($get) => TurkiyeIlceler::ilceSecenekleri($get('ikamet_il')))
                        ->searchable()
                        ->native(false)
                        ->nullable(),

                    Textarea::make('acik_adres')
                        ->label('Açık Adres')
                        ->rows(3)
                        ->maxLength(2000)
                        ->nullable()
                        ->columnSpanFull(),

                    Textarea::make('aciklama')
                        ->label('Açıklama')
                        ->rows(3)
                        ->maxLength(2000)
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Sosyal Medya')
                ->schema([
                    TextInput::make('nsosyal')
                        ->label('NSosyal')
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make('facebook')
                        ->label('Facebook')
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make('youtube')
                        ->label('YouTube')
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make('linkedin')
                        ->label('LinkedIn')
                        ->url()
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make('instagram')
                        ->label('Instagram')
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make('twitter')
                        ->label('Twitter/X')
                        ->maxLength(255)
                        ->nullable(),
                ])
                ->columns(3),

            Section::make('Onay Durumu')
                ->schema([
                    ToggleButtons::make('durum')
                        ->label('Durum')
                        ->inline()
                        ->options([
                            'beklemede' => 'Beklemede',
                            'pasif' => 'Pasif',
                            'aktif' => 'Aktif',
                            'reddedildi' => 'Reddedildi',
                        ])
                        ->colors([
                            'beklemede' => 'warning',
                            'pasif' => 'gray',
                            'aktif' => 'success',
                            'reddedildi' => 'danger',
                        ])
                        ->required(),

                    TextInput::make('onaylayan_id')
                        ->label('Onaylayan')
                        ->disabled()
                        ->formatStateUsing(fn ($state) => $state ? Yonetici::find($state)?->ad_soyad : null),

                    DatePicker::make('onay_tarihi')
                        ->label('Onay Tarihi')
                        ->disabled(),

                    Textarea::make('red_notu')
                        ->label('Red Notu')
                        ->maxLength(1000)
                        ->nullable(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uye.ad_soyad')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mezuniyet_yili')
                    ->label('Mezuniyet Yılı')
                    ->sortable(),

                TextColumn::make('kurum.ad')
                    ->label('Kurum')
                    ->formatStateUsing(fn ($state, $record) => $state ?? $record->kurum_manuel ?? '—')
                    ->sortable(),

                IconColumn::make('hafiz')
                    ->label('Hafız')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('ikamet_il')
                    ->label('İkamet İli')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('durum')
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
                    ][$state] ?? $state)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options([
                        'beklemede' => 'Beklemede',
                        'pasif' => 'Pasif',
                        'aktif' => 'Aktif',
                        'reddedildi' => 'Reddedildi',
                    ])
                    ->multiple(),

                SelectFilter::make('hafiz')
                    ->label('Hafızlık')
                    ->options([
                        1 => 'Hafız',
                        0 => 'Hafız Değil',
                    ]),

                SelectFilter::make('kurum_id')
                    ->label('Kurum')
                    ->relationship('kurum', 'ad')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('ikamet_il')
                    ->label('İkamet İli')
                    ->options(fn () => collect(TurkiyeIller::tumu())->mapWithKeys(fn ($il) => [$il => $il])),

                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('onayla')
                    ->label('Onayla')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->durum === 'beklemede' && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Halkla İlişkiler']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'durum' => 'aktif',
                            'onaylayan_id' => auth()->id(),
                            'onay_tarihi' => now(),
                        ]);

                        // Mezun rozetini ekle
                        $mevcutRozet = UyeRozet::where('uye_id', $record->uye_id)
                            ->where('tip', RozetTipi::Mezun->value)
                            ->first();

                        if (!$mevcutRozet) {
                            UyeRozet::create([
                                'uye_id' => $record->uye_id,
                                'tip' => RozetTipi::Mezun->value,
                                'kazanilma_tarihi' => now(),
                                'kaynak_tip' => 'mezun_profil',
                                'kaynak_id' => $record->id,
                            ]);
                        }

                        app(\App\Services\KisiEslestirmeService::class)->mezunEslestir($record);

                        Notification::make()
                            ->success()
                            ->title('Başarılı')
                            ->body('Mezun profili onaylandı.')
                            ->send();
                    }),

                Action::make('reddet')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->durum, ['beklemede', 'aktif', 'pasif']) && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Halkla İlişkiler']))
                    ->form([
                        Textarea::make('red_notu')
                            ->label('Red Notu')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'durum' => 'reddedildi',
                            'red_notu' => $data['red_notu'],
                        ]);

                        // Mezun rozetini kaldır
                        UyeRozet::where('uye_id', $record->uye_id)
                            ->where('tip', RozetTipi::Mezun->value)
                            ->where('kaynak_tip', 'mezun_profil')
                            ->delete();

                        Notification::make()
                            ->success()
                            ->title('Başarılı')
                            ->body('Mezun profili reddedildi.')
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMezunProfiller::route('/'),
            'view' => Pages\ViewMezunProfil::route('/{record}'),
            'edit' => Pages\EditMezunProfil::route('/{record}/edit'),
        ];
    }
}
