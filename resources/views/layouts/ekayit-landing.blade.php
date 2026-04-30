<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'E-Kayıt') — Kestanepazarı</title>
  <meta name="description" content="@yield('meta_description', 'Kestanepazarı Derneği öğrenci e-kayıt başvurusu.')">
  <meta name="robots" content="@yield('robots', 'index, follow')">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,700;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  @yield('schema')
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="font-jakarta antialiased" style="background: #f8f9fb; min-height: 100vh;">

  {{-- Minimal Header: sadece logo --}}
  <header style="background: #fff; border-bottom: 1px solid #eef0f3; position: sticky; top: 0; z-index: 50;">
    <div style="max-width: 960px; margin: 0 auto; padding: 0 24px; height: 64px; display: flex; align-items: center; justify-content: center;">
      <a href="{{ route('ekayit.landing') }}" aria-label="Kestanepazarı">
        <img src="{{ asset('images/logo.svg') }}" alt="Kestanepazarı" style="height: 46px; width: auto; display: block;">
      </a>
    </div>
  </header>

  {{-- İçerik --}}
  <main>
    @yield('content')
  </main>

  @stack('scripts')
</body>
</html>
