<?php

namespace App\Filament\Resources;

use App\Data\TurkiyeIlceler;
use App\Data\TurkiyeIller;
use App\Enums\EtkinlikDurumu;
use App\Enums\EtkinlikTipi;
use App\Filament\Resources\EtkinlikResource\Pages;
use App\Jobs\AiEtkinlikIsleJob;
use App\Models\Etkinlik;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EtkinlikResource extends Resource
{
    protected static ?string $model = Etkinlik::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Etkinlikler';

    protected static ?string $modelLabel = 'Etkinlik';

    protected static ?string $pluralModelLabel = 'Etkinlikler';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 22;

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
        return self::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return self::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Temel Bilgiler')
                ->schema([
                    TextInput::make('baslik')
                        ->label('Başlık')
                        ->required()
                        ->maxLength(100)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => mb_strlen((string) ($state ?? ''), 'UTF-8') . '/100 karakter')
                        ->afterStateUpdated(function (callable $set, callable $get, ?string $state): void {
                            $slug = Str::slug((string) ($state ?? ''));
                            if (filled($slug)) {
                                $set('slug', mb_substr($slug, 0, 100, 'UTF-8'));
                            }

                            if (blank($get('seo_baslik'))) {
                                $set('seo_baslik', mb_substr((string) ($state ?? ''), 0, 60, 'UTF-8'));
                            }
                        }),

                    TextInput::make('seo_baslik')
                        ->label('SEO Başlığı')
                        ->maxLength(60)
                        ->nullable()
                        ->helperText(fn (?string $state) => mb_strlen((string) ($state ?? ''), 'UTF-8') . '/60 karakter - Boş bırakılırsa başlığın ilk 60 karakteri kullanılır'),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(100)
                        ->unique(table: 'etkinlikler', column: 'slug', ignoreRecord: true),

                    Select::make('tip')
                        ->label('Tip')
                        ->options(EtkinlikTipi::secenekler())
                        ->default(EtkinlikTipi::Fiziksel->value)
                        ->required()
                        ->live(),

                    Select::make('durum')
                        ->label('Durum')
                        ->options(EtkinlikDurumu::secenekler())
                        ->default(EtkinlikDurumu::Taslak->value)
                        ->required(),

                    Textarea::make('ozet')
                        ->label('Özet')
                        ->rows(4)
                        ->maxLength(300)
                        ->helperText(fn (?string $state) => mb_strlen((string) ($state ?? ''), 'UTF-8') . '/300 karakter'),

                    RichEditor::make('aciklama')
                        ->label('Açıklama')
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
                ])
                ->columns(2),

            Section::make('Tarih ve Konum')
                ->schema([
                    DateTimePicker::make('baslangic_tarihi')
                        ->label('Başlangıç Tarihi')
                        ->required()
                        ->seconds(false),

                    DateTimePicker::make('bitis_tarihi')
                        ->label('Bitiş Tarihi')
                        ->seconds(false),

                    TextInput::make('kontenjan')
                        ->label('Kontenjan')
                        ->numeric()
                        ->integer()
                        ->minValue(1),

                    TextInput::make('kayitli_kisi')
                        ->label('Kayıtlı Kişi')
                        ->numeric()
                        ->integer()
                        ->default(0)
                        ->minValue(0),

                    TextInput::make('konum_ad')
                        ->label('Konum Adı')
                        ->maxLength(255)
                        ->visible(fn (callable $get): bool => in_array((string) $get('tip'), [EtkinlikTipi::Fiziksel->value, EtkinlikTipi::Hibrit->value], true)),

                    Textarea::make('konum_adres')
                        ->label('Adres')
                        ->rows(3)
                        ->maxLength(500)
                        ->visible(fn (callable $get): bool => in_array((string) $get('tip'), [EtkinlikTipi::Fiziksel->value, EtkinlikTipi::Hibrit->value], true)),

                    Select::make('konum_il')
                        ->label('İl')
                        ->options(TurkiyeIller::secenekler())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('konum_ilce', null))
                        ->visible(fn (callable $get): bool => in_array((string) $get('tip'), [EtkinlikTipi::Fiziksel->value, EtkinlikTipi::Hibrit->value], true)),

                    Select::make('konum_ilce')
                        ->label('İlçe')
                        ->options(fn (callable $get) => TurkiyeIlceler::ilceSecenekleri($get('konum_il')))
                        ->searchable()
                        ->visible(fn (callable $get): bool => in_array((string) $get('tip'), [EtkinlikTipi::Fiziksel->value, EtkinlikTipi::Hibrit->value], true)),

                    Select::make('google_places_place_id')
                        ->label('Google Places Arama')
                        ->dehydrated(false)
                        ->searchable()
                        ->live(debounce: 600)
                        ->getSearchResultsUsing(function (string $search): array {
                            $arama = trim($search);
                            if (mb_strlen($arama, 'UTF-8') < 3) {
                                return [];
                            }

                            $apiKey = (string) (config('services.google_maps.api_key') ?: config('services.google_maps.public_api_key'));
                            if (blank($apiKey)) {
                                return [];
                            }

                            $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
                                'input' => $arama,
                                'language' => 'tr',
                                'components' => 'country:tr',
                                'key' => $apiKey,
                            ]);

                            if (! $response->ok()) {
                                return [];
                            }

                            return collect((array) data_get($response->json(), 'predictions', []))
                                ->take(10)
                                ->mapWithKeys(fn (array $tahmin) => [
                                    (string) data_get($tahmin, 'place_id') => (string) data_get($tahmin, 'description'),
                                ])
                                ->filter(fn (string $label, string $value) => filled($label) && filled($value))
                                ->all();
                        })
                        ->getOptionLabelUsing(function (mixed $value): ?string {
                            if (! is_string($value) || blank($value)) {
                                return null;
                            }

                            $apiKey = (string) (config('services.google_maps.api_key') ?: config('services.google_maps.public_api_key'));
                            if (blank($apiKey)) {
                                return null;
                            }

                            $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                                'place_id' => $value,
                                'language' => 'tr',
                                'fields' => 'name,formatted_address',
                                'key' => $apiKey,
                            ]);

                            if (! $response->ok()) {
                                return null;
                            }

                            $sonuc = (array) data_get($response->json(), 'result', []);

                            return (string) (data_get($sonuc, 'name') ?: data_get($sonuc, 'formatted_address') ?: $value);
                        })
                        ->afterStateUpdated(function (callable $set, ?string $state): void {
                            if (blank($state)) {
                                return;
                            }

                            $apiKey = (string) (config('services.google_maps.api_key') ?: config('services.google_maps.public_api_key'));
                            if (blank($apiKey)) {
                                return;
                            }

                            $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                                'place_id' => $state,
                                'language' => 'tr',
                                'fields' => 'name,formatted_address,geometry,place_id,address_component',
                                'key' => $apiKey,
                            ]);

                            if (! $response->ok()) {
                                return;
                            }

                            $sonuc = (array) data_get($response->json(), 'result', []);
                            $bilesenler = collect((array) data_get($sonuc, 'address_components', []));

                            $il = $bilesenler
                                ->first(fn (array $bilesen) => in_array('administrative_area_level_1', (array) data_get($bilesen, 'types', []), true));
                            $ilce = $bilesenler
                                ->first(fn (array $bilesen) => in_array('administrative_area_level_2', (array) data_get($bilesen, 'types', []), true));

                            $set('konum_place_id', (string) data_get($sonuc, 'place_id'));
                            $set('konum_ad', (string) data_get($sonuc, 'name'));
                            $set('konum_adres', (string) data_get($sonuc, 'formatted_address'));
                            $set('konum_lat', data_get($sonuc, 'geometry.location.lat'));
                            $set('konum_lng', data_get($sonuc, 'geometry.location.lng'));
                            $set('konum_il', (string) data_get($il, 'long_name'));
                            $set('konum_ilce', (string) data_get($ilce, 'long_name'));
                            $set('google_places_place_id', (string) data_get($sonuc, 'place_id'));
                        })
                        ->helperText(fn (): string => blank(config('services.google_maps.api_key')) && blank(config('services.google_maps.public_api_key'))
                            ? 'Google Maps API anahtarı bulunamadı. Lütfen GOOGLE_MAPS_API_KEY tanımlayın.'
                            : 'Konum aramak için en az 3 karakter yazın.')
                        ->visible(fn (callable $get): bool => in_array((string) $get('tip'), [EtkinlikTipi::Fiziksel->value, EtkinlikTipi::Hibrit->value], true)),

                    TextInput::make('konum_place_id')
                        ->label('Place ID')
                        ->maxLength(255)
                        ->visible(fn (callable $get): bool => in_array((string) $get('tip'), [EtkinlikTipi::Fiziksel->value, EtkinlikTipi::Hibrit->value], true)),

                    TextInput::make('konum_lat')
                        ->label('Enlem')
                        ->numeric()
                        ->step(0.0000001)
                        ->visible(fn (callable $get): bool => in_array((string) $get('tip'), [EtkinlikTipi::Fiziksel->value, EtkinlikTipi::Hibrit->value], true)),

                    TextInput::make('konum_lng')
                        ->label('Boylam')
                        ->numeric()
                        ->step(0.0000001)
                        ->visible(fn (callable $get): bool => in_array((string) $get('tip'), [EtkinlikTipi::Fiziksel->value, EtkinlikTipi::Hibrit->value], true)),

                    Placeholder::make('harita_onizleme')
                        ->label('Harita Önizleme')
                        ->content(function (callable $get): \Illuminate\Support\HtmlString {
                            $lat = $get('konum_lat');
                            $lng = $get('konum_lng');

                            if (! filled($lat) || ! filled($lng)) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Harita için enlem ve boylam giriniz.</p>');
                            }

                            $src = 'https://maps.google.com/maps?q=' . urlencode((string) $lat . ',' . (string) $lng) . '&z=15&output=embed';

                            return new \Illuminate\Support\HtmlString('<iframe src="' . e($src) . '" width="100%" height="240" style="border:0;border-radius:8px;" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>');
                        })
                        ->columnSpanFull()
                        ->visible(fn (callable $get): bool => in_array((string) $get('tip'), [EtkinlikTipi::Fiziksel->value, EtkinlikTipi::Hibrit->value], true)),

                    TextInput::make('online_link')
                        ->label('Online Link')
                        ->url()
                        ->maxLength(255)
                        ->visible(fn (callable $get): bool => in_array((string) $get('tip'), [EtkinlikTipi::Online->value, EtkinlikTipi::Hibrit->value], true)),
                ])
                ->columns(2),

            Section::make('Görseller')
                ->schema([
                    FileUpload::make('ana_gorsel_gecici')
                        ->label('Ana Görsel')
                        ->disk('local')
                        ->directory('tmp/etkinlikler')
                        ->visibility('private')
                        ->image()
                        ->maxFiles(1)
                        ->dehydrated(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(65536)
                        ->imagePreviewHeight('180')
                        ->helperText('Tek görsel. Önerilen: en az 1280x720 piksel. JPG, PNG veya WEBP, maks 64MB.'),

                    Placeholder::make('gorsel_lg_onizleme')
                        ->label('Mevcut Ana Görsel')
                        ->content(function (?Etkinlik $record): \Illuminate\Support\HtmlString {
                            if (! $record?->gorsel_lg) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Henüz ana görsel yok.</p>');
                            }

                            return new \Illuminate\Support\HtmlString(
                                '<img src="' . e($record->gorsel_lg) . '" style="max-width:100%;max-height:200px;border-radius:8px;object-fit:cover;" alt="Ana görsel önizleme" />'
                            );
                        }),

                    FileUpload::make('galeri_gorseller')
                        ->label('Galeri')
                        ->disk('local')
                        ->directory('tmp/etkinlikler')
                        ->visibility('private')
                        ->image()
                        ->multiple()
                        ->appendFiles()
                        ->maxFiles(30)
                        ->reorderable()
                        ->dehydrated(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(65536)
                        ->imagePreviewHeight('120')
                        ->helperText('Ek galeri görselleri. Sürükleyerek sıralayabilirsiniz. Her biri maks 64MB.'),

                    Placeholder::make('galeri_onizleme')
                        ->label('Mevcut Galeri')
                        ->content(function (?Etkinlik $record): \Illuminate\Support\HtmlString {
                            if (! $record || $record->gorseller()->count() === 0) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Henüz galeri görseli yok.</p>');
                            }

                            $html = '<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-top:8px;">';
                            foreach ($record->gorseller as $gorsel) {
                                $url = $gorsel->lgUrl();
                                $html .= '<div style="position:relative;">'
                                    . '<img src="' . e($url) . '" style="border-radius:6px;width:100%;height:96px;object-fit:cover;">'
                                    . '<span style="position:absolute;top:4px;left:4px;background:rgba(0,0,0,0.55);color:#fff;font-size:11px;padding:1px 5px;border-radius:4px;">' . $gorsel->sira . '</span>'
                                    . '</div>';
                            }
                            $html .= '</div>';

                            return new \Illuminate\Support\HtmlString($html);
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('SEO')
                ->schema([
                    Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->rows(3)
                        ->maxLength(160)
                        ->helperText(fn (?string $state) => mb_strlen((string) ($state ?? ''), 'UTF-8') . '/160 karakter'),

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

                    Actions::make([
                        FormAction::make('ai_baslat')
                            ->label('AI İşlemlerini Başlat')
                            ->icon('heroicon-o-sparkles')
                            ->color('primary')
                            ->visible(fn (?Etkinlik $record): bool => filled($record?->id))
                            ->requiresConfirmation()
                            ->action(function (?Etkinlik $record): void {
                                if (! $record) {
                                    return;
                                }

                                $record->update(['ai_islendi' => false]);
                                AiEtkinlikIsleJob::dispatch($record->id);

                                Notification::make()
                                    ->title('AI işlemi sıraya alındı.')
                                    ->success()
                                    ->send();
                            }),
                    ]),
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
                    ->limit(80)
                    ->tooltip(fn (Etkinlik $record) => $record->baslik)
                    ->searchable(['baslik', 'konum_ad', 'ozet'])
                    ->sortable(),

                TextColumn::make('tip')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn (EtkinlikTipi|string|null $state) => $state instanceof EtkinlikTipi
                        ? $state->label()
                        : EtkinlikTipi::tryFrom((string) $state)?->label() ?? $state)
                    ->color(fn (EtkinlikTipi|string|null $state) => match ($state instanceof EtkinlikTipi ? $state : EtkinlikTipi::tryFrom((string) $state)) {
                        EtkinlikTipi::Fiziksel => 'primary',
                        EtkinlikTipi::Online => 'success',
                        EtkinlikTipi::Hibrit => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (EtkinlikDurumu|string|null $state) => $state instanceof EtkinlikDurumu
                        ? $state->label()
                        : EtkinlikDurumu::tryFrom((string) $state)?->label() ?? $state)
                    ->color(fn (EtkinlikDurumu|string|null $state) => match ($state instanceof EtkinlikDurumu ? $state : EtkinlikDurumu::tryFrom((string) $state)) {
                        EtkinlikDurumu::Taslak => 'gray',
                        EtkinlikDurumu::Yayinda => 'success',
                        EtkinlikDurumu::Tamamlandi => 'info',
                        EtkinlikDurumu::Iptal => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('baslangic_tarihi')
                    ->label('Başlangıç Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('konum_ad')
                    ->label('Konum')
                    ->formatStateUsing(function (?string $state, Etkinlik $record): string {
                        $tip = $record->tip instanceof EtkinlikTipi
                            ? $record->tip
                            : EtkinlikTipi::tryFrom((string) $record->tip);

                        if ($tip === EtkinlikTipi::Online) {
                            return 'Online';
                        }

                        return filled($state) ? $state : '-';
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kontenjan_durumu')
                    ->label('Kontenjan/Kayıtlı')
                    ->state(fn (Etkinlik $record) => ($record->kayitli_kisi ?? 0) . '/' . ($record->kontenjan ?? '-')),
            ])
            ->defaultSort('baslangic_tarihi', 'desc')
            ->filters([
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options(EtkinlikDurumu::secenekler()),
                SelectFilter::make('tip')
                    ->label('Tip')
                    ->options(EtkinlikTipi::secenekler()),
                SelectFilter::make('konum_il')
                    ->label('İl')
                    ->options(TurkiyeIller::secenekler()),
                Filter::make('baslangic_tarihi')
                    ->label('Başlangıç Tarihi Aralığı')
                    ->form([
                        DatePicker::make('baslangic')->label('Başlangıç'),
                        DatePicker::make('bitis')->label('Bitiş'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['baslangic'] ?? null, fn (Builder $q, $date) => $q->whereDate('baslangic_tarihi', '>=', $date))
                            ->when($data['bitis'] ?? null, fn (Builder $q, $date) => $q->whereDate('baslangic_tarihi', '<=', $date));
                    }),
                SelectFilter::make('kontenjan_dolu')
                    ->label('Kontenjan')
                    ->options([
                        'dolu' => 'Dolu',
                        'musait' => 'Müsait',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $deger = $data['value'] ?? null;

                        if ($deger === 'dolu') {
                            return $query->whereNotNull('kontenjan')->whereColumn('kayitli_kisi', '>=', 'kontenjan');
                        }

                        if ($deger === 'musait') {
                            return $query->where(function (Builder $alt) {
                                $alt->whereNull('kontenjan')
                                    ->orWhereColumn('kayitli_kisi', '<', 'kontenjan');
                            });
                        }

                        return $query;
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('yayinla')
                    ->label('Yayınla')
                    ->color('success')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn (Etkinlik $record) => $record->durum !== EtkinlikDurumu::Yayinda)
                    ->action(fn (Etkinlik $record) => $record->update(['durum' => EtkinlikDurumu::Yayinda])),
                Action::make('tamamlandi')
                    ->label('Tamamlandı Olarak İşaretle')
                    ->color('info')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Etkinlik $record) => $record->durum !== EtkinlikDurumu::Tamamlandi)
                    ->action(fn (Etkinlik $record) => $record->update(['durum' => EtkinlikDurumu::Tamamlandi])),
                Action::make('iptal')
                    ->label('İptal Et')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Etkinlik $record) => $record->durum !== EtkinlikDurumu::Iptal)
                    ->action(fn (Etkinlik $record) => $record->update(['durum' => EtkinlikDurumu::Iptal])),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['yonetici'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEtkinliks::route('/'),
            'create' => Pages\CreateEtkinlik::route('/create'),
            'edit' => Pages\EditEtkinlik::route('/{record}/edit'),
        ];
    }
}
