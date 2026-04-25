@extends('layouts.app')

@php
    $sablon = $sayfa->sablon instanceof \BackedEnum
        ? $sayfa->sablon->value
        : ($sayfa->sablon ?: 'standart');

    $sayfaSlug = $sayfa->slug ?: 'hakkimizda';
    $sayfaBaslik = $sayfa->ad ?: 'Kurumsal';
    $sayfaOzet = $sayfa->ozet ?: \Illuminate\Support\Str::limit(strip_tags((string) $sayfa->icerik), 170, '...');
    $metaDescription = $sayfa->meta_description ?: $sayfaOzet ?: config('site.aciklama');
    $atolyeMetni = mb_strtolower(trim($sayfaBaslik . ' ' . $sayfaOzet . ' ' . strip_tags((string) $sayfa->icerik)), 'UTF-8');
    $stemAtolyesiMi = \Illuminate\Support\Str::contains($atolyeMetni, 'stem');

    if ($sablon === 'atolye' && $stemAtolyesiMi && ! \Illuminate\Support\Str::contains(mb_strtolower($metaDescription, 'UTF-8'), 'stem atölyesi nedir')) {
        $metaDescription = \Illuminate\Support\Str::limit(
            trim($metaDescription . ' STEM atölyesi nedir sorusuna yönelik amaç, içerik ve uygulama yaklaşımı bu sayfada açıklanır.'),
            160,
            ''
        );
    }

    $seoBaslik = $sayfaBaslik;
    if ($sablon === 'atolye' && $stemAtolyesiMi) {
        $seoBaslik .= ' | STEM Atölyesi Nedir?';
    }

    $canonicalUrl = $sayfa->canonical_url ?: ($sayfa->slug
        ? route('kurumsal.show', ['slug' => $sayfa->slug])
        : route('kurumsal.show'));

    $robotsMeta = match ($sayfa->robots instanceof \BackedEnum ? $sayfa->robots->value : (string) $sayfa->robots) {
        'noindex' => 'noindex, follow',
        'noindex_nofollow' => 'noindex, nofollow',
        default => 'index, follow',
    };

    $heroGorsel = $sayfa->bannerMasaustuUrl() ?: $sayfa->gorselLgUrl();
    $schemaImage = asset('images/kp-gorsel.jpg');
    $ogImage = $sayfa->ogGorselUrl() ?: $heroGorsel ?: $schemaImage;

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
        'kurum' => 'Organization',
        'atolye' => 'EducationalOrganization',
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
        'image' => $ogImage ?: $schemaImage,
    ];

    if (in_array($schemaTipi, ['Organization', 'EducationalOrganization'], true)) {
        $sayfaSchema['telephone'] = config('site.telefon');
        $sayfaSchema['email'] = config('site.eposta');
        $sayfaSchema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => '872. Sk. No:52',
            'postalCode' => '35250',
            'addressLocality' => config('site.schema_locality', 'Karabağlar'),
            'addressRegion' => config('site.schema_region', 'İzmir'),
            'addressCountry' => 'TR',
        ];
    }

    if ($schemaTipi === 'EducationalOrganization') {
        $sayfaSchema['areaServed'] = [
            '@type' => 'AdministrativeArea',
            'name' => 'İzmir, Türkiye',
        ];
        $sayfaSchema['knowsAbout'] = $stemAtolyesiMi
            ? ['STEM', 'Fen eğitimi', 'Teknoloji', 'Mühendislik', 'Matematik']
            : ['Atölye eğitimi', 'Uygulamalı öğrenme'];
    }

    $atolyeSssListesi = collect((array) ($sayfa->sss_listesi ?? []))
        ->map(function ($oge): ?array {
            if (! is_array($oge)) {
                return null;
            }

            $soru = trim((string) ($oge['soru'] ?? ''));
            $cevap = trim((string) ($oge['cevap'] ?? ''));

            if ($soru === '' || $cevap === '') {
                return null;
            }

            return [
                'soru' => $soru,
                'cevap' => $cevap,
            ];
        })
        ->filter()
        ->take(5)
        ->values();

    $stemFaqSchema = null;
    if ($sablon === 'atolye' && $atolyeSssListesi->isNotEmpty()) {
        $stemFaqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $atolyeSssListesi->map(fn (array $sss) => [
                '@type' => 'Question',
                'name' => $sss['soru'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $sss['cevap'],
                ],
            ])->all(),
        ];
    } elseif ($sablon === 'atolye' && $stemAtolyesiMi) {
        $stemFaqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'STEM atölyesi nedir?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => trim($sayfaOzet ?: ($sayfaBaslik . ' öğrencilerin fen, teknoloji, mühendislik ve matematik alanlarında uygulamalı öğrenmesini destekleyen bir atölye modelidir.')),
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'STEM atölyesinde hangi beceriler gelişir?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Problem çözme, analitik düşünme, takım çalışması, tasarım odaklı düşünme ve üretim becerileri gelişir.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'STEM atölyesi kimler için uygundur?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Uygulamalı öğrenmeye ilgi duyan öğrenciler, gençler ve STEM becerilerini güçlendirmek isteyen katılımcılar için uygundur.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'STEM atölyesinde hangi etkinlikler yapılır?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Atölyede deney, proje tabanlı uygulama, problem çözme görevleri, modelleme ve takım çalışması odaklı üretim etkinlikleri yapılır.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'STEM atölyesi öğrencinin eğitimine nasıl katkı sağlar?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'STEM atölyesi öğrencinin analitik düşünme, araştırma yapma, yaratıcı çözüm üretme ve disiplinler arası öğrenme becerilerini güçlendirir.',
                    ],
                ],
            ],
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

@section('title', $seoBaslik)
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
@if(is_array($stemFaqSchema))
<script type="application/ld+json">
{!! json_encode($stemFaqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
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

                @if($sablon === 'atolye' && $stemAtolyesiMi)
                    <section class="kurumsal-section-card">
                        <h2 class="kurumsal-section-title">STEM Atölyesi Nedir?</h2>
                        <div class="kurumsal-prose mt-4">
                            <p>{{ $sayfaOzet ?: ($sayfaBaslik . ' öğrencilerin fen, teknoloji, mühendislik ve matematik alanlarında uygulamalı öğrenmesini destekleyen bir atölye modelidir.') }}</p>
                            <p>Bu atölye modeli, teorik bilgiyi uygulama ile birleştirerek problem çözme, üretim ve takım çalışması becerilerini güçlendirmeyi hedefler.</p>
                        </div>
                    </section>
                @endif

                @if($sablon === 'atolye' && $atolyeSssListesi->isNotEmpty())
                    <section class="kurumsal-section-card">
                        <h2 class="kurumsal-section-title">Sık Sorulan Sorular</h2>
                        <div class="mt-4 space-y-4">
                            @foreach($atolyeSssListesi as $sss)
                                <div class="rounded-xl border border-primary/10 bg-white p-4">
                                    <h3 class="text-[16px] font-semibold text-[#162e4b]">{{ $sss['soru'] }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-[#62868d]">{{ $sss['cevap'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="order-2 space-y-6 lg:order-2 lg:col-span-1 lg:w-80 lg:sticky lg:top-6">
                @include('components.sidebar')
            </aside>
        </div>
    </section>
@endsection
