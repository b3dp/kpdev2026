@extends('layouts.app')

@section('title', 'Ana Sayfa')
@section('meta_description', config('site.aciklama'))
@section('robots', 'index, follow')
@section('og_type', 'website')
@section('og_image', asset('img/og-default.jpg'))

@section('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "WebSite",
  "name": "{{ config('site.ad') }} Öğrenci Yetiştirme Derneği",
  "url": "{{ url('/') }}",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "{{ url('/arama') }}?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
@endsection



@section('content')
@php
  $sagKolonHaberler = $mansetHaberler->take(3);
  if ($sagKolonHaberler->isEmpty()) {
    $sagKolonHaberler = $sonHaberler->take(3);
  }
    $buyukHaber = $sonHaberler->first();
    $ortaHaber = $sonHaberler->skip(1)->take(1)->first();
    $kucukHaberler = $sonHaberler->skip(2)->take(2);
    $aktifKategori = request('kategori');
@endphp

<section class="bg-bg-soft pb-[72px] pt-[106px]">
  <div class="mx-auto max-w-7xl px-6">
    <div class="grid items-center gap-12 lg:grid-cols-2">
      <div>
        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-accent/25 bg-white px-4 py-1.5">
          <span class="inline-block h-[7px] w-[7px] shrink-0 rounded-full bg-accent"></span>
          <span class="font-jakarta text-xs font-semibold tracking-[0.04em] text-accent">Seferihisar, İzmir - 1966'dan beri</span>
        </div>

        <h1 class="mb-5 font-baskerville text-[clamp(34px,4.5vw,56px)] font-bold leading-[1.18] text-primary">
          Geleceği<br>
          <span class="italic text-accent">Birlikte</span><br>
          İnşa Ediyoruz
        </h1>

        <p class="mb-8 max-w-[440px] font-jakarta text-base leading-[1.75] text-teal-muted">
          58 yıldır Seferihisar gençlerinin eğitim hayatında yanlarındayız. Her bağışınız bir öğrencinin geleceğini şekillendiriyor.
        </p>

        <div class="flex flex-wrap gap-3">
          <a href="{{ route('bagis.index') }}" class="flex items-center gap-2 rounded-[10px] bg-orange-cta px-[26px] py-[13px] font-jakarta text-sm font-bold text-white shadow-[0_4px_16px_rgba(233,89,37,.25)] transition-colors hover:bg-[#c94620]">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            Bağış Yap
          </a>
          <a href="{{ route('ekayit.index') }}" class="flex items-center gap-2 rounded-[10px] border border-primary/20 bg-white px-[26px] py-[13px] font-jakarta text-sm font-semibold text-primary transition-colors hover:bg-bg-soft">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Öğrenci Kayıt
          </a>
        </div>

        <div class="mt-10 flex gap-8 border-t border-primary/10 pt-8">
          <div>
            <p class="mb-1 font-baskerville text-[28px] font-bold text-primary">1.250</p>
            <p class="font-jakarta text-[12.5px] font-medium text-teal-muted">Aktif Öğrenci</p>
          </div>
          <div>
            <p class="mb-1 font-baskerville text-[28px] font-bold text-primary">4.500+</p>
            <p class="font-jakarta text-[12.5px] font-medium text-teal-muted">Mezun</p>
          </div>
          <div>
            <p class="mb-1 font-baskerville text-[28px] font-bold text-primary">58</p>
            <p class="font-jakarta text-[12.5px] font-medium text-teal-muted">Yıllık Tecrübe</p>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-3">
        @forelse($sagKolonHaberler as $index => $manset)
          <a href="{{ route('haberler.show', $manset->slug) }}" class="hero-news-card">
            <div class="hero-img-wrap" style="height:{{ $index === 0 ? '180px' : '150px' }};">
              @if($manset->gorsel_lg)
                <img
                  src="https://cdn.kestanepazari.org.tr/{{ $manset->gorsel_lg }}"
                  alt="{{ $manset->baslik }}"
                  class="absolute inset-0 h-full w-full object-cover"
                  loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                  @if($index === 0) fetchpriority="high" @endif
                  width="560"
                  height="{{ $index === 0 ? '180' : '150' }}"
                >
              @else
                <div style="position:absolute;inset:0;background:linear-gradient(160deg,{{ $index % 2 === 0 ? '#2a4a6b 0%,#0d1f33 100%' : '#1e3d2f 0%,#0d2618 100%' }});display:flex;align-items:center;justify-content:center;">
                  <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,0.12)" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                </div>
              @endif

              <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(8,16,26,.9) 0%,rgba(8,16,26,.2) 60%,transparent 100%);"></div>

              <div style="position:relative;z-index:1;width:100%;">
                @if($manset->kategori)
                  <span class="badge-cat mb-2" style="background:{{ $manset->kategori->renk ?? ($index === 0 ? '#3B82F6' : '#FF9300') }};color:#fff;">{{ $manset->kategori->ad }}</span>
                @endif
                <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:{{ $index === 0 ? '15px' : '14px' }};color:#fff;margin:0 0 8px;line-height:1.35;">{{ $manset->baslik }}</h3>
                <span class="meta-date">
                  <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                  {{ $manset->yayin_tarihi?->format('d M Y') }}
                </span>
              </div>
            </div>
          </a>
        @empty
          <div class="rounded-[14px] border border-primary/10 bg-white p-5 text-center font-jakarta text-sm text-teal-muted">
            Manşet haber bulunamadı.
          </div>
        @endforelse
      </div>
    </div>
  </div>
</section>

<section class="relative overflow-hidden bg-[linear-gradient(135deg,#162E4B_0%,#091420_100%)] py-16">
  <div class="absolute -right-[60px] -top-[60px] h-[250px] w-[250px] rounded-full bg-accent/10"></div>
  <div class="relative z-[1] mx-auto max-w-7xl px-6 text-center">
    <p class="mb-3.5 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.1em] text-cream/60">Harekete Geç</p>
    <h2 class="mb-4 font-baskerville text-[clamp(24px,3.5vw,38px)] font-bold leading-[1.25] text-cream">Geleceği Birlikte <span class="italic text-accent">İnşa Edelim</span></h2>
    <p class="mx-auto mb-7 max-w-[560px] font-jakarta text-[15px] leading-[1.7] text-cream/60">Her yıl yüzlerce öğrenci burs desteğimizle hayallerine ulaşıyor.</p>
    <div class="flex flex-wrap justify-center gap-3">
      <a href="{{ route('bagis.index') }}" class="flex items-center gap-2 rounded-[10px] bg-orange-cta px-7 py-[13px] font-jakarta text-sm font-bold text-white transition-colors hover:bg-[#c94620]">Bağış Yap</a>
      <a href="{{ route('ekayit.index') }}" class="flex items-center gap-2 rounded-[10px] border border-cream/30 px-7 py-[13px] font-jakarta text-sm font-semibold text-cream transition-colors hover:bg-white/10">Öğrenci Kayıt</a>
    </div>
  </div>
</section>

<section class="bg-bg-soft py-[72px]">
  <div class="mx-auto max-w-7xl px-6">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="mb-2 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.1em] text-accent">İyiliğin Farklı Yüzleri</p>
        <h2 class="font-baskerville text-[clamp(22px,3vw,32px)] font-bold text-primary">Bağış Türleri</h2>
      </div>
      <a href="{{ route('bagis.index') }}" class="flex items-center gap-1.5 font-jakarta text-[13px] font-semibold text-primary opacity-70 transition-opacity hover:opacity-100">
        Tümünü Gör
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>

    <div class="bagis-scroll">
      @forelse($bagisturleri as $tur)
        <div class="bagis-kart">
          <div class="bagis-foto">
            @if($tur->gorsel_dikey)
              <img
                src="https://cdn.kestanepazari.org.tr/{{ $tur->gorsel_dikey }}"
                alt="{{ $tur->ad }}"
                class="h-full w-full object-cover"
                loading="lazy"
                width="260"
                height="170"
              >
            @else
              <div style="width:100%;height:170px;background:linear-gradient(135deg,#162E4B 0%,#091420 100%);display:flex;align-items:center;justify-content:center;position:relative;">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="rgba(235,223,181,.2)" stroke-width=".8"><path stroke-linecap="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.5) 0%,transparent 60%);"></div>
              </div>
            @endif

            <div class="bagis-arrow-btn">
              <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
            </div>
          </div>

          <div class="p-[18px]">
            <h3 class="mb-1.5 font-jakarta text-base font-bold text-primary">{{ $tur->ad }}</h3>
            <p class="mb-3.5 font-jakarta text-[13px] leading-[1.55] text-teal-muted">{{ $tur->aciklama }}</p>
            <a href="{{ route('bagis.show', $tur->slug) }}" class="font-jakarta text-[13px] font-bold tracking-[0.04em] text-accent transition-colors hover:text-orange-cta">BAĞIŞ YAP</a>
          </div>
        </div>
      @empty
        <p class="py-8 text-center font-jakarta text-sm text-teal-muted">Henüz bağış türü eklenmemiş.</p>
      @endforelse
    </div>
  </div>
</section>

<section class="bg-white py-[72px]">
  <div class="mx-auto max-w-7xl px-6">
    <div class="mb-3 flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="mb-2 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.1em] text-accent">Güncel Gelişmeler</p>
        <h2 class="font-baskerville text-[clamp(22px,3vw,32px)] font-bold text-primary">Haberler &amp; Duyurular</h2>
      </div>
      <a href="{{ route('haberler.index') }}" class="mt-2 flex items-center gap-1.5 font-jakarta text-[13px] font-bold tracking-[0.04em] text-primary transition-colors hover:text-accent">
        TÜM HABER ARŞİVİ
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>

    <div class="mb-7 flex flex-wrap gap-2">
      <a href="{{ route('haberler.index') }}" class="cat-pill {{ !$aktifKategori ? 'active' : 'inactive' }}">Tümü</a>
      @foreach($kategoriler as $kat)
        <a href="{{ route('haberler.index', ['kategori' => $kat->slug]) }}" class="cat-pill {{ $aktifKategori === $kat->slug ? 'active' : 'inactive' }}">{{ $kat->ad }}</a>
      @endforeach
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
      @if($buyukHaber)
        <a href="{{ route('haberler.show', $buyukHaber->slug) }}" class="haber-buyuk">
          <div class="haber-foto" style="height:320px;flex:1;">
            @if($buyukHaber->gorsel_lg)
              <img
                src="https://cdn.kestanepazari.org.tr/{{ $buyukHaber->gorsel_lg }}"
                alt="{{ $buyukHaber->baslik }}"
                class="h-full w-full object-cover"
                loading="lazy"
                width="640"
                height="320"
              >
            @else
              <div style="width:100%;height:100%;background:linear-gradient(160deg,#2a4060 0%,#0d1e32 100%);display:flex;align-items:center;justify-content:center;">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.12)" stroke-width=".8"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
              </div>
            @endif

            <div class="haber-overlay"></div>
            <div class="haber-bottom">
              @if($buyukHaber->kategori)
                <span style="display:inline-block;background:{{ $buyukHaber->kategori->renk ?? '#3B82F6' }};color:#fff;font-size:11px;font-weight:700;padding:3px 12px;border-radius:999px;margin-bottom:10px;font-family:'Plus Jakarta Sans',sans-serif;">{{ $buyukHaber->kategori->ad }}</span>
              @endif
              <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:20px;color:#fff;margin-bottom:8px;line-height:1.3;">{{ $buyukHaber->baslik }}</h3>
              <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;color:rgba(255,255,255,.65);margin-bottom:10px;line-height:1.5;">{{ $buyukHaber->ozet }}</p>
              <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:rgba(255,255,255,.5);font-family:'Plus Jakarta Sans',sans-serif;">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                {{ $buyukHaber->yayin_tarihi?->format('d M Y') }}
              </span>
            </div>
          </div>
        </a>
      @endif

      <div class="flex flex-col gap-3">
        @if($ortaHaber)
          <a href="{{ route('haberler.show', $ortaHaber->slug) }}" class="haber-kucuk flex-1">
            <div class="haber-foto" style="height:160px;">
              @if($ortaHaber->gorsel_lg)
                <img
                  src="https://cdn.kestanepazari.org.tr/{{ $ortaHaber->gorsel_lg }}"
                  alt="{{ $ortaHaber->baslik }}"
                  class="h-full w-full object-cover"
                  loading="lazy"
                  width="560"
                  height="160"
                >
              @else
                <div style="width:100%;height:100%;background:linear-gradient(160deg,#1a3d30 0%,#0c2018 100%);display:flex;align-items:center;justify-content:center;">
                  <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.12)" stroke-width=".8"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                </div>
              @endif

              <div class="haber-overlay"></div>
              <div class="haber-bottom">
                @if($ortaHaber->kategori)
                  <span style="display:inline-block;background:{{ $ortaHaber->kategori->renk ?? '#FF9300' }};color:#fff;font-size:10.5px;font-weight:700;padding:2px 10px;border-radius:999px;margin-bottom:7px;font-family:'Plus Jakarta Sans',sans-serif;">{{ $ortaHaber->kategori->ad }}</span>
                @endif
                <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:15px;color:#fff;margin-bottom:5px;line-height:1.3;">{{ $ortaHaber->baslik }}</h3>
                <span style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:rgba(255,255,255,.5);font-family:'Plus Jakarta Sans',sans-serif;">
                  <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                  {{ $ortaHaber->yayin_tarihi?->format('d M Y') }}
                </span>
              </div>
            </div>
          </a>
        @endif

        <div class="grid flex-1 grid-cols-2 gap-3">
          @foreach($kucukHaberler as $kucuk)
            <a href="{{ route('haberler.show', $kucuk->slug) }}" class="haber-kucuk">
              <div class="haber-foto" style="height:130px;">
                @if($kucuk->gorsel_sm)
                  <img
                    src="https://cdn.kestanepazari.org.tr/{{ $kucuk->gorsel_sm }}"
                    alt="{{ $kucuk->baslik }}"
                    class="h-full w-full object-cover"
                    loading="lazy"
                    width="280"
                    height="130"
                  >
                @else
                  <div style="width:100%;height:100%;background:linear-gradient(160deg,#2d3748 0%,#1a202c 100%);display:flex;align-items:center;justify-content:center;">
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.12)" stroke-width=".8"><rect x="3" y="3" width="18" height="18" rx="3"/></svg>
                  </div>
                @endif

                <div class="haber-overlay"></div>
                <div class="haber-bottom" style="padding:10px;">
                  @if($kucuk->kategori)
                    <span style="display:inline-block;background:{{ $kucuk->kategori->renk ?? 'rgba(255,255,255,.2)' }};color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;margin-bottom:5px;font-family:'Plus Jakarta Sans',sans-serif;backdrop-filter:blur(4px);">{{ $kucuk->kategori->ad }}</span>
                  @endif
                  <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:12.5px;color:#fff;margin-bottom:4px;line-height:1.3;">{{ $kucuk->baslik }}</h3>
                  <span style="font-size:11px;color:rgba(255,255,255,.45);font-family:'Plus Jakarta Sans',sans-serif;">{{ $kucuk->yayin_tarihi?->format('d M Y') }}</span>
                </div>
              </div>
            </a>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

<section class="bg-bg-soft py-[72px]">
  <div class="mx-auto max-w-7xl px-6">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="mb-2 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.1em] text-accent">Takvim</p>
        <h2 class="font-baskerville text-[clamp(22px,3vw,32px)] font-bold text-primary">Yaklaşan Etkinlikler</h2>
      </div>
      <a href="{{ route('etkinlikler.index') }}" class="flex items-center gap-1.5 font-jakarta text-[13px] font-semibold text-primary opacity-70 transition-opacity hover:opacity-100">
        Tüm Takvim
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
      @forelse($yaklasanEtkinlikler as $etkinlik)
        <div class="etk-kart">
          <div class="etk-date">
            <p style="font-family:'Libre Baskerville',serif;font-weight:700;font-size:22px;line-height:1;margin:0;">{{ $etkinlik->baslangic_tarihi?->format('d') }}</p>
            <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:rgba(235,223,181,.65);margin:3px 0 0;">{{ $etkinlik->baslangic_tarihi?->translatedFormat('M') }}</p>
          </div>
          <div style="flex:1;">
            <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:14.5px;color:#162E4B;margin-bottom:8px;line-height:1.35;">{{ $etkinlik->baslik }}</h3>
            <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:12px;">
              <span style="font-size:12px;color:#62868D;display:flex;align-items:center;gap:5px;font-family:'Plus Jakarta Sans',sans-serif;">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                {{ $etkinlik->baslangic_tarihi?->format('H:i') }}@if($etkinlik->bitis_tarihi) - {{ $etkinlik->bitis_tarihi->format('H:i') }}@endif
              </span>
              @if($etkinlik->konum_ad)
                <span style="font-size:12px;color:#62868D;display:flex;align-items:center;gap:5px;font-family:'Plus Jakarta Sans',sans-serif;">
                  <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  {{ $etkinlik->konum_ad }}
                </span>
              @endif
            </div>
            <a href="{{ route('etkinlikler.show', $etkinlik->slug) }}" class="flex items-center gap-1 font-jakarta text-[12.5px] font-semibold text-accent transition-colors hover:text-orange-cta">
              Detay
              <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
            </a>
          </div>
        </div>
      @empty
        <p class="py-8 text-center font-jakarta text-sm text-teal-muted">Yaklaşan etkinlik yok.</p>
      @endforelse
    </div>
  </div>
</section>

<section class="quote-band py-20">
  <div class="relative z-[1] mx-auto max-w-[680px] px-6 text-center">
    <p class="mb-6 font-baskerville text-[clamp(20px,3vw,28px)] font-bold italic leading-[1.55] text-primary">
      "Bir çocuğa eğitim vermek,<br>ona sonsuz bir kapı açmaktır."
    </p>
    <p class="mb-7 font-jakarta text-[13px] font-semibold uppercase tracking-[0.06em] text-primary/40">- Kestanepazari Dernegi</p>
    <a href="{{ route('iletisim.index') }}" class="inline-flex items-center gap-2 rounded-[10px] bg-primary px-7 py-[13px] font-jakarta text-sm font-bold text-cream transition-colors hover:bg-primary-dark">
      <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Gönüllü Olun
    </a>
  </div>
</section>
@endsection

