@extends('layouts.app')

@php
    $sablon = $sayfa->sablon instanceof \BackedEnum
        ? $sayfa->sablon->value
        : ($sayfa->sablon ?: 'standart');

    $sayfaSlug = $sayfa->slug ?: 'hakkimizda';
    $sayfaBaslik = $sayfa->ad ?: 'Kurumsal';
    $sayfaOzet = $sayfa->ozet ?: \Illuminate\Support\Str::limit(strip_tags((string) $sayfa->icerik), 170, '...');
    $metaDescription = $sayfa->meta_description ?: $sayfaOzet ?: config('site.aciklama');
    $canonicalUrl = $sayfa->canonical_url ?: ($sayfa->slug
        ? route('kurumsal.show', ['slug' => $sayfa->slug])
        : route('kurumsal.show'));

    $robotsMeta = match ($sayfa->robots instanceof \BackedEnum ? $sayfa->robots->value : (string) $sayfa->robots) {
        'noindex' => 'noindex, follow',
        'noindex_nofollow' => 'noindex, nofollow',
        default => 'index, follow',
    };

    $heroGorsel = $sayfa->bannerMasaustuUrl() ?: $sayfa->gorselLgUrl();
    $ogImage = $sayfa->ogGorselUrl() ?: $heroGorsel ?: 'https://cdn.kestanepazari.org.tr/logo.png';

    $schemaTipi = match ($sablon) {
        'iletisim' => 'ContactPage',
        'kurum' => 'Organization',
        default => 'WebPage',
    };

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect([
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Ana Sayfa',
                'item' => url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Kurumsal',
                'item' => route('kurumsal.show'),
            ],
            ...collect($breadcrumbSayfalari ?? [])->values()->map(fn ($breadcrumb, $index) => [
                '@type' => 'ListItem',
                'position' => $index + 3,
                'name' => $breadcrumb->ad,
                'item' => route('kurumsal.show', ['slug' => $breadcrumb->slug]),
            ])->all(),
            [
                '@type' => 'ListItem',
                'position' => collect($breadcrumbSayfalari ?? [])->count() + 3,
                'name' => $sayfaBaslik,
                'item' => $canonicalUrl,
            ],
        ])->values()->all(),
    ];

    $sayfaSchema = [
        '@context' => 'https://schema.org',
        '@type' => $schemaTipi,
        'name' => $sayfaBaslik,
        'description' => $metaDescription,
        'url' => $canonicalUrl,
        'image' => $ogImage,
    ];

    if ($schemaTipi === 'Organization') {
        $sayfaSchema['telephone'] = config('site.telefon');
        $sayfaSchema['email'] = config('site.eposta');
        $sayfaSchema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => config('site.adres'),
            'addressCountry' => 'TR',
        ];
    }

    $yatayMenu = $sayfaSlug === 'hakkimizda'
        ? collect([
            ['href' => '#tarihce', 'label' => 'Tarihçe', 'aktif' => true],
            ['href' => '#amac', 'label' => 'Amaç', 'aktif' => false],
            ['href' => '#hakkimizda', 'label' => 'Hakkımızda', 'aktif' => false],
            ['href' => '#yonetim', 'label' => 'Yönetim Kurulu', 'aktif' => false],
        ])
        : $menuSayfalari->map(fn ($menuSayfa) => [
            'href' => route('kurumsal.show', ['slug' => $menuSayfa->slug]),
            'label' => $menuSayfa->ad,
            'aktif' => $menuSayfa->slug === $sayfaSlug,
        ]);
@endphp

@section('title', $sayfaBaslik)
@section('meta_description', $metaDescription)
@section('robots', $robotsMeta)
@section('canonical', $canonicalUrl)
@section('og_image', $ogImage)

@section('schema')
<script type="application/ld+json">
{!! json_encode($sayfaSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
<script type="application/ld+json">
{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endsection

@section('content')
    <section class="kurumsal-hero">
        @if($heroGorsel)
            <div class="kurumsal-hero-image" style="background-image: url('{{ $heroGorsel }}');"></div>
        @endif

        <div class="kurumsal-hero-overlay"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 lg:px-6 lg:py-20">
            <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-[#ebdfb5]/70">
                <a href="{{ route('home') }}" class="transition hover:text-[#ebdfb5]">Ana Sayfa</a>
                <span>/</span>
                <a href="{{ route('kurumsal.show') }}" class="transition hover:text-[#ebdfb5]">Kurumsal</a>
                @if($sayfaSlug !== 'hakkimizda')
                    <span>/</span>
                    <span class="text-[#ebdfb5]">{{ $sayfaBaslik }}</span>
                @endif
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_260px] lg:items-end">
                <div>
                    <span class="kurumsal-chip">Köklü miras • Eğitim odaklı yapı</span>
                    <h1 class="mt-4 font-baskerville text-3xl font-bold leading-tight text-[#ebdfb5] md:text-5xl">
                        {{ $sayfaBaslik }}
                    </h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-[#ebdfb5]/75 md:text-base">
                        {{ $sayfaOzet ?: 'Kestanepazarı’nın kurumsal yaklaşımını, değerlerini ve güncel içeriklerini bu sayfada inceleyebilirsiniz.' }}
                    </p>
                </div>

                <div class="kurumsal-hero-badge-box">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#b27829]">Öne çıkan</p>
                    <p class="mt-2 font-baskerville text-2xl font-bold text-white">1966</p>
                    <p class="mt-2 text-sm leading-6 text-white/70">
                        Seferihisar’da öğrencilerin eğitim yolculuğunu destekleyen güven temelli dayanışma geleneği.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="kurumsal-top-nav">
        <div class="mx-auto max-w-7xl px-4 lg:px-6">
            <div class="kurumsal-top-nav-inner">
                @foreach($yatayMenu as $item)
                    @php $hashLink = \Illuminate\Support\Str::startsWith($item['href'], '#'); @endphp
                    <a
                        href="{{ $item['href'] }}"
                        @if($hashLink) data-kurumsal-nav @endif
                        class="page-nav-link {{ $item['aktif'] ? 'active' : '' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <section class="mx-auto max-w-7xl px-4 py-10 lg:px-6 lg:py-12">
        <div class="grid gap-6 xl:grid-cols-[240px_minmax(0,1fr)_320px]">
            <aside class="order-2 space-y-4 xl:order-1">
                <div class="kurumsal-menu-card">
                    <p class="kurumsal-menu-title">Kurumsal Menü</p>

                    <div class="mt-3 space-y-2">
                        @foreach($menuSayfalari as $menuSayfa)
                            <a
                                href="{{ route('kurumsal.show', ['slug' => $menuSayfa->slug]) }}"
                                class="kurumsal-menu-link {{ $menuSayfa->slug === $sayfaSlug ? 'is-active' : '' }}"
                            >
                                <span>{{ $menuSayfa->ad }}</span>
                                <span aria-hidden="true">›</span>
                            </a>
                        @endforeach
                    </div>

                    @if($ustSayfa)
                        <div class="mt-5 rounded-2xl border border-[#162e4b]/10 bg-[#f7f5f0] p-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#62868d]">Bağlı olduğu sayfa</p>
                            <a href="{{ route('kurumsal.show', ['slug' => $ustSayfa->slug]) }}" class="mt-2 inline-flex text-sm font-semibold text-[#162e4b] transition hover:text-[#b27829]">
                                {{ $ustSayfa->ad }}
                            </a>
                        </div>
                    @endif

                    @if($altSayfalar->isNotEmpty())
                        <div class="mt-5 rounded-2xl border border-[#162e4b]/10 bg-[#f7f5f0] p-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#62868d]">Alt sayfalar</p>
                            <div class="mt-2 space-y-2">
                                @foreach($altSayfalar as $altSayfa)
                                    <a href="{{ route('kurumsal.show', ['slug' => $altSayfa->slug]) }}" class="block text-sm text-[#162e4b]/75 transition hover:text-[#b27829]">
                                        {{ $altSayfa->ad }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="kurumsal-cta-card">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#ebdfb5]/60">Destek</p>
                    <h3 class="mt-2 font-baskerville text-xl font-bold text-[#ebdfb5]">Bir öğrencinin yolculuğuna katkı sunun</h3>
                    <p class="mt-3 text-sm leading-6 text-[#ebdfb5]/70">
                        Burs, barınma ve gelişim programlarına destek olmak için bağış sayfamızı ziyaret edebilirsiniz.
                    </p>
                    <a href="{{ route('bagis.index') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#e95925] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#c94620]">
                        Bağış Yap
                    </a>
                </div>
            </aside>

            <div class="order-1 space-y-6 xl:order-2">
                @include('pages.kurumsal.partials.' . match ($sablon) {
                    'iletisim' => 'iletisim',
                    'kurum' => 'kurum',
                    default => 'standart',
                })

                @if($sayfa->gorseller->isNotEmpty())
                    <section class="kurumsal-section-card">
                        <div class="mb-5 flex items-center justify-between gap-4">
                            <div>
                                <p class="kurumsal-eyebrow">Galeri</p>
                                <h2 class="kurumsal-section-title">Sayfadan kareler</h2>
                            </div>
                            <span class="rounded-full bg-[#f7f5f0] px-3 py-1 text-xs font-semibold text-[#62868d]">
                                {{ $sayfa->gorseller->count() }} görsel
                            </span>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($sayfa->gorseller as $gorsel)
                                <figure class="kurumsal-gallery-item">
                                    <img
                                        src="{{ $gorsel->lgUrl() }}"
                                        alt="{{ $gorsel->alt_text ?: $sayfaBaslik }}"
                                        class="h-52 w-full object-cover"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                    @if($gorsel->alt_text)
                                        <figcaption>{{ $gorsel->alt_text }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="order-3 xl:order-3">
                @include('components.sidebar')
            </aside>
        </div>
    </section>
@endsection
