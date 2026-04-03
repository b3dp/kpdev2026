<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', config('site.ad')) — Kestanepazarı</title>
<meta name="description" content="@yield('meta_description', config('site.aciklama'))">
<meta name="robots" content="@yield('robots', 'index, follow')">
<link rel="canonical" href="@yield('canonical', url()->current())">

<!-- Open Graph -->
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:title" content="@yield('title') — Kestanepazarı">
<meta property="og:description" content="@yield('meta_description', config('site.aciklama'))">
<meta property="og:image" content="@yield('og_image', asset('img/og-default.jpg'))">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="tr_TR">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@yield('title') — Kestanepazarı">
<meta name="twitter:description" content="@yield('meta_description')">
<meta name="twitter:image" content="@yield('og_image', asset('img/og-default.jpg'))">

<!-- GEO -->
<meta name="geo.region" content="TR-35">
<meta name="geo.placename" content="Seferihisar, İzmir">
<meta name="geo.position" content="38.1956;26.8344">
<meta name="ICBM" content="38.1956, 26.8344">

<!-- Font preload -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,700;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Organization Schema (tüm sayfalarda) -->
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "Organization",
  "name": "{{ config('site.ad') }} Öğrenci Yetiştirme Derneği",
  "url": "{{ url('/') }}",
  "logo": "https://cdn.kestanepazari.org.tr/logo.png",
  "telephone": "{{ config('site.telefon') }}",
  "email": "{{ config('site.eposta') }}",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Seferihisar",
    "addressRegion": "İzmir",
    "addressCountry": "TR"
  },
  "sameAs": ["{{ config('site.facebook') }}", "{{ config('site.instagram') }}", "{{ config('site.x') }}"]
}
</script>

<!-- Sayfa bazlı Schema -->
@yield('schema')

@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="font-jakarta bg-bg-soft">
  @include('components.header')
  <main>
    @yield('content')
  </main>
  @include('components.footer')
  @stack('scripts')
</body>
</html>
