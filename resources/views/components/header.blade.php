@php
    $telefon = config('site.telefon');
  $haber_kategorileri = \App\Models\HaberKategorisi::query()
    ->where('aktif', true)
    ->orderBy('sira')
    ->orderBy('ad')
    ->get(['ad', 'slug']);
    $sepet = session('sepet', []);
    $sepet_adet = is_array($sepet) ? count($sepet) : 0;
    $sepet_toplam = collect($sepet)->sum(fn (array $satir) => (float) ($satir['toplam'] ?? 0));
    $header_arama = trim((string) request('q', ''));
    $populer_aramalar = app(\App\Services\AramaService::class)->getirPopulerAramalar(6);
    $uye_oturumu_acik = \Illuminate\Support\Facades\Auth::guard('uye')->check();
    $aktif_uye = $uye_oturumu_acik ? \Illuminate\Support\Facades\Auth::guard('uye')->user() : null;
    $yonetici_oturumu_acik = auth()->guard('web')->check();
    $kurumsal_sayfalar = \App\Models\KurumsalSayfa::query()
      ->where('durum', 'yayinda')
      ->whereNull('ust_sayfa_id')
      ->orderBy('sira')
      ->orderBy('ad')
      ->get(['ad', 'slug', 'sablon']);
    $kurumsal_standart_sayfalar = $kurumsal_sayfalar
      ->where('sablon', \App\Enums\KurumsalSablonu::Standart->value)
      ->values();
    $egitim_ogretim_sayfalari = $kurumsal_sayfalar
      ->where('sablon', \App\Enums\KurumsalSablonu::Kurum->value)
      ->values();
    $atolye_sayfalari = $kurumsal_sayfalar
      ->where('sablon', \App\Enums\KurumsalSablonu::Atolye->value)
      ->values();
    $aktif_kurumsal_slug = (string) request()->route('slug', '');
    $egitim_menu_aktif = request()->routeIs('kurumsal*')
      && (
        $aktif_kurumsal_slug === 'kurumlar'
        || $egitim_ogretim_sayfalari->contains('slug', $aktif_kurumsal_slug)
      );
    $atolye_menu_aktif = request()->routeIs('kurumsal*')
      && (
        $aktif_kurumsal_slug === 'atolyeler'
        || $atolye_sayfalari->contains('slug', $aktif_kurumsal_slug)
      );
    $kurumsal_menu_aktif = request()->routeIs('kurumsal*') && ! $egitim_menu_aktif && ! $atolye_menu_aktif;
    $uye_bas_harfleri = $aktif_uye
        ? collect(preg_split('/\s+/', trim((string) $aktif_uye->ad_soyad)))
            ->filter()
            ->take(2)
            ->map(fn ($parca) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($parca, 0, 1)))
            ->implode('')
        : 'Ü';
@endphp

<header id="main-header" class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-100">
  <div id="top-bar" class="bg-primary text-white text-[12px] font-jakarta">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-[38px] flex items-center justify-between">
      <div class="flex items-center gap-4">
        <a href="tel:{{ preg_replace('/\D/', '', $telefon = config('site.telefon')) }}" class="flex items-center gap-1.5 opacity-75 hover:opacity-100 transition-opacity">
          <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          {{ config('site.telefon') }}
        </a>
        <a href="mailto:{{ config('site.eposta') }}" class="hidden sm:flex items-center gap-1.5 opacity-75 hover:opacity-100 transition-opacity">
          <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
          {{ config('site.eposta') }}
        </a>
        <a href="{{ route('iletisim.index') }}" class="hidden md:flex items-center gap-1 opacity-75 hover:opacity-100 transition-opacity font-medium">
          İletişim
        </a>
      </div>

      <div class="flex items-center gap-3">
        <span class="opacity-40 hidden sm:block text-[11px] tracking-wide uppercase">Takip Et</span>

        @if(config('site.facebook'))
          <a href="{{ config('site.facebook') }}" aria-label="Facebook" class="opacity-60 hover:opacity-100 hover:text-accent-bright transition-all">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>
            </svg>
          </a>
        @endif

        @if(config('site.instagram'))
          <a href="{{ config('site.instagram') }}" aria-label="Instagram" class="opacity-60 hover:opacity-100 hover:text-accent-bright transition-all">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
              <circle cx="12" cy="12" r="4"/>
              <circle cx="17.5" cy="6.5" r="0.5" fill="currentColor" stroke="none"/>
            </svg>
          </a>
        @endif

        @if(config('site.x'))
          <a href="{{ config('site.x') }}" aria-label="X / Twitter" class="opacity-60 hover:opacity-100 hover:text-accent-bright transition-all">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
          </a>
        @endif
      </div>
    </div>
  </div>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-[72px] lg:h-[82px]">
      <a href="{{ route('home') }}" class="flex items-center flex-shrink-0">
        <img src="{{ asset('images/logo.svg') }}" alt="Kestanepazarı" class="h-[60px] w-auto lg:h-[66px]">
      </a>

      <nav class="hidden lg:flex items-center gap-0.5">
        <div class="has-dropdown">
          <a href="{{ route('kurumsal.show') }}" class="nav-link {{ $kurumsal_menu_aktif ? 'active text-accent font-semibold' : 'text-primary font-medium' }} flex items-center gap-1 font-jakarta text-[13.5px] px-3 py-2 rounded cursor-pointer">
            Kurumsal
            <svg class="chev w-3.5 h-3.5 {{ $kurumsal_menu_aktif ? 'opacity-60' : 'opacity-40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </a>
          <div class="dropdown-panel bg-white rounded-xl shadow-xl shadow-primary/10 border border-gray-100 p-2">
            @forelse($kurumsal_standart_sayfalar as $kurumsal_sayfa)
              <a href="{{ route('kurumsal.show', $kurumsal_sayfa->slug) }}" class="dropdown-item"><span class="dot"></span>{{ $kurumsal_sayfa->ad }}</a>
            @empty
              <a href="{{ route('kurumsal.show', 'hakkimizda') }}" class="dropdown-item"><span class="dot"></span>Hakkımızda</a>
            @endforelse
          </div>
        </div>

        <div class="has-dropdown">
          <a href="{{ route('kurumsal.show', 'kurumlar') }}" class="nav-link {{ $egitim_menu_aktif ? 'active text-accent font-semibold' : 'text-primary font-medium' }} flex items-center gap-1 font-jakarta text-[13.5px] px-3 py-2 rounded cursor-pointer">
            Eğitim/Öğretim
            <svg class="chev w-3.5 h-3.5 {{ $egitim_menu_aktif ? 'opacity-60' : 'opacity-40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </a>
          <div class="dropdown-panel bg-white rounded-xl shadow-xl shadow-primary/10 border border-gray-100 p-2">
            @forelse($egitim_ogretim_sayfalari as $egitim_ogretim_sayfasi)
              <a href="{{ route('kurumsal.show', $egitim_ogretim_sayfasi->slug) }}" class="dropdown-item"><span class="dot"></span>{{ $egitim_ogretim_sayfasi->ad }}</a>
            @empty
              <a href="{{ route('kurumsal.show', 'kurumlar') }}" class="dropdown-item"><span class="dot"></span>Kurumlar</a>
            @endforelse
          </div>
        </div>

        <div class="has-dropdown">
          <a href="{{ route('kurumsal.show', 'atolyeler') }}" class="nav-link {{ $atolye_menu_aktif ? 'active text-accent font-semibold' : 'text-primary font-medium' }} flex items-center gap-1 font-jakarta text-[13.5px] px-3 py-2 rounded cursor-pointer">
            Atölyeler
            <svg class="chev w-3.5 h-3.5 {{ $atolye_menu_aktif ? 'opacity-60' : 'opacity-40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </a>
          <div class="dropdown-panel bg-white rounded-xl shadow-xl shadow-primary/10 border border-gray-100 p-2">
            @forelse($atolye_sayfalari as $atolye_sayfasi)
              <a href="{{ route('kurumsal.show', $atolye_sayfasi->slug) }}" class="dropdown-item"><span class="dot"></span>{{ $atolye_sayfasi->ad }}</a>
            @empty
              <a href="{{ route('kurumsal.show', 'atolyeler') }}" class="dropdown-item"><span class="dot"></span>Atölyeler</a>
            @endforelse
          </div>
        </div>

        <div class="has-dropdown">
          <button class="nav-link {{ request()->routeIs('haberler*') ? 'active text-accent font-semibold' : 'text-primary font-medium' }} flex items-center gap-1 font-jakarta text-[13.5px] px-3 py-2 rounded cursor-pointer">
            Haberler
            <svg class="chev w-3.5 h-3.5 {{ request()->routeIs('haberler*') ? 'opacity-60' : 'opacity-40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div class="dropdown-panel bg-white rounded-xl shadow-xl shadow-primary/10 border border-gray-100 p-2">
            <a href="{{ route('haberler.index') }}" class="dropdown-item"><span class="dot"></span>Tüm Haberler</a>
            @foreach($haber_kategorileri as $haber_kategori)
              <a href="{{ route('haberler.index', ['kategori' => $haber_kategori->slug]) }}" class="dropdown-item"><span class="dot"></span>{{ $haber_kategori->ad }}</a>
            @endforeach
          </div>
        </div>

        <div class="has-dropdown">
          <button class="nav-link {{ request()->routeIs('etkinlikler*') ? 'active text-accent font-semibold' : 'text-primary font-medium' }} flex items-center gap-1 font-jakarta text-[13.5px] px-3 py-2 rounded cursor-pointer">
            Etkinlikler
            <svg class="chev w-3.5 h-3.5 {{ request()->routeIs('etkinlikler*') ? 'opacity-60' : 'opacity-40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div class="dropdown-panel bg-white rounded-xl shadow-xl shadow-primary/10 border border-gray-100 p-2">
            <a href="{{ route('etkinlikler.index') }}" class="dropdown-item"><span class="dot"></span>Tüm Etkinlikler</a>
            <a href="{{ route('etkinlikler.index', ['filtre' => 'bu-ay']) }}" class="dropdown-item"><span class="dot"></span>Bu Ay</a>
            <a href="{{ route('etkinlikler.index', ['filtre' => 'takvim']) }}" class="dropdown-item"><span class="dot"></span>Etkinlik Takvimi</a>
          </div>
        </div>

        <a href="{{ route('ekayit.index') }}" class="nav-link {{ request()->routeIs('ekayit*') ? 'active text-accent font-semibold' : 'text-primary font-medium' }} font-jakarta text-[13.5px] px-3 py-2 rounded">E-Kayıt</a>
      </nav>

      <div class="flex items-center gap-1 lg:gap-2">
        <button
          id="search-toggle"
          type="button"
          aria-label="Aramayı aç"
          aria-expanded="false"
          class="flex items-center justify-center w-9 h-9 rounded-md transition-colors {{ request()->routeIs('arama.*') ? 'bg-bg-soft text-accent' : 'text-primary hover:text-accent hover:bg-bg-soft' }}"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <circle cx="11" cy="11" r="8" />
            <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
          </svg>
        </button>

        @if($uye_oturumu_acik && $aktif_uye)
          <a href="{{ route('uye.profil.index') }}"
             class="hidden sm:flex items-center gap-2 rounded-full border border-primary/10 bg-white py-1 pl-1 pr-3 no-underline transition-colors hover:bg-bg-soft">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary font-jakarta text-[11px] font-bold text-cream">
              {{ $uye_bas_harfleri }}
            </span>
            <span class="hidden md:block text-left">
              <span class="block font-jakarta text-[11px] leading-none text-teal-muted">Profilim</span>
              <span class="mt-0.5 block max-w-[120px] truncate font-jakarta text-[12.5px] font-semibold text-primary">
                {{ $aktif_uye->ad_soyad }}
              </span>
            </span>
          </a>

          <form action="{{ route('uye.cikis') }}" method="POST" class="hidden lg:block">
            @csrf
            <button type="submit"
                    class="hidden sm:flex items-center gap-1.5 rounded-md px-2 py-2 text-primary transition-colors hover:bg-bg-soft hover:text-accent">
              <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V7m0 0V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h5a2 2 0 002-2v-1"/>
              </svg>
              <span class="hidden font-jakarta text-[13px] font-medium md:block">Çıkış</span>
            </button>
          </form>
        @elseif($yonetici_oturumu_acik)
          <span class="hidden font-jakarta text-[13px] font-medium md:block">{{ auth()->guard('web')->user()->name }}</span>
        @elseif(Route::has('login'))
          <a href="{{ route('login') }}" class="hidden sm:flex items-center gap-1.5 text-primary hover:text-accent transition-colors px-2 py-2 rounded-md hover:bg-bg-soft">
            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <circle cx="12" cy="8" r="4"/>
              <path stroke-linecap="round" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            <span class="font-jakarta text-[13px] font-medium hidden md:block">Giriş</span>
          </a>
        @endif

        <a href="{{ route('bagis.sepet') }}" aria-label="Sepet" id="cart-btn" data-cart-trigger="true" aria-controls="cart-drawer" aria-expanded="false" class="relative flex items-center justify-center w-9 h-9 rounded-md text-primary hover:text-accent hover:bg-bg-soft transition-colors">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>

          @if(session('sepet') && count(session('sepet')) > 0)
            <span id="sepet-badge" class="absolute -top-0.5 -right-0.5 w-[18px] h-[18px] bg-orange-cta text-white text-[10px] font-bold font-jakarta rounded-full flex items-center justify-center leading-none shadow-sm">
              {{ $sepet_adet }}
            </span>
          @else
            <span id="sepet-badge" class="absolute -top-0.5 -right-0.5 hidden w-[18px] h-[18px] bg-orange-cta text-white text-[10px] font-bold font-jakarta rounded-full items-center justify-center leading-none shadow-sm">0</span>
          @endif
        </a>

        <a href="{{ route('bagis.index') }}" class="flex items-center gap-1.5 bg-orange-cta hover:bg-[#c94620] text-white font-jakarta font-bold text-[13px] px-4 py-2 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md active:scale-95 ml-1">
          <svg class="w-[14px] h-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
          </svg>
          <span class="hidden sm:inline">BAĞIŞ YAP</span>
          <span class="sm:hidden">Bağış</span>
        </a>

        <button id="ham-btn" aria-label="Menüyü Aç/Kapat" aria-expanded="false" class="lg:hidden flex flex-col justify-center gap-[5px] p-2 ml-1 rounded-md hover:bg-bg-soft transition-colors">
          <span class="ham-line line1"></span>
          <span class="ham-line line2"></span>
          <span class="ham-line line3"></span>
        </button>
      </div>
    </div>
  </div>

  <div id="search-drawer" aria-hidden="true">
    <div class="search-modal-panel">
      <div class="mb-3 flex items-center justify-between gap-3">
        <h3 class="font-jakarta text-base font-bold text-primary">Site İçi Arama</h3>
        <button type="button" data-search-close class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-primary/10 text-primary transition-colors hover:bg-bg-soft" aria-label="Aramayı kapat">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <form action="{{ route('arama.index') }}" method="GET" role="search" class="flex items-center gap-2" data-search-form>
        <div class="relative flex-1">
          <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-primary/45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
          </svg>
          <input
            type="search"
            name="q"
            value="{{ $header_arama }}"
            class="header-search-input"
            placeholder="Sitede ara..."
            autocomplete="off"
            aria-label="Sitede ara"
          >
        </div>
        <button type="submit" class="inline-flex h-[42px] items-center justify-center rounded-[10px] bg-orange-cta px-4 font-jakarta text-[13px] font-bold text-white transition-colors hover:bg-[#c94620]">Ara</button>
      </form>

      <div class="mt-3 space-y-3">
        <div>
          <p class="header-search-heading">Popüler Aramalar</p>
          <div class="flex flex-wrap gap-2">
            @foreach($populer_aramalar as $etiket)
              <a href="{{ route('arama.index', ['q' => $etiket]) }}" class="search-chip">{{ $etiket }}</a>
            @endforeach
          </div>
        </div>

        <div data-recent-block class="hidden">
          <p class="header-search-heading">Son Aramalar</p>
          <div data-recent-searches class="flex flex-wrap gap-2"></div>
        </div>
      </div>
    </div>
  </div>

  <div id="mobile-menu" aria-hidden="true">
    <nav class="border-t border-gray-100 bg-white px-4 pb-5 pt-2 space-y-0.5">
      <div>
        <button class="mob-acc-btn {{ $kurumsal_menu_aktif ? 'open text-accent font-semibold bg-bg-soft' : 'text-primary font-medium hover:bg-bg-soft hover:text-accent' }} w-full flex items-center justify-between px-3 py-2.5 rounded-lg font-jakarta text-[14px] transition-colors" data-target="mob-kurumsal">
          <span class="flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>Kurumsal
          </span>
          <svg class="mob-chev w-4 h-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div id="mob-kurumsal" class="mob-sub pl-5 space-y-0.5 mt-0.5 {{ $kurumsal_menu_aktif ? 'open' : '' }}">
          <a href="{{ route('kurumsal.show') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Kurumsal Anasayfa</a>
          @forelse($kurumsal_standart_sayfalar as $kurumsal_sayfa)
            <a href="{{ route('kurumsal.show', $kurumsal_sayfa->slug) }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">{{ $kurumsal_sayfa->ad }}</a>
          @empty
            <a href="{{ route('kurumsal.show', 'hakkimizda') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Hakkımızda</a>
          @endforelse
        </div>
      </div>

      <div>
        <button class="mob-acc-btn {{ $egitim_menu_aktif ? 'open text-accent font-semibold bg-bg-soft' : 'text-primary font-medium hover:bg-bg-soft hover:text-accent' }} w-full flex items-center justify-between px-3 py-2.5 rounded-lg font-jakarta text-[14px] transition-colors" data-target="mob-egitim-ogretim">
          Eğitim/Öğretim
          <svg class="mob-chev w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div id="mob-egitim-ogretim" class="mob-sub pl-5 space-y-0.5 mt-0.5 {{ $egitim_menu_aktif ? 'open' : '' }}">
          <a href="{{ route('kurumsal.show', 'kurumlar') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Kurumlar</a>
          @forelse($egitim_ogretim_sayfalari as $egitim_ogretim_sayfasi)
            <a href="{{ route('kurumsal.show', $egitim_ogretim_sayfasi->slug) }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">{{ $egitim_ogretim_sayfasi->ad }}</a>
          @empty
            <a href="{{ route('kurumsal.show', 'kurumlar') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Kurum listesi yakında eklenecek</a>
          @endforelse
        </div>
      </div>

      <div>
        <button class="mob-acc-btn {{ $atolye_menu_aktif ? 'open text-accent font-semibold bg-bg-soft' : 'text-primary font-medium hover:bg-bg-soft hover:text-accent' }} w-full flex items-center justify-between px-3 py-2.5 rounded-lg font-jakarta text-[14px] transition-colors" data-target="mob-atolyeler">
          Atölyeler
          <svg class="mob-chev w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div id="mob-atolyeler" class="mob-sub pl-5 space-y-0.5 mt-0.5 {{ $atolye_menu_aktif ? 'open' : '' }}">
          <a href="{{ route('kurumsal.show', 'atolyeler') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Atölyeler</a>
          @forelse($atolye_sayfalari as $atolye_sayfasi)
            <a href="{{ route('kurumsal.show', $atolye_sayfasi->slug) }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">{{ $atolye_sayfasi->ad }}</a>
          @empty
            <a href="{{ route('kurumsal.show', 'atolyeler') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Atölye listesi yakında eklenecek</a>
          @endforelse
        </div>
      </div>

      <div>
        <button class="mob-acc-btn {{ request()->routeIs('haberler*') ? 'open text-accent font-semibold bg-bg-soft' : 'text-primary font-medium hover:bg-bg-soft hover:text-accent' }} w-full flex items-center justify-between px-3 py-2.5 rounded-lg font-jakarta text-[14px] transition-colors" data-target="mob-haberler">
          Haberler
          <svg class="mob-chev w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div id="mob-haberler" class="mob-sub pl-5 space-y-0.5 mt-0.5 {{ request()->routeIs('haberler*') ? 'open' : '' }}">
          <a href="{{ route('haberler.index') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Tüm Haberler</a>
          @foreach($haber_kategorileri as $haber_kategori)
            <a href="{{ route('haberler.index', ['kategori' => $haber_kategori->slug]) }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">{{ $haber_kategori->ad }}</a>
          @endforeach
        </div>
      </div>

      <div>
        <button class="mob-acc-btn {{ request()->routeIs('etkinlikler*') ? 'open text-accent font-semibold bg-bg-soft' : 'text-primary font-medium hover:bg-bg-soft hover:text-accent' }} w-full flex items-center justify-between px-3 py-2.5 rounded-lg font-jakarta text-[14px] transition-colors" data-target="mob-etkinlikler">
          Etkinlikler
          <svg class="mob-chev w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div id="mob-etkinlikler" class="mob-sub pl-5 space-y-0.5 mt-0.5 {{ request()->routeIs('etkinlikler*') ? 'open' : '' }}">
          <a href="{{ route('etkinlikler.index') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Tüm Etkinlikler</a>
          <a href="{{ route('etkinlikler.index', ['filtre' => 'bu-ay']) }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Bu Ay</a>
          <a href="{{ route('etkinlikler.index', ['filtre' => 'takvim']) }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Etkinlik Takvimi</a>
        </div>
      </div>

      <a href="{{ route('ekayit.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('ekayit*') ? 'text-accent font-semibold bg-bg-soft' : 'text-primary font-medium hover:bg-bg-soft hover:text-accent' }} font-jakarta text-[14px] transition-colors">E-Kayıt</a>

      <div class="pt-3 flex items-center gap-2 border-t border-gray-100 mt-1">
        @if($uye_oturumu_acik && $aktif_uye)
          <a href="{{ route('uye.profil.index') }}" class="flex items-center gap-2 text-primary font-jakarta font-medium text-[13px] px-3 py-2 rounded-md hover:bg-bg-soft transition-colors">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-cream">
              {{ $uye_bas_harfleri }}
            </span>
            Profilim
          </a>

          <form id="logout-form-mobile" action="{{ route('uye.cikis') }}" method="POST">
            @csrf
            <button type="submit" class="flex items-center gap-1.5 text-primary font-jakarta font-medium text-[13px] px-3 py-2 rounded-md hover:bg-bg-soft transition-colors">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V7m0 0V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h5a2 2 0 002-2v-1"/>
              </svg>
              Çıkış
            </button>
          </form>
        @elseif(Route::has('login'))
          <a href="{{ route('login') }}" class="flex items-center gap-1.5 text-primary font-jakarta font-medium text-[13px] px-3 py-2 rounded-md hover:bg-bg-soft transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <circle cx="12" cy="8" r="4"/>
              <path stroke-linecap="round" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            Giriş Yap
          </a>
        @endif

        <a href="{{ route('bagis.sepet') }}" class="flex items-center gap-1.5 text-primary font-jakarta font-medium text-[13px] px-3 py-2 rounded-md hover:bg-bg-soft transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          Sepet ({{ $sepet_adet }})
        </a>

        <a href="{{ route('bagis.index') }}" class="flex-1 text-center bg-orange-cta hover:bg-[#c94620] text-white font-jakarta font-bold text-[13px] px-4 py-2 rounded-lg transition-colors">
          BAĞIŞ YAP
        </a>
      </div>
    </nav>
  </div>
</header>

<div id="cart-drawer-overlay" class="hidden fixed inset-0 z-[70] bg-slate-950/35 opacity-0 transition-opacity duration-200"></div>
<aside id="cart-drawer"
       aria-hidden="true"
       class="fixed right-0 top-0 z-[80] flex h-full w-full max-w-md translate-x-full flex-col border-l border-primary/10 bg-white shadow-2xl transition-transform duration-300 ease-out">
  <div class="flex items-center justify-between border-b border-slate-100 px-4 py-4 sm:px-5">
    <div>
      <p class="font-baskerville text-[24px] font-bold text-primary">Sepetim</p>
      <p id="cart-drawer-count" class="mt-1 font-jakarta text-xs text-teal-muted">{{ $sepet_adet > 0 ? $sepet_adet.' kalem' : 'Sepet boş' }}</p>
    </div>
    <button type="button" id="cart-drawer-close" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-primary/10 text-primary transition-colors hover:bg-bg-soft" aria-label="Sepeti kapat">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>

  <div class="border-b border-slate-100 bg-bg-soft px-4 py-3 sm:px-5">
    <p class="font-jakarta text-[12px] leading-5 text-teal-muted">Bağış kalemlerinizi hızlıca gözden geçirin, isterseniz buradan silin ya da ödeme adımına geçin.</p>
  </div>

  <div id="cart-drawer-items"
       data-cart-items='@json($sepet)'
       data-remove-url="{{ url('/bagis/sepetten-cikar') }}"
       data-cart-url="{{ route('bagis.sepet') }}"
       data-bagis-url="{{ route('bagis.index') }}"
       class="flex-1 space-y-3 overflow-y-auto px-4 py-4 sm:px-5">
  </div>

  <div class="border-t border-slate-100 px-4 py-4 sm:px-5">
    <div class="mb-3 flex items-center justify-between font-jakarta text-sm text-teal-muted">
      <span>Toplam</span>
      <span id="cart-drawer-total" class="font-baskerville text-[22px] font-bold text-primary">₺{{ number_format((float) $sepet_toplam, 2, ',', '.') }}</span>
    </div>

    <div class="space-y-2.5">
      <a href="{{ route('bagis.sepet') }}" class="flex w-full items-center justify-center rounded-[10px] bg-orange-cta px-4 py-3 font-jakarta text-sm font-bold text-white transition-colors hover:bg-[#c94620]">
        Sepeti Gör ve Ödeme Adımına Geç
      </a>
      <a href="{{ route('bagis.index') }}" class="flex w-full items-center justify-center rounded-[10px] border border-primary/10 bg-white px-4 py-3 font-jakarta text-sm font-semibold text-primary transition-colors hover:bg-bg-soft">
        Yeni Bağış Ekle
      </a>
    </div>
  </div>
</aside>
