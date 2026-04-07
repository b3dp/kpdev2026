<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', config('site.ad')) — Kestanepazarı</title>
<meta name="description" content="@yield('meta_description', config('site.aciklama'))">
<meta name="robots" content="@yield('robots', 'index, follow')">
<link rel="canonical" href="@yield('canonical', url()->current())">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:title" content="@yield('title', config('site.ad')) — Kestanepazarı">
<meta property="og:description" content="@yield('meta_description', config('site.aciklama'))">
<meta property="og:image" content="@yield('og_image', asset('img/og-default.jpg'))">
<meta property="og:url" content="{{ url()->current() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,700;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Organization",
  "name": "{{ config('site.ad') }} Öğrenci Yetiştirme Derneği",
  "url": "{{ url('/') }}",
  "logo": "https://cdn.kestanepazari.org.tr/logo.png",
  "telephone": "{{ config('site.telefon') }}",
  "email": "{{ config('site.eposta') }}"
}
</script>
@yield('schema')
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="font-jakarta bg-bg-soft">

  @php
    $checkout_sepet = session('sepet', $sepet ?? []);
    $checkout_sepet = is_array($checkout_sepet) ? $checkout_sepet : [];
    $checkout_sepet_toplam = collect($checkout_sepet)->sum(fn (array $satir) => (float) ($satir['toplam'] ?? 0));
  @endphp

  <header style="position:fixed;top:0;left:0;right:0;z-index:50;background:#fff;border-bottom:1px solid #f1f1ef;">
    <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
      <div style="display:flex;align-items:center;justify-content:space-between;height:64px;gap:16px;">
        <a href="{{ route('home') }}" style="display:flex;align-items:center;gap:12px;text-decoration:none;flex-shrink:0;">
          <span style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#162E4B,#28484C);color:#EBDFB5;font-family:'Libre Baskerville',serif;font-weight:700;font-size:17px;display:flex;align-items:center;justify-content:center;">K</span>
          <span style="font-family:'Libre Baskerville',serif;font-weight:700;font-size:16px;color:#162E4B;">{{ config('site.ad') }}</span>
        </a>

        @hasSection('checkout_progress')
          @yield('checkout_progress')
        @else
          <div class="hidden items-center gap-2 rounded-lg border border-primary/10 bg-bg-soft px-3.5 py-[7px] md:flex">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span class="font-jakarta text-[12.5px] font-medium text-primary">256-bit SSL Güvenli Ödeme</span>
          </div>
        @endif

        @hasSection('checkout_actions')
          @yield('checkout_actions')
        @else
          <div style="display:flex;align-items:center;gap:8px;">
            <a href="{{ route('bagis.index') }}"
               class="flex items-center gap-1.5 font-jakarta text-[13px] text-teal-muted no-underline transition-colors hover:text-primary">
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Bağış Türleri
            </a>
            <a href="{{ route('bagis.sepet') }}" id="cart-btn" data-cart-trigger="true" aria-controls="cart-drawer" aria-expanded="false" style="position:relative;width:36px;height:36px;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#162E4B;flex-shrink:0;text-decoration:none;">
              <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              @if(count($checkout_sepet) > 0)
                <span id="sepet-badge" style="position:absolute;top:-2px;right:-2px;width:17px;height:17px;background:#E95925;color:#fff;font-size:10px;font-weight:700;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Plus Jakarta Sans',sans-serif;">{{ count($checkout_sepet) }}</span>
              @else
                <span id="sepet-badge" style="position:absolute;top:-2px;right:-2px;width:17px;height:17px;background:#E95925;color:#fff;font-size:10px;font-weight:700;border-radius:50%;display:none;align-items:center;justify-content:center;font-family:'Plus Jakarta Sans',sans-serif;">0</span>
              @endif
            </a>
          </div>
        @endif
      </div>
    </div>
  </header>

  <div id="cart-drawer-overlay" class="hidden fixed inset-0 z-[70] bg-slate-950/35 opacity-0 transition-opacity duration-200"></div>
  <aside id="cart-drawer"
         aria-hidden="true"
         class="fixed right-0 top-0 z-[80] flex h-full w-full max-w-md translate-x-full flex-col border-l border-primary/10 bg-white shadow-2xl transition-transform duration-300 ease-out">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-4 sm:px-5">
      <div>
        <p class="font-baskerville text-[24px] font-bold text-primary">Sepetim</p>
        <p id="cart-drawer-count" class="mt-1 font-jakarta text-xs text-teal-muted">{{ count($checkout_sepet) > 0 ? count($checkout_sepet).' kalem' : 'Sepet boş' }}</p>
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
         data-cart-items='@json($checkout_sepet)'
         data-remove-url="{{ url('/bagis/sepetten-cikar') }}"
         data-cart-url="{{ route('bagis.sepet') }}"
         data-bagis-url="{{ route('bagis.index') }}"
         class="flex-1 space-y-3 overflow-y-auto px-4 py-4 sm:px-5">
    </div>

    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">
      <div class="mb-3 flex items-center justify-between font-jakarta text-sm text-teal-muted">
        <span>Toplam</span>
        <span id="cart-drawer-total" class="font-baskerville text-[22px] font-bold text-primary">₺{{ number_format((float) $checkout_sepet_toplam, 2, ',', '.') }}</span>
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

  <main>
    @yield('content')
  </main>

  <footer>
    <div style="height:3px;background:linear-gradient(to right,transparent,#B27829 30%,#B27829 70%,transparent);opacity:.7;"></div>
    <div style="background-color:#EBDFB5;">
      <div style="max-width:1280px;margin:0 auto;padding:32px 24px;">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;">
          <a href="{{ route('home') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
            <span style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#162E4B,#28484C);color:#EBDFB5;font-family:'Libre Baskerville',serif;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;">K</span>
            <span style="font-family:'Libre Baskerville',serif;font-weight:700;font-size:15px;color:#162E4B;">{{ config('site.ad') }}</span>
          </a>
          <div style="display:flex;gap:16px;align-items:center;">
            <a href="{{ route('kurumsal.show', 'gizlilik-politikasi') }}" class="bottom-link transition-colors hover:text-accent">Gizlilik</a>
            <span style="color:rgba(22,46,75,.2);font-size:10px;">|</span>
            <a href="{{ route('kurumsal.show', 'cerez-politikasi') }}" class="bottom-link transition-colors hover:text-accent">Çerez</a>
            <span style="color:rgba(22,46,75,.2);font-size:10px;">|</span>
            <a href="{{ route('kurumsal.show', 'kvkk') }}" class="bottom-link transition-colors hover:text-accent">KVKK</a>
          </div>
          <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:rgba(22,46,75,.5);">© {{ date('Y') }} Kestanepazarı Derneği</p>
        </div>
      </div>
    </div>
  </footer>

  @stack('scripts')
</body>
</html>
