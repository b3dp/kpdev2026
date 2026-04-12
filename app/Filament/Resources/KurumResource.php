<?php

namespace App\Filament\Resources;

use App\Data\TurkiyeIlceler;
use App\Data\TurkiyeIller;
use App\Enums\KurumTipi;
use App\Filament\Resources\KurumResource\Pages;
use App\Models\Kurum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class KurumResource extends Resource
{
    protected static ?string $model = Kurum::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Kurumlar';

    protected static ?string $modelLabel = 'Kurum';

    protected static ?string $pluralModelLabel = 'Kurumlar';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 11;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör']);
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
            TextInput::make('ad')
                ->label('Ad')
                ->required()
                ->maxLength(500),

            Select::make('tip')
                ->label('Tip')
                ->options(KurumTipi::secenekler())
                ->searchable()
                ->createOptionForm([
                    TextInput::make('tip')
                        ->label('Yeni Tip')
                        ->required(),
                ])
                ->createOptionUsing(function (array $data) {
                    return $data['tip'];
                })
                ->nullable(),

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

            TextInput::make('web_sitesi')
                ->label('Web Sitesi')
                ->url()
                ->maxLength(255),

            Textarea::make('aciklama')
                ->label('Açıklama')
                ->rows(4)
                ->columnSpanFull(),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')
                    ->label('Ad')
                    ->sortable()
                    ->searchable(['ad', 'il']),

                TextColumn::make('tip')
                    ->label('Tip')
                    ->formatStateUsing(fn (?string $state) => $state ? (KurumTipi::tryFrom($state)?->label() ?? $state) : null)
                    ->badge()
                    ->sortable(),

                TextColumn::make('il')
                    ->label('İl')
                    ->sortable(),

                TextColumn::make('telefon')
                    ->label('Telefon')
                    ->sortable(),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tip')
                    ->label('Tip')
                    ->options(KurumTipi::secenekler()),
                SelectFilter::make('il')
                    ->label('İl')
                    ->options(TurkiyeIller::secenekler()),
                TernaryFilter::make('aktif')
                    ->label('Aktif'),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('onayla')
                    ->label(fn (Kurum $record) => $record->aktif ? 'Pasif Yap' : 'Onayla')
                    ->icon(fn (Kurum $record) => $record->aktif ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Kurum $record) => $record->aktif ? 'warning' : 'success')
                    ->action(fn (Kurum $record) => $record->update(['aktif' => ! $record->aktif])),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('birlestir')
                        ->label('Birleştir')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('info')
                        ->requiresConfirmation(false)
                        ->form(fn (Collection $records): array => [
                            Radio::make('secilen_ad')
                                ->label('Hangi adı kullanmak istiyorsunuz?')
                                ->options($records->pluck('ad', 'ad')->toArray())
                                ->required()
                                ->helperText('Seçilen kurumlardan birinin adını seçin veya aşağıya farklı bir ad yazın.'),
                            TextInput::make('ozel_ad')
                                ->label('Ya da farklı bir ad yazın (bu alan doldurulursa seçimi geçersiz kılar)')
                                ->maxLength(500)
                                ->placeholder('Farklı bir kurum adı girin...'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $anaAd = filled($data['ozel_ad']) ? trim($data['ozel_ad']) : $data['secilen_ad'];

                            // Ana kurum: seçilen ada sahip ilk aktif kayıt, yoksa herhangi biri
                            $anaKurum = $records->first(fn (Kurum $k) => $k->ad === $data['secilen_ad'])
                                ?? $records->first();

                            // Ana kurumun adını güncelle
                            $anaKurum->update(['ad' => $anaAd, 'aktif' => true]);

                            $silinecekIds = $records
                                ->where('id', '!=', $anaKurum->id)
                                ->pluck('id')
                                ->all();

                            if (! empty($silinecekIds)) {
                                // Çakışan pivot kayıtları (aynı haberde ana kurum zaten varsa eski olanı sil)
                                DB::table('haber_kurumlar')
                                    ->whereIn('kurum_id', $silinecekIds)
                                    ->whereIn('haber_id', function ($q) use ($anaKurum) {
                                        $q->select('haber_id')
                                            ->from('haber_kurumlar')
                                            ->where('kurum_id', $anaKurum->id);
                                    })
                                    ->delete();

                                // Kalan referansları ana kuruma taşı
                                DB::table('haber_kurumlar')
                                    ->whereIn('kurum_id', $silinecekIds)
                                    ->update(['kurum_id' => $anaKurum->id]);

                                // Eski kurumları soft-delete et
                                Kurum::withTrashed()
                                    ->whereIn('id', $silinecekIds)
                                    ->update(['deleted_at' => now(), 'aktif' => false]);
                            }

                            Notification::make()
                                ->title('Kurumlar birleştirildi')
                                ->body("\"{$anaAd}\" adıyla " . (count($silinecekIds) + 1) . ' kurum tek kayda dönüştürüldü.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('toplu_onayla')
                        ->label('Onayla (Aktif)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['aktif' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('toplu_pasif_yap')
                        ->label('Pasif Yap')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['aktif' => false]))
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
            'index' => Pages\ListKurums::route('/'),
            'create' => Pages\CreateKurum::route('/create'),
            'edit' => Pages\EditKurum::route('/{record}/edit'),
        ];
    }
}