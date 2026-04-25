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

    $ustBolum = match ($sablon) {
        'kurum' => [
            'label' => 'Kurumlar',
            'href' => route('kurumsal.show', ['slug' => 'kurumlar']),
        ],
        'atolye' => [
            'label' => 'Atölyeler',
            'href' => route('kurumsal.show', ['slug' => 'atolyeler']),
        ],
        default => [
            'label' => 'Kurumsal',
            'href' => route('kurumsal.show'),
        ],
    };

    $schemaTipi = match ($sablon) {
        'iletisim' => 'ContactPage',
        'kurum', 'atolye' => 'Organization',
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
                'name' => $ustBolum['label'],
                'item' => $ustBolum['href'],
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

    $standartMenuSayfalari = $menuSayfalari
        ->filter(function ($menuSayfa) {
            $sablonDegeri = $menuSayfa->sablon instanceof \BackedEnum
                ? $menuSayfa->sablon->value
                : (string) ($menuSayfa->sablon ?? '');

            return $sablonDegeri === 'standart';
        })
        ->values();

    $yatayMenu = $standartMenuSayfalari->map(fn ($menuSayfa) => [
        'href' => route('kurumsal.show', ['slug' => $menuSayfa->slug]),
        'label' => $menuSayfa->ad,
        'aktif' => $menuSayfa->slug === $sayfaSlug,
    ])->values();

    $yatayMenu->push([
        'href' => route('kurumsal.show', ['slug' => 'kurumlar']),
        'label' => 'Kurumlar',
        'aktif' => $sablon === 'kurum' || $sayfaSlug === 'kurumlar',
    ]);

    $yatayMenu->push([
        'href' => route('kurumsal.show', ['slug' => 'atolyeler']),
        'label' => 'Atölyeler',
        'aktif' => $sablon === 'atolye' || $sayfaSlug === 'atolyeler',
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
                <a href="{{ $ustBolum['href'] }}" class="transition hover:text-[#ebdfb5]">{{ $ustBolum['label'] }}</a>
                @if($sayfaSlug !== 'hakkimizda')
                    <span>/</span>
                    <span class="text-[#ebdfb5]">{{ $sayfaBaslik }}</span>
                @endif
            </div>

            <div>
                <h1 class="font-baskerville text-3xl font-bold leading-tight text-[#ebdfb5] md:text-5xl">
                        {{ $sayfaBaslik }}
                </h1>
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
        <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
            <div class="order-1 space-y-6 lg:order-1 lg:col-span-2">
                @include('pages.kurumsal.partials.' . match ($sablon) {
                    'iletisim' => 'iletisim',
                    'kurum' => 'kurum',
                    'atolye' => 'kurum',
                    default => 'standart',
                })
            </div>

            <aside class="order-2 space-y-6 lg:order-2 lg:col-span-1 lg:w-80 lg:sticky lg:top-6">
                @include('components.sidebar')
            </aside>
        </div>
    </section>
@endsection
