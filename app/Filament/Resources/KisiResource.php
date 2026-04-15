<?php

namespace App\Filament\Resources;

use App\Data\TurkiyeIlceler;
use App\Data\TurkiyeIller;
use App\Enums\KisiCinsiyet;
use App\Filament\Resources\KisiResource\Pages;
use App\Models\Kisi;
use App\Models\Kurum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KisiResource extends Resource
{
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = Kisi::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Kişiler';

    protected static ?string $modelLabel = 'Kişi';

    protected static ?string $pluralModelLabel = 'Kişiler';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return static::izinVarMi('kisiler.listele');
    }

    public static function canCreate(): bool
    {
        return static::izinVarMi('kisiler.kaydet');
    }

    public static function canEdit($record): bool
    {
        return static::izinVarMi('kisiler.duzenle');
    }

    public static function canDelete($record): bool
    {
        return static::izinVarMi('kisiler.sil');
    }

    public static function canDeleteAny(): bool
    {
        return static::izinVarMi('kisiler.sil');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('ad')
                ->label('Ad')
                ->required()
                ->maxLength(100),

            TextInput::make('soyad')
                ->label('Soyad')
                ->required()
                ->maxLength(100),

            Select::make('cinsiyet')
                ->label('Cinsiyet')
                ->options(KisiCinsiyet::secenekler())
                ->default(KisiCinsiyet::Belirtilmemis->value)
                ->nullable(),

            DatePicker::make('dogum_tarihi')
                ->label('Doğum Tarihi')
                ->native(false)
                ->displayFormat('d.m.Y'),

            TextInput::make('tc_kimlik')
                ->label('TC')
                ->numeric()
                ->minLength(11)
                ->maxLength(11)
                ->unique(table: 'kisiler', column: 'tc_kimlik', ignoreRecord: true),

            TextInput::make('telefon')
                ->label('Telefon')
                ->tel()
                ->maxLength(20),

            TextInput::make('eposta')
                ->label('E-posta')
                ->email()
                ->maxLength(255),

            Textarea::make('adres')
                ->label('Adres')
                ->rows(3)
                ->columnSpanFull(),

            Select::make('il')
                ->label('İl')
                ->options(TurkiyeIller::secenekler())
                ->searchable()
                ->live(),

            Select::make('ilce')
                ->label('İlçe')
                ->options(fn (callable $get) => TurkiyeIlceler::ilceSecenekleri($get('il')))
                ->searchable()
                ->disabled(fn (callable $get) => blank($get('il'))),

            TextInput::make('meslek')
                ->label('Meslek')
                ->maxLength(255),

            Select::make('kurumlar')
                ->label('Kurum')
                ->relationship('kurumlar', 'ad')
                ->multiple()
                ->preload()
                ->searchable(),

            Textarea::make('notlar')
                ->label('Notlar')
                ->rows(4)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')
                    ->label('Ad Soyad')
                    ->formatStateUsing(fn (Kisi $record) => $record->full_ad)
                    ->searchable(['ad', 'soyad', 'telefon', 'eposta'])
                    ->sortable(),

                TextColumn::make('telefon')
                    ->label('Telefon')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('eposta')
                    ->label('E-posta')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('il')
                    ->label('İl')
                    ->sortable(),

                TextColumn::make('meslek')
                    ->label('Meslek')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('ai_onaylandi')
                    ->label('AI Onay')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('il')
                    ->label('İl')
                    ->options(TurkiyeIller::secenekler()),
                SelectFilter::make('cinsiyet')
                    ->label('Cinsiyet')
                    ->options(KisiCinsiyet::secenekler()),
                TernaryFilter::make('ai_onaylandi')
                    ->label('AI Onaylandı'),
                SelectFilter::make('kurum')
                    ->label('Kurum')
                    ->options(fn () => Kurum::query()->orderBy('ad')->pluck('ad', 'id')->all())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('kurumlar', fn (Builder $kurumSorgusu) => $kurumSorgusu->whereKey($data['value']));
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('onayla')
                    ->label(fn (Kisi $record) => $record->ai_onaylandi ? 'Onayı Kaldır' : 'Onayla')
                    ->icon(fn (Kisi $record) => $record->ai_onaylandi ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Kisi $record) => $record->ai_onaylandi ? 'warning' : 'success')
                    ->action(fn (Kisi $record) => $record->update(['ai_onaylandi' => ! $record->ai_onaylandi])),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('toplu_onayla')
                        ->label('Onayla')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['ai_onaylandi' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('toplu_onay_kaldir')
                        ->label('Onayı Kaldır')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['ai_onaylandi' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKisis::route('/'),
            'create' => Pages\CreateKisi::route('/create'),
            'edit' => Pages\EditKisi::route('/{record}/edit'),
        ];
    }
}