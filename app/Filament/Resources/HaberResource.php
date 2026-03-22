<?php

namespace App\Filament\Resources;

use App\Enums\HaberDurumu;
use App\Enums\HaberOncelik;
use App\Filament\Resources\HaberResource\Pages;
use App\Models\Etiket;
use App\Models\Haber;
use App\Models\HaberKategorisi;
use App\Models\Kisi;
use App\Models\Kurum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class HaberResource extends Resource
{
    protected static ?string $model = Haber::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Haberler';

    protected static ?string $modelLabel = 'Haber';

    protected static ?string $pluralModelLabel = 'Haberler';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 21;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Editör', 'Yazar']);
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
        return self::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return self::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Haber Bilgileri')
                ->schema([
                    TextInput::make('baslik')
                        ->label('Başlık')
                        ->required()
                        ->maxLength(60)
                        ->live(onBlur: true)
                        ->helperText(fn (?string $state) => mb_strlen((string) $state) . '/60')
                        ->afterStateUpdated(function (callable $set, ?string $state): void {
                            if (filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(100)
                        ->unique(table: 'haberler', column: 'slug', ignoreRecord: true),

                    Select::make('kategori_id')
                        ->label('Kategori')
                        ->relationship(
                            name: 'kategori',
                            titleAttribute: 'ad',
                            modifyQueryUsing: fn (Builder $query) => $query->where('aktif', true)->orderBy('sira')->orderBy('ad'),
                        )
                        ->searchable()
                        ->preload(),

                    Textarea::make('ozet')
                        ->label('Özet')
                        ->rows(4)
                        ->maxLength(300)
                        ->helperText(fn (?string $state) => mb_strlen((string) $state) . '/300'),

                    RichEditor::make('icerik')
                        ->label('İçerik')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'link',
                            'blockquote',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'undo',
                            'redo',
                        ])
                        ->columnSpanFull(),

                    FileUpload::make('gorseller')
                        ->label('Haber Görselleri')
                        ->disk('local')
                        ->directory('tmp/haberler')
                        ->visibility('private')
                        ->image()
                        ->multiple()
                        ->appendFiles()
                        ->maxFiles(20)
                        ->reorderable()
                        ->dehydrated(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(65536)
                        ->imagePreviewHeight('180')
                        ->helperText('JPG, JPEG, PNG, WEBP - maksimum 64MB. İlk sıradaki görsel ana görsel olur.'),

                    Placeholder::make('gorsel_lg_onizleme')
                        ->label('Mevcut Görsel Önizleme')
                        ->content(function (?Haber $record) {
                            if (! $record?->gorsel_lg) {
                                return 'Henüz optimize görsel yok.';
                            }

                            return new \Illuminate\Support\HtmlString(
                                '<img src="' . e($record->gorsel_lg) . '" style="max-width: 100%; border-radius: 8px;" alt="Haber görsel önizleme" />'
                            );
                        }),

                    Select::make('etiketler')
                        ->label('Etiketler')
                        ->relationship('etiketler', 'ad')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('ad')->label('Etiket')->required()->maxLength(120),
                        ]),

                    Select::make('kisiler')
                        ->label('Kişiler')
                        ->relationship(
                            name: 'kisiler',
                            titleAttribute: 'ad',
                            modifyQueryUsing: fn (Builder $query) => $query->orderBy('ad')->orderBy('soyad'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Kisi $record) => $record->full_ad)
                        ->multiple()
                        ->preload()
                        ->searchable(['ad', 'soyad']),

                    Select::make('kurumlar')
                        ->label('Kurumlar')
                        ->relationship('kurumlar', 'ad')
                        ->multiple()
                        ->preload()
                        ->searchable(['ad']),
                ])
                ->columns(2),

            Section::make('SEO ve Yayın Ayarları')
                ->schema([
                    Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->rows(3)
                        ->maxLength(160)
                        ->helperText(fn (?string $state) => mb_strlen((string) $state) . '/160'),

                    Select::make('robots')
                        ->label('Robots')
                        ->options([
                            'index' => 'index',
                            'noindex' => 'noindex',
                            'noindex_nofollow' => 'noindex,nofollow',
                        ])
                        ->default('index')
                        ->required(),

                    TextInput::make('canonical_url')
                        ->label('Canonical URL')
                        ->url()
                        ->maxLength(255),

                    Toggle::make('manset')
                        ->label('Manşet')
                        ->helperText('En fazla 10 haber manşet olabilir.')
                        ->disabled(fn (?Haber $record) => ! ($record?->manset) && Haber::mansetSayisi() >= 10)
                        ->live()
                        ->afterStateUpdated(function (callable $set, bool $state): void {
                            if ($state) {
                                $set('oncelik', HaberOncelik::Manset->value);
                            }
                        }),

                    DateTimePicker::make('yayin_tarihi')
                        ->label('Yayın Tarihi')
                        ->seconds(false),

                    DateTimePicker::make('yayin_bitis')
                        ->label('Yayın Bitiş')
                        ->seconds(false),

                    Select::make('oncelik')
                        ->label('Öncelik')
                        ->options(HaberOncelik::secenekler())
                        ->default(HaberOncelik::Normal->value)
                        ->required(),

                    Select::make('durum')
                        ->label('Durum')
                        ->options(HaberDurumu::secenekler())
                        ->default(HaberDurumu::Taslak->value)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('baslik')
                    ->label('Başlık')
                    ->description(fn (Haber $record) => $record->slug)
                    ->url(fn (Haber $record) => '/haberler/' . $record->slug, shouldOpenInNewTab: true)
                    ->searchable(['baslik', 'ozet', 'icerik'])
                    ->sortable(),

                TextColumn::make('kategori.ad')
                    ->label('Kategori')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (HaberDurumu|string|null $state) => $state instanceof HaberDurumu
                        ? $state->label()
                        : HaberDurumu::tryFrom((string) $state)?->label() ?? $state)
                    ->color(fn (HaberDurumu|string|null $state) => match ($state instanceof HaberDurumu ? $state : HaberDurumu::tryFrom((string) $state)) {
                        HaberDurumu::Taslak => 'gray',
                        HaberDurumu::Incelemede => 'warning',
                        HaberDurumu::Yayinda => 'success',
                        HaberDurumu::Reddedildi => 'danger',
                        HaberDurumu::Arsivde => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('oncelik')
                    ->label('Öncelik')
                    ->badge()
                    ->formatStateUsing(fn (HaberOncelik|string|null $state) => $state instanceof HaberOncelik
                        ? $state->label()
                        : HaberOncelik::tryFrom((string) $state)?->label() ?? $state)
                    ->sortable(),

                IconColumn::make('manset')
                    ->label('Manşet')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('yayin_tarihi')
                    ->label('Yayın Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('goruntuleme')
                    ->label('Görüntüleme')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options(HaberDurumu::secenekler()),
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->options(fn () => HaberKategorisi::query()->orderBy('ad')->pluck('ad', 'id')->all()),
                TernaryFilter::make('manset')
                    ->label('Manşet'),
                Filter::make('yayin_tarihi')
                    ->label('Yayın Tarihi Aralığı')
                    ->form([
                        DatePicker::make('baslangic')->label('Başlangıç'),
                        DatePicker::make('bitis')->label('Bitiş'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['baslangic'] ?? null, fn (Builder $q, $date) => $q->whereDate('yayin_tarihi', '>=', $date))
                            ->when($data['bitis'] ?? null, fn (Builder $q, $date) => $q->whereDate('yayin_tarihi', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('yayinla')
                    ->label('Yayınla')
                    ->color('success')
                    ->icon('heroicon-o-check-badge')
                    ->visible(function (Haber $record): bool {
                        $durum = $record->durum instanceof HaberDurumu
                            ? $record->durum
                            : HaberDurumu::tryFrom((string) $record->durum);

                        return auth()->check()
                            && auth()->user()->hasRole('Editör')
                            && $durum !== HaberDurumu::Yayinda;
                    })
                    ->action(function (Haber $record): void {
                        $record->update(['durum' => HaberDurumu::Yayinda, 'yayin_tarihi' => $record->yayin_tarihi ?? now()]);
                    }),
                Action::make('arsivle')
                    ->label('Arşivle')
                    ->color('gray')
                    ->icon('heroicon-o-archive-box')
                    ->visible(fn (Haber $record) => $record->durum !== HaberDurumu::Arsivde)
                    ->action(fn (Haber $record) => $record->update(['durum' => HaberDurumu::Arsivde])),
                Action::make('mansete_al')
                    ->label('Manşete Al')
                    ->color('primary')
                    ->icon('heroicon-o-star')
                    ->visible(fn (Haber $record) => ! $record->manset)
                    ->action(function (Haber $record): void {
                        if (Haber::mansetSayisi() >= 10) {
                            Notification::make()->title('Manşet limiti dolu.')->danger()->send();
                            return;
                        }

                        $record->update(['manset' => true, 'oncelik' => HaberOncelik::Manset]);
                    }),
                Action::make('mansetten_cikar')
                    ->label('Manşetten Çıkar')
                    ->color('warning')
                    ->icon('heroicon-o-star')
                    ->visible(fn (Haber $record) => $record->manset)
                    ->action(fn (Haber $record) => $record->update(['manset' => false, 'oncelik' => HaberOncelik::Normal])),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['kategori', 'yonetici'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHabers::route('/'),
            'create' => Pages\CreateHaber::route('/create'),
            'edit' => Pages\EditHaber::route('/{record}/edit'),
        ];
    }
}
