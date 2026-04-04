@php
    $telefon = config('site.telefon');
    $sepet = session('sepet', []);
    $sepet_adet = is_array($sepet) ? count($sepet) : 0;
    $header_arama = trim((string) request('q', ''));
    $populer_aramalar = ['burs', 'etkinlik', 'kurban', 'kayıt', 'zekat', 'mezun'];
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
    <div class="flex items-center justify-between h-16 lg:h-[68px]">
      <a href="{{ route('home') }}" class="flex items-center gap-3 flex-shrink-0">
        <span class="logo-k w-9 h-9 rounded-lg flex items-center justify-center text-lg select-none shadow-sm">K</span>
        <span class="font-baskerville text-primary font-bold text-[17px] leading-tight hidden sm:block">Kestanepazarı</span>
      </a>

      <nav class="hidden lg:flex items-center gap-0.5">
        <div class="has-dropdown">
          <button class="nav-link {{ request()->routeIs('kurumsal*') ? 'active text-accent font-semibold' : 'text-primary font-medium' }} flex items-center gap-1 font-jakarta text-[13.5px] px-3 py-2 rounded cursor-pointer">
            Kurumsal
            <svg class="chev w-3.5 h-3.5 {{ request()->routeIs('kurumsal*') ? 'opacity-60' : 'opacity-40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div class="dropdown-panel bg-white rounded-xl shadow-xl shadow-primary/10 border border-gray-100 p-2">
            <a href="{{ route('kurumsal.show', 'hakkimizda') }}" class="dropdown-item"><span class="dot"></span>Hakkımızda</a>
            <a href="{{ route('kurumsal.show', 'yonetim-kurulu') }}" class="dropdown-item"><span class="dot"></span>Yönetim Kurulu</a>
            <a href="{{ route('kurumsal.show', 'dernek-tuzugu') }}" class="dropdown-item"><span class="dot"></span>Dernek Tüzüğü</a>
            <a href="{{ route('kurumsal.show', 'faaliyet-raporlari') }}" class="dropdown-item"><span class="dot"></span>Faaliyet Raporları</a>
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
            <a href="{{ route('haberler.index', ['kategori' => 'egitim']) }}" class="dropdown-item"><span class="dot"></span>Eğitim</a>
            <a href="{{ route('haberler.index', ['kategori' => 'ziyaret']) }}" class="dropdown-item"><span class="dot"></span>Ziyaret</a>
            <a href="{{ route('haberler.index', ['kategori' => 'etkinlikler']) }}" class="dropdown-item"><span class="dot"></span>Etkinlikler</a>
            <a href="{{ route('haberler.index', ['kategori' => 'kurban-bagis']) }}" class="dropdown-item"><span class="dot"></span>Kurban &amp; Bağış</a>
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
        <a href="{{ route('mezunlar.index') }}" class="nav-link {{ request()->routeIs('mezunlar*') ? 'active text-accent font-semibold' : 'text-primary font-medium' }} font-jakarta text-[13.5px] px-3 py-2 rounded">Mezunlar</a>
        <a href="{{ route('iletisim.index') }}" class="nav-link {{ request()->routeIs('iletisim*') ? 'active text-accent font-semibold' : 'text-primary font-medium' }} font-jakarta text-[13.5px] px-3 py-2 rounded">İletişim</a>
      </nav>

      <div class="flex items-center gap-1 lg:gap-2">
        <div class="header-search-shell hidden xl:block">
          <form action="{{ route('arama.index') }}" method="GET" role="search" class="header-search-form" data-search-form>
            <div class="relative">
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
              <button type="submit" class="header-search-submit">Ara</button>
            </div>
          </form>

          <div class="header-search-panel">
            <div>
              <p class="header-search-heading">Popüler Aramalar</p>
              <div class="flex flex-wrap gap-2">
                @foreach($populer_aramalar as $etiket)
                  <a href="{{ route('arama.index', ['q' => $etiket]) }}" class="search-chip">{{ $etiket }}</a>
                @endforeach
              </div>
            </div>

            <div data-recent-block class="mt-4 hidden">
              <p class="header-search-heading">Son Aramalar</p>
              <div data-recent-searches class="flex flex-wrap gap-2"></div>
            </div>
          </div>
        </div>

        <button
          id="search-toggle"
          type="button"
          aria-label="Aramayı aç"
          aria-expanded="false"
          class="xl:hidden flex items-center justify-center w-9 h-9 rounded-md transition-colors {{ request()->routeIs('arama.*') ? 'bg-bg-soft text-accent' : 'text-primary hover:text-accent hover:bg-bg-soft' }}"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <circle cx="11" cy="11" r="8" />
            <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
          </svg>
        </button>

        @auth
          <span class="font-jakarta text-[13px] font-medium hidden md:block">{{ Auth::user()->name }}</span>
          @if(Route::has('logout'))
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="hidden sm:flex items-center gap-1.5 text-primary hover:text-accent transition-colors px-2 py-2 rounded-md hover:bg-bg-soft">
              <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V7m0 0V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h5a2 2 0 002-2v-1"/>
              </svg>
              <span class="font-jakarta text-[13px] font-medium hidden md:block">Çıkış</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
          @endif
        @else
          @if(Route::has('login'))
            <a href="{{ route('login') }}" class="hidden sm:flex items-center gap-1.5 text-primary hover:text-accent transition-colors px-2 py-2 rounded-md hover:bg-bg-soft">
              <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <circle cx="12" cy="8" r="4"/>
                <path stroke-linecap="round" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
              </svg>
              <span class="font-jakarta text-[13px] font-medium hidden md:block">Giriş</span>
            </a>
          @endif
        @endauth

        <button aria-label="Sepet" id="cart-btn" class="relative flex items-center justify-center w-9 h-9 rounded-md text-primary hover:text-accent hover:bg-bg-soft transition-colors">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>

          @if(session('sepet') && count(session('sepet')) > 0)
            <span class="absolute -top-0.5 -right-0.5 w-[18px] h-[18px] bg-orange-cta text-white text-[10px] font-bold font-jakarta rounded-full flex items-center justify-center leading-none shadow-sm">
              {{ $sepet_adet }}
            </span>
          @endif
        </button>

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

  <div id="search-drawer" aria-hidden="true" class="xl:hidden">
    <div class="border-t border-gray-100 bg-white px-4 pb-4 pt-3">
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
        <button class="mob-acc-btn {{ request()->routeIs('kurumsal*') ? 'open text-accent font-semibold bg-bg-soft' : 'text-primary font-medium hover:bg-bg-soft hover:text-accent' }} w-full flex items-center justify-between px-3 py-2.5 rounded-lg font-jakarta text-[14px] transition-colors" data-target="mob-kurumsal">
          <span class="flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>Kurumsal
          </span>
          <svg class="mob-chev w-4 h-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div id="mob-kurumsal" class="mob-sub pl-5 space-y-0.5 mt-0.5 {{ request()->routeIs('kurumsal*') ? 'open' : '' }}">
          <a href="{{ route('kurumsal.show', 'hakkimizda') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Hakkımızda</a>
          <a href="{{ route('kurumsal.show', 'yonetim-kurulu') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Yönetim Kurulu</a>
          <a href="{{ route('kurumsal.show', 'dernek-tuzugu') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Dernek Tüzüğü</a>
          <a href="{{ route('kurumsal.show', 'faaliyet-raporlari') }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Faaliyet Raporları</a>
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
          <a href="{{ route('haberler.index', ['kategori' => 'egitim']) }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Eğitim</a>
          <a href="{{ route('haberler.index', ['kategori' => 'ziyaret']) }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Ziyaret</a>
          <a href="{{ route('haberler.index', ['kategori' => 'etkinlikler']) }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Etkinlikler</a>
          <a href="{{ route('haberler.index', ['kategori' => 'kurban-bagis']) }}" class="block px-3 py-2 rounded-lg text-primary hover:bg-bg-soft hover:text-accent font-jakarta text-[13.5px] transition-colors">Kurban &amp; Bağış</a>
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
      <a href="{{ route('mezunlar.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('mezunlar*') ? 'text-accent font-semibold bg-bg-soft' : 'text-primary font-medium hover:bg-bg-soft hover:text-accent' }} font-jakarta text-[14px] transition-colors">Mezunlar</a>
      <a href="{{ route('iletisim.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('iletisim*') ? 'text-accent font-semibold bg-bg-soft' : 'text-primary font-medium hover:bg-bg-soft hover:text-accent' }} font-jakarta text-[14px] transition-colors">İletişim</a>

      <div class="pt-3 flex items-center gap-2 border-t border-gray-100 mt-1">
        @auth
          @if(Route::has('logout'))
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();"
               class="flex items-center gap-1.5 text-primary font-jakarta font-medium text-[13px] px-3 py-2 rounded-md hover:bg-bg-soft transition-colors">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V7m0 0V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h5a2 2 0 002-2v-1"/>
              </svg>
              Çıkış
            </a>
            <form id="logout-form-mobile" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
          @endif
        @else
          @if(Route::has('login'))
            <a href="{{ route('login') }}" class="flex items-center gap-1.5 text-primary font-jakarta font-medium text-[13px] px-3 py-2 rounded-md hover:bg-bg-soft transition-colors">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <circle cx="12" cy="8" r="4"/>
                <path stroke-linecap="round" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
              </svg>
              Giriş Yap
            </a>
          @endif
        @endauth

        <div class="flex items-center gap-1.5 text-primary font-jakarta font-medium text-[13px] px-3 py-2 rounded-md hover:bg-bg-soft transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          Sepet ({{ $sepet_adet }})
        </div>

        <a href="{{ route('bagis.index') }}" class="flex-1 text-center bg-orange-cta hover:bg-[#c94620] text-white font-jakarta font-bold text-[13px] px-4 py-2 rounded-lg transition-colors">
          BAĞIŞ YAP
        </a>
      </div>
    </nav>
  </div>
</header>
