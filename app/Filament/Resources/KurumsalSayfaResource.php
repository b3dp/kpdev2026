<?php

namespace App\Filament\Resources;

use App\Enums\KurumsalSablonu;
use App\Enums\RobotsKurali;
use App\Filament\Resources\KurumsalSayfaResource\Pages;
use App\Models\Kurum;
use App\Models\KurumsalSayfa;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class KurumsalSayfaResource extends Resource
{
    protected static ?string $model = KurumsalSayfa::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Kurumsal Sayfalar';

    protected static ?string $modelLabel = 'Kurumsal Sayfa';

    protected static ?string $pluralModelLabel = 'Kurumsal Sayfalar';

    protected static ?string $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 23;

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
            Section::make('Temel Bilgiler')
                ->schema([
                    TextInput::make('ad')
                        ->label('Sayfa Adı')
                        ->required()
                        ->maxLength(255)
                        ->live(debounce: 500)
                        ->afterStateUpdated(function (callable $set, callable $get, ?string $state): void {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(table: 'kurumsal_sayfalar', column: 'slug', ignoreRecord: true),

                    Radio::make('sablon')
                        ->label('Şablon')
                        ->options(KurumsalSablonu::secenekler())
                        ->default(KurumsalSablonu::Standart->value)
                        ->inline()
                        ->inlineLabel(false)
                        ->live()
                        ->required(),

                    Select::make('ust_sayfa_id')
                        ->label('Üst Sayfa')
                        ->options(function (?KurumsalSayfa $record): array {
                            return KurumsalSayfa::query()
                                ->orderBy('sira')
                                ->orderBy('ad')
                                ->get()
                                ->filter(function (KurumsalSayfa $sayfa) use ($record): bool {
                                    if ($record && $sayfa->id === $record->id) {
                                        return false;
                                    }

                                    return $sayfa->altSayfaEklenebilirMi();
                                })
                                ->mapWithKeys(function (KurumsalSayfa $sayfa): array {
                                    $prefix = str_repeat('— ', max(0, $sayfa->seviye() - 1));
                                    return [$sayfa->id => $prefix . $sayfa->ad];
                                })
                                ->all();
                        })
                        ->searchable()
                        ->live()
                        ->helperText(function (callable $get): ?string {
                            $ustSayfaId = $get('ust_sayfa_id');
                            if (! filled($ustSayfaId)) {
                                return null;
                            }

                            $ustSayfa = KurumsalSayfa::query()->find($ustSayfaId);
                            if (! $ustSayfa?->altSayfaEklenebilirMi()) {
                                return 'Alt sayfa eklenemez';
                            }

                            return null;
                        }),

                    Select::make('kurum_id')
                        ->label('Kurum İlişkisi')
                        ->options(fn () => Kurum::query()->orderBy('ad')->pluck('ad', 'id')->all())
                        ->searchable()
                        ->visible(fn (callable $get): bool => $get('sablon') === KurumsalSablonu::Kurum->value),

                    ToggleButtons::make('durum')
                        ->label('Durum')
                        ->inline()
                        ->options([
                            'taslak' => 'Taslak',
                            'yayinda' => 'Yayında',
                        ])
                        ->colors([
                            'taslak' => 'gray',
                            'yayinda' => 'success',
                        ])
                        ->default('taslak')
                        ->required(),

                    TextInput::make('sira')
                        ->label('Sıra')
                        ->numeric()
                        ->integer()
                        ->default(0)
                        ->required(),
                ])
                ->columns(2),

            Section::make('İçerik')
                ->schema([
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

                    TextInput::make('video_embed_url')
                        ->label('Video Embed URL')
                        ->url()
                        ->nullable(),
                ])
                ->columns(2),

            Section::make('Görseller')
                ->schema([
                    FileUpload::make('ana_gorsel_gecici')
                        ->label('Sayfa Görseli')
                        ->disk('local')
                        ->directory('tmp/kurumsal-sayfalar')
                        ->visibility('private')
                        ->image()
                        ->maxFiles(1)
                        ->dehydrated(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(65536)
                        ->helperText('Bu alan yeni görsel yüklemek içindir. Kaydedilen mevcut görsel aşağıda gösterilir.'),

                    Placeholder::make('mevcut_sayfa_gorseli')
                        ->label('Mevcut Sayfa Görseli')
                        ->content(function (?KurumsalSayfa $record): \Illuminate\Support\HtmlString {
                            if (! filled($record?->gorsel_lg)) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Henüz sayfa görseli yok.</p>');
                            }

                            return new \Illuminate\Support\HtmlString(
                                '<img src="' . e((string) $record->gorsel_lg) . '" style="max-width:100%;max-height:180px;border-radius:8px;object-fit:cover;" alt="Sayfa görseli" />'
                            );
                        }),

                    FileUpload::make('banner_masaustu_gecici')
                        ->label('Banner Masaüstü')
                        ->disk('local')
                        ->directory('tmp/kurumsal-sayfalar')
                        ->visibility('private')
                        ->image()
                        ->maxFiles(1)
                        ->dehydrated(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(65536)
                        ->helperText('Bu alan yeni banner yüklemek içindir. Kaydedilen banner aşağıda gösterilir.'),

                    Placeholder::make('mevcut_banner_masaustu')
                        ->label('Mevcut Banner Masaüstü')
                        ->content(function (?KurumsalSayfa $record): \Illuminate\Support\HtmlString {
                            if (! filled($record?->banner_masaustu)) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Henüz masaüstü banner yok.</p>');
                            }

                            return new \Illuminate\Support\HtmlString(
                                '<img src="' . e((string) $record->banner_masaustu) . '" style="max-width:100%;max-height:180px;border-radius:8px;object-fit:cover;" alt="Banner masaustu" />'
                            );
                        }),

                    FileUpload::make('banner_mobil_gecici')
                        ->label('Banner Mobil')
                        ->disk('local')
                        ->directory('tmp/kurumsal-sayfalar')
                        ->visibility('private')
                        ->image()
                        ->maxFiles(1)
                        ->dehydrated(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(65536)
                        ->helperText('Bu alan yeni mobil banner yüklemek içindir. Kaydedilen mobil banner aşağıda gösterilir.'),

                    Placeholder::make('mevcut_banner_mobil')
                        ->label('Mevcut Banner Mobil')
                        ->content(function (?KurumsalSayfa $record): \Illuminate\Support\HtmlString {
                            if (! filled($record?->banner_mobil)) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Henüz mobil banner yok.</p>');
                            }

                            return new \Illuminate\Support\HtmlString(
                                '<img src="' . e((string) $record->banner_mobil) . '" style="max-width:100%;max-height:180px;border-radius:8px;object-fit:cover;" alt="Banner mobil" />'
                            );
                        }),

                    FileUpload::make('og_gorsel_gecici')
                        ->label('OG Görsel')
                        ->disk('local')
                        ->directory('tmp/kurumsal-sayfalar')
                        ->visibility('private')
                        ->image()
                        ->maxFiles(1)
                        ->dehydrated(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(65536)
                        ->helperText('Bu alan yeni OG görsel yüklemek içindir. Kaydedilen OG görsel aşağıda gösterilir.'),

                    Placeholder::make('mevcut_og_gorsel')
                        ->label('Mevcut OG Görsel')
                        ->content(function (?KurumsalSayfa $record): \Illuminate\Support\HtmlString {
                            if (! filled($record?->og_gorsel)) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Henüz OG görsel yok.</p>');
                            }

                            $url = filter_var((string) $record->og_gorsel, FILTER_VALIDATE_URL)
                                ? (string) $record->og_gorsel
                                : Storage::disk('spaces')->url((string) $record->og_gorsel);

                            return new \Illuminate\Support\HtmlString(
                                '<img src="' . e($url) . '" style="max-width:100%;max-height:180px;border-radius:8px;object-fit:cover;" alt="OG gorsel" />'
                            );
                        }),

                    FileUpload::make('galeri_gorseller')
                        ->label('Galeri')
                        ->disk('local')
                        ->directory('tmp/kurumsal-sayfalar')
                        ->visibility('private')
                        ->image()
                        ->multiple()
                        ->appendFiles()
                        ->reorderable()
                        ->maxFiles(30)
                        ->dehydrated(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(65536)
                        ->columnSpanFull(),

                    Placeholder::make('galeri_onizleme')
                        ->label('Mevcut Galeri')
                        ->content(function (?KurumsalSayfa $record): \Illuminate\Support\HtmlString {
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
                    Textarea::make('ozet')
                        ->label('Özet/Summary')
                        ->rows(4)
                        ->maxLength(300)
                        ->helperText(fn (?string $state) => mb_strlen((string) ($state ?? ''), 'UTF-8') . '/300'),

                    Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->rows(3)
                        ->maxLength(160)
                        ->helperText(fn (?string $state) => mb_strlen((string) ($state ?? ''), 'UTF-8') . '/160'),

                    Select::make('robots')
                        ->label('Robots')
                        ->options(RobotsKurali::secenekler())
                        ->default(RobotsKurali::Index->value)
                        ->required(),

                    TextInput::make('canonical_url')
                        ->label('Canonical URL')
                        ->url()
                        ->maxLength(500),

                    Actions::make([
                        FormAction::make('ai_islemleri_baslat')
                            ->label('AI İşlemlerini Başlat')
                            ->icon('heroicon-o-sparkles')
                            ->color('primary')
                            ->action(function (callable $set, callable $get): void {
                                $icerik = strip_tags((string) $get('icerik'));

                                if (blank($get('meta_description')) && filled($icerik)) {
                                    $set('meta_description', Str::limit($icerik, 160, ''));
                                }

                                if (blank($get('ozet')) && filled($icerik)) {
                                    $set('ozet', Str::limit($icerik, 300, ''));
                                }

                                if (blank($get('slug')) && filled($get('ad'))) {
                                    $set('slug', Str::slug((string) $get('ad')));
                                }

                                $set('ai_islendi', true);

                                Notification::make()
                                    ->title('AI alanları güncellendi.')
                                    ->success()
                                    ->send();
                            }),
                    ]),
                ])
                ->columns(2),

            Section::make('İletişim Lokasyonları')
                ->schema([
                    Repeater::make('lokasyonlar')
                        ->label('Lokasyonlar')
                        ->relationship('lokasyonlar')
                        ->reorderableWithButtons()
                        ->defaultItems(0)
                        ->schema([
                            TextInput::make('lokasyon_adi')
                                ->label('Lokasyon Adı')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('adres')
                                ->label('Adres')
                                ->rows(2)
                                ->required(),
                            TextInput::make('eposta')
                                ->label('E-posta')
                                ->email()
                                ->maxLength(255),
                            TextInput::make('konum_lat')
                                ->label('Enlem')
                                ->numeric()
                                ->step(0.0000001),
                            TextInput::make('konum_lng')
                                ->label('Boylam')
                                ->numeric()
                                ->step(0.0000001),
                            TextInput::make('konum_place_id')
                                ->label('Place ID')
                                ->maxLength(255),
                            TextInput::make('sira')
                                ->label('Sıra')
                                ->integer()
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->addActionLabel('Lokasyon Ekle'),

                    Placeholder::make('iletisim_notu')
                        ->label('Telefon Notu')
                        ->content('Sabit telefonlar config/iletisim.php üzerinden yönetilir.')
                        ->columnSpanFull(),
                ])
                ->visible(fn (callable $get): bool => $get('sablon') === KurumsalSablonu::Iletisim->value),

            Hidden::make('ai_islendi')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')
                    ->label('Ad')
                    ->formatStateUsing(fn (string $state, KurumsalSayfa $record) => str_repeat('— ', max(0, $record->seviye() - 1)) . $state)
                    ->searchable(['ad', 'slug', 'ozet', 'icerik'])
                    ->sortable(),

                TextColumn::make('sablon')
                    ->label('Şablon')
                    ->formatStateUsing(fn (KurumsalSablonu|string|null $state) => $state instanceof KurumsalSablonu
                        ? $state->label()
                        : KurumsalSablonu::tryFrom((string) $state)?->label() ?? $state)
                    ->badge()
                    ->color(fn (KurumsalSablonu|string|null $state) => match ($state instanceof KurumsalSablonu ? $state : KurumsalSablonu::tryFrom((string) $state)) {
                        KurumsalSablonu::Standart => 'primary',
                        KurumsalSablonu::Iletisim => 'warning',
                        KurumsalSablonu::Kurum => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('ustSayfa.ad')
                    ->label('Üst Sayfa')
                    ->sortable(),

                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'yayinda' ? 'success' : 'gray')
                    ->formatStateUsing(fn (?string $state) => $state === 'yayinda' ? 'Yayında' : 'Taslak')
                    ->sortable(),

                TextColumn::make('sira')
                    ->label('Sıra')
                    ->sortable(),
            ])
            ->defaultSort('sira', 'asc')
            ->filters([
                SelectFilter::make('sablon')
                    ->label('Şablon')
                    ->options(KurumsalSablonu::secenekler()),
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options([
                        'taslak' => 'Taslak',
                        'yayinda' => 'Yayında',
                    ]),
                Filter::make('created_at')
                    ->form([
                        TextInput::make('baslangic')->label('Başlangıç Tarihi')->type('date'),
                        TextInput::make('bitis')->label('Bitiş Tarihi')->type('date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['baslangic'] ?? null, fn (Builder $q, $tarih) => $q->whereDate('created_at', '>=', $tarih))
                            ->when($data['bitis'] ?? null, fn (Builder $q, $tarih) => $q->whereDate('created_at', '<=', $tarih));
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['ustSayfa'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKurumsalSayfas::route('/'),
            'create' => Pages\CreateKurumsalSayfa::route('/create'),
            'edit' => Pages\EditKurumsalSayfa::route('/{record}/edit'),
        ];
    }
}
