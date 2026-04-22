@php
  // SEO: Sabit kurumsal ad ve kok URL tanimi.
  $siteRoot = rtrim((string) config('app.url'), '/');
  $kurulusAdi = 'Kestanepazarı Öğrenci Yetiştirme Derneği';
  $pageTitle = trim($__env->yieldContent('title', config('site.ad')));
  $metaDescription = trim($__env->yieldContent('meta_description', config('site.aciklama')));
  $canonicalUrl = trim($__env->yieldContent('canonical', url()->current()));
  $ogType = trim($__env->yieldContent('og_type', 'website'));
  $ogImage = trim($__env->yieldContent('og_image', asset('img/og-default.jpg')));
  $ogUpdatedTime = trim($__env->yieldContent('og_updated_time'));
  $robotsBase = trim($__env->yieldContent('robots', 'index, follow'));
  $robotsMeta = $robotsBase . ', max-snippet:-1, max-image-preview:large, max-video-preview:-1';

  $geoRegion = config('site.geo_region', 'TR-35');
  $geoPlacename = config('site.geo_placename', 'Karabağlar, İzmir');
  $geoPosition = config('site.geo_position');
  $geoIcbm = config('site.geo_icbm');
  $schemaLocality = config('site.schema_locality', 'Karabağlar');
  $schemaRegion = config('site.schema_region', 'İzmir');
  $geoLatitude = null;
  $geoLongitude = null;

  if (filled($geoPosition)) {
    $normalizedGeo = str_replace(',', ';', (string) $geoPosition);
    $parts = array_values(array_filter(array_map('trim', explode(';', $normalizedGeo)), fn ($value) => $value !== ''));

    if (count($parts) >= 2) {
      $geoLatitude = $parts[0];
      $geoLongitude = $parts[1];
    }
  }

  $websiteSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    // SEO: WebSite kimligini sabitlemek icin @id eklendi.
    '@id' => $siteRoot . '/#website',
    'name' => $kurulusAdi,
    'url' => $siteRoot,
    'potentialAction' => [
      '@type' => 'SearchAction',
      'target' => route('arama.index') . '?q={search_term_string}',
      'query-input' => 'required name=search_term_string',
    ],
  ];

  $organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    // SEO: Organization kimligini sabitlemek icin @id eklendi.
    '@id' => $siteRoot . '/#organization',
    'name' => $kurulusAdi,
    'url' => $siteRoot,
    'logo' => 'https://cdn.kestanepazari.org.tr/logo.png',
    'telephone' => config('site.telefon'),
    'email' => config('site.eposta'),
    'address' => [
      '@type' => 'PostalAddress',
      'addressLocality' => $schemaLocality,
      'addressRegion' => $schemaRegion,
      'addressCountry' => 'TR',
    ],
    'sameAs' => array_values(array_filter([
      config('site.facebook'),
      config('site.instagram'),
      config('site.x'),
    ])),
  ];

  if (filled($geoLatitude) && filled($geoLongitude)) {
    $organizationSchema['geo'] = [
      '@type' => 'GeoCoordinates',
      'latitude' => $geoLatitude,
      'longitude' => $geoLongitude,
    ];
  }

  $labelMap = [
    'kurumsal' => 'Kurumsal',
    'haberler' => 'Haberler',
    'etkinlikler' => 'Etkinlikler',
    'bagis' => 'Bağış',
    'iletisim' => 'İletişim',
    'mezunlar' => 'Mezunlar',
    'kayit' => 'E-Kayıt',
    'arama' => 'Arama',
  ];

  // SEO: Detay sayfalarinda breadcrumb son etiketini kullanici dostu adla guncelle.
  $sonSegmentOzelAdi = null;
  if (request()->routeIs('haberler.show') && isset($haber)) {
    $sonSegmentOzelAdi = $haber->baslik ?? null;
  } elseif (request()->routeIs('etkinlikler.show') && isset($etkinlik)) {
    $sonSegmentOzelAdi = $etkinlik->baslik ?? null;
  } elseif (request()->routeIs('kurumsal.show') && isset($sayfa)) {
    $sonSegmentOzelAdi = $sayfa->ad ?? ($sayfa->baslik ?? null);
  } elseif (request()->routeIs('bagis.show') && isset($bagisTuru)) {
    $sonSegmentOzelAdi = $bagisTuru->ad ?? null;
  }

  $breadcrumbItems = [[
    '@type' => 'ListItem',
    'position' => 1,
    'name' => 'Ana Sayfa',
    'item' => url('/'),
  ]];

  $segmentler = request()->segments();
  $sonSegmentIndeksi = count($segmentler) - 1;
  $accumulatedPath = '';
  foreach ($segmentler as $index => $segment) {
    $accumulatedPath .= '/' . $segment;
    $slug = strtolower((string) $segment);
    $label = $labelMap[$slug] ?? \Illuminate\Support\Str::title(str_replace('-', ' ', (string) $segment));

    if ($index === $sonSegmentIndeksi && filled($sonSegmentOzelAdi)) {
      $label = $sonSegmentOzelAdi;
    }

    $breadcrumbItems[] = [
      '@type' => 'ListItem',
      'position' => $index + 2,
      'name' => $label,
      'item' => url($accumulatedPath),
    ];
  }

  $breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
  ];

  // SEO: Layout bazli WebPage schema kaldirildi, sadece ilgili sayfa tiplerinde schema uretilir.
  $icerikSchema = null;

  // SEO: Haber detayinda NewsArticle sadece sayfa bazli bloktan gelsin.
  if (request()->routeIs('etkinlikler.show')) {
    $icerikSchema = [
      '@context' => 'https://schema.org',
      '@type' => 'Event',
      'name' => $pageTitle,
      'description' => $metaDescription,
      'eventStatus' => 'https://schema.org/EventScheduled',
      'url' => $canonicalUrl,
      'image' => [$ogImage],
      'location' => [
        '@type' => 'Place',
        'name' => $geoPlacename,
      ],
      'organizer' => [
        '@type' => 'Organization',
        'name' => $kurulusAdi,
      ],
    ];
  } elseif (request()->routeIs('iletisim.*')) {
    $icerikSchema = [
      '@context' => 'https://schema.org',
      '@type' => 'ContactPage',
      'name' => 'İletişim',
      'description' => $metaDescription,
      'url' => $canonicalUrl,
      'mainEntity' => [
        '@type' => 'Organization',
        'name' => $kurulusAdi,
        'contactPoint' => [[
          '@type' => 'ContactPoint',
          'telephone' => config('site.telefon'),
          'email' => config('site.eposta'),
          'contactType' => 'customer support',
          'areaServed' => 'TR',
          'availableLanguage' => ['tr'],
        ]],
      ],
    ];
  }
@endphp

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', config('site.ad')) — Kestanepazarı</title>
<meta name="description" content="{!! html_entity_decode($metaDescription, ENT_QUOTES, 'UTF-8') !!}">
<meta name="robots" content="{{ $robotsMeta }}">
<meta name="referrer" content="strict-origin-when-cross-origin">
<meta name="theme-color" content="#162E4B">
<meta name="color-scheme" content="light">
<link rel="canonical" href="{{ $canonicalUrl }}">
<link rel="alternate" hreflang="tr-TR" href="{{ $canonicalUrl }}">
<link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">

<!-- Open Graph -->
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="Kestanepazarı">
<meta property="og:title" content="{{ $pageTitle }} — Kestanepazarı">
<meta property="og:description" content="{!! html_entity_decode($metaDescription, ENT_QUOTES, 'UTF-8') !!}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="tr_TR">
<meta property="og:locale:alternate" content="tr_TR">
@if(filled($ogUpdatedTime))
<meta property="og:updated_time" content="{{ $ogUpdatedTime }}">
@endif
@if(filled($geoLatitude) && filled($geoLongitude))
<meta property="og:latitude" content="{{ $geoLatitude }}">
<meta property="og:longitude" content="{{ $geoLongitude }}">
@endif

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="Kestanepazarı">
<meta name="twitter:creator" content="Kestanepazarı">
<meta name="twitter:title" content="{{ $pageTitle }} — Kestanepazarı">
<meta name="twitter:description" content="{!! html_entity_decode($metaDescription, ENT_QUOTES, 'UTF-8') !!}">
<meta name="twitter:image" content="{{ $ogImage }}">

<!-- GEO -->
<meta name="geo.region" content="{{ $geoRegion }}">
<meta name="geo.placename" content="{{ $geoPlacename }}">
@if(filled($geoPosition))
<meta name="geo.position" content="{{ $geoPosition }}">
@endif
@if(filled($geoIcbm))
<meta name="ICBM" content="{{ $geoIcbm }}">
@endif

<!-- Font preload -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,700;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Organization Schema (tüm sayfalarda) -->
<script type="application/ld+json">
{!! json_encode($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<script type="application/ld+json">
{!! json_encode($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

@if(!request()->routeIs('home'))
{{-- SEO: Ana sayfada tek ogeli breadcrumb cikarmamak icin kosul eklendi. --}}
<script type="application/ld+json">
{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

@if(is_array($icerikSchema))
{{-- SEO: Sadece Event/ContactPage gibi durumlarda layout schema cikar. --}}
<script type="application/ld+json">
{!! json_encode($icerikSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

<!-- Sayfa bazlı Schema -->
@yield('schema')

@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="site-layout font-jakarta bg-bg-soft">
  @include('components.header')
  <main>
    @yield('content')
  </main>
  @include('components.footer')
  @stack('scripts')
</body>
</html>
