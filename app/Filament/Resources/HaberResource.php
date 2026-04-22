<?php

namespace App\Filament\Resources;

use App\Enums\HaberDurumu;
use App\Enums\HaberOncelik;
use App\Filament\Resources\HaberResource\Pages;
use App\Models\Etiket;
use App\Models\Haber;
use App\Models\HaberGorseli;
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
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
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
    use \App\Support\PanelYetkiKontrolu;

    protected static ?string $model = Haber::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Haberler';

    protected static ?string $modelLabel = 'Haber';

    protected static ?string $pluralModelLabel = 'Haberler';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 21;

    public static function canViewAny(): bool
    {
        return static::izinVarMi('haberler.listele');
    }

    public static function canCreate(): bool
    {
        return static::izinVarMi('haberler.kaydet');
    }

    public static function canEdit($record): bool
    {
        return static::izinVarMi('haberler.duzenle');
    }

    public static function canDelete($record): bool
    {
        return static::izinVarMi('haberler.sil');
    }

    public static function canDeleteAny(): bool
    {
        return static::izinVarMi('haberler.sil');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Haber Bilgileri')
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
                        ->helperText(fn (?string $state) => mb_strlen((string) ($state ?? ''), 'UTF-8') . '/60 karakter - Boş bırakılırsa başlığın ilk 60 karakteri kullanılır')
                        ->placeholder('Boş bırakılırsa başlıktan otomatik oluşturulur'),

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

                    Select::make('ek_kategori_idleri')
                        ->label('Ek Kategoriler')
                        ->options(fn () => HaberKategorisi::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->pluck('ad', 'id')->all())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Select $component, ?Haber $record): void {
                            if (! $record) {
                                $component->state([]);

                                return;
                            }

                            $component->state(
                                $record->kategoriler()
                                    ->where('haber_kategorileri.id', '!=', $record->kategori_id)
                                    ->pluck('haber_kategorileri.id')
                                    ->all()
                            );
                        })
                        ->helperText('Bir haber birden çok kategoriye atanabilir. İlk kategori ana kategori olarak ayrı seçilir.'),

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

                    FileUpload::make('ana_gorsel_gecici')
                        ->label('Ana Görsel')
                        ->disk('local')
                        ->directory('tmp/haberler')
                        ->visibility('private')
                        ->image()
                        ->maxFiles(1)
                        ->dehydrated(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(65536)
                        ->imagePreviewHeight('180')
                        ->helperText('Tek görsel. Önerilen: en az 1280×720 piksel. JPG, PNG veya WEBP, maks 64MB.'),

                    Placeholder::make('gorsel_lg_onizleme')
                        ->label('Mevcut Ana Görsel')
                        ->content(function (?Haber $record): \Illuminate\Support\HtmlString {
                            if (! $record?->gorsel_lg) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Henüz ana görsel yok.</p>');
                            }

                            return new \Illuminate\Support\HtmlString(
                                '<img src="' . e($record->gorsel_lg) . '" style="max-width:100%;max-height:200px;border-radius:8px;object-fit:cover;" alt="Ana görsel önizleme" />'
                            );
                        }),

                    FileUpload::make('galeri_gorseller')
                        ->label('Galeri Görselleri')
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
                        ->imagePreviewHeight('120')
                        ->helperText('Ek görseller. Sürükleyerek sıralayabilirsiniz. Her biri maks 64MB.'),

                    Placeholder::make('galeri_onizleme')
                        ->label('Mevcut Galeri')
                        ->content(function (?Haber $record): \Illuminate\Support\HtmlString {
                            if (! $record || $record->gorseller()->count() === 0) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Henüz galeri görseli yok.</p>');
                            }

                            $html = '<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-top:8px;">';
                            foreach ($record->gorseller as $gorsel) {
                                $url = $gorsel->lgUrl();

                                if (config('app.debug')) {
                                    logger()->info('Haber galeri görsel önizleme URL kontrolü', [
                                        'haber_id' => $record->id,
                                        'gorsel_id' => $gorsel->id,
                                        'lg_yol' => $gorsel->lg_yol,
                                        'olusan_url' => $url,
                                    ]);
                                }

                                if (blank($url)) {
                                    continue;
                                }

                                $html .= '<div style="position:relative;">'
                                    . '<img src="' . e($url) . '" style="border-radius:6px;width:100%;height:96px;object-fit:cover;">'
                                    . '<span style="position:absolute;top:4px;left:4px;background:rgba(0,0,0,0.55);color:#fff;font-size:11px;padding:1px 5px;border-radius:4px;">' . $gorsel->sira . '</span>'
                                    . '</div>';
                            }
                            $html .= '</div>';

                            return new \Illuminate\Support\HtmlString($html);
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
                        ->options(function (): array {
                            $user = auth()->user();

                            if ($user?->hasAnyRole(['Admin', 'Editör'])) {
                                return HaberDurumu::secenekler();
                            }

                            return [HaberDurumu::Taslak->value => HaberDurumu::Taslak->label()];
                        })
                        ->default(HaberDurumu::Taslak->value)
                        ->required()
                        ->disabled(function (?Haber $record): bool {
                            if (! auth()->check() || ! auth()->user()->hasAnyRole(['Yazar', 'Halkla İlişkiler'])) {
                                return false;
                            }

                            $durum = $record?->durum instanceof HaberDurumu
                                ? $record->durum
                                : HaberDurumu::tryFrom((string) $record?->durum);

                            return $durum === HaberDurumu::Incelemede;
                        }),
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
                    ->tooltip(fn (Haber $record) => $record->baslik)
                    ->description(fn (Haber $record) => $record->slug)
                    ->url(fn (Haber $record) => '/haberler/' . $record->slug, shouldOpenInNewTab: true)
                    ->searchable(['baslik', 'ozet', 'icerik'])
                    ->sortable(),

                TextColumn::make('kategori.ad')
                    ->label('Kategoriler')
                    ->html()
                    ->formatStateUsing(function (Haber $record): string {
                        $kategoriler = collect([$record->kategori?->ad])
                            ->merge($record->kategoriler->pluck('ad'))
                            ->filter()
                            ->unique()
                            ->values();

                        if ($kategoriler->isEmpty()) {
                            return '<span style="font-size:11px;color:#94a3b8;">-</span>';
                        }

                        return $kategoriler
                            ->map(fn (string $ad) => '<span style="display:inline-block;margin:1px 4px 1px 0;padding:1px 6px;border:1px solid #cbd5e1;border-radius:999px;font-size:10px;line-height:1.2;white-space:nowrap;">' . e($ad) . '</span>')
                            ->implode('');
                    })
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (HaberDurumu|string|null $state) => $state instanceof HaberDurumu
                        ? $state->label()
                        : HaberDurumu::tryFrom((string) $state)?->label() ?? $state)
                    ->color(fn (HaberDurumu|string|null $state) => match ($state instanceof HaberDurumu ? $state : HaberDurumu::tryFrom((string) $state)) {
                        HaberDurumu::Taslak => 'gray',
                        HaberDurumu::Planli => 'info',
                        HaberDurumu::Incelemede => 'warning',
                        HaberDurumu::Yayinda => 'success',
                        HaberDurumu::Reddedildi => 'danger',
                        HaberDurumu::Arsivde => 'info',
                        default => 'gray',
                    })
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
                    ->options(fn () => HaberKategorisi::query()->orderBy('ad')->pluck('ad', 'id')->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $kategoriId = (int) ($data['value'] ?? 0);

                        if ($kategoriId <= 0) {
                            return $query;
                        }

                        return $query->where(function (Builder $altQuery) use ($kategoriId): void {
                            $altQuery
                                ->where('kategori_id', $kategoriId)
                                ->orWhereHas('kategoriler', fn (Builder $kategoriQuery) => $kategoriQuery->where('haber_kategorileri.id', $kategoriId));
                        });
                    }),
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
                EditAction::make()
                    ->iconButton()
                    ->visible(fn (Haber $record) => static::izinVarMi('haberler.duzenle')),
                DeleteAction::make()
                    ->iconButton()
                    ->visible(fn (Haber $record) => static::izinVarMi('haberler.sil')),
                RestoreAction::make()
                    ->iconButton(),
                ForceDeleteAction::make()
                    ->iconButton(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['kategori', 'kategoriler', 'yonetici'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        if (auth()->check() && auth()->user()->hasRole('Yazar')) {
            return $query->where('yonetici_id', auth()->id());
        }

        if (auth()->check() && auth()->user()->hasRole('Halkla İlişkiler')) {
            return $query->where('yonetici_id', auth()->id());
        }

        return $query;
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
