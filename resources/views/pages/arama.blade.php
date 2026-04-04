@extends('layouts.app')

@section('title', $q ? '"' . $q . '" için Arama Sonuçları' : 'Arama')
@section('meta_description', $q ? '"' . $q . '" için ' . $toplamSonuc . ' sonuç bulundu.' : 'Sitede arama yapın.')
@section('robots', 'noindex, follow')

@section('schema')
@php
    $arama_schema = json_encode(
        [
            '@context' => 'https://schema.org',
            '@type' => 'SearchResultsPage',
            'name' => $q ? '"' . $q . '" için Arama Sonuçları' : 'Arama',
            'url' => url()->current() . ($q ? '?q=' . urlencode($q) : ''),
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
@endphp
<script type="application/ld+json">@php echo $arama_schema; @endphp</script>
@endsection

@section('content')
@php
    $aktifTip = request('tip', 'tumu');
    $gosterHaber = in_array($aktifTip, ['tumu', 'haber']);
    $gosterEtkinlik = in_array($aktifTip, ['tumu', 'etkinlik']);
    $gosterSayfa = in_array($aktifTip, ['tumu', 'sayfa']);
    $populerAramalar = ['burs', 'etkinlik', 'kurban', 'kayıt', 'zekat', 'mezun', 'yurt', 'hafızlık'];
@endphp

<section class="bg-bg-soft pb-20 pt-[126px]">
  <div class="mx-auto max-w-7xl px-6">
    <div class="mb-8">
      <form action="{{ route('arama.index') }}" method="GET" role="search">
        <div style="position:relative;">
          <svg style="position:absolute;left:18px;top:50%;transform:translateY(-50%);color:rgba(22,46,75,.4);pointer-events:none;" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
          </svg>
          <input
            id="main-search"
            type="search"
            name="q"
            value="{{ $q }}"
            class="search-big pl-14"
            placeholder="Ne aramak istiyorsunuz?"
            autocomplete="off"
            autofocus
          />
          <button type="submit" class="sr-only">Ara</button>

          @if($q)
            <a
              href="{{ route('arama.index') }}"
              style="position:absolute;right:16px;top:50%;transform:translateY(-50%);width:28px;height:28px;border-radius:999px;background:rgba(22,46,75,.1);display:flex;align-items:center;justify-content:center;color:rgba(22,46,75,.5);"
              class="transition-colors hover:bg-primary/15 hover:text-primary"
              aria-label="Aramayı temizle"
            >
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </a>
          @endif
        </div>
      </form>
    </div>

    <div class="grid gap-10 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
          <div id="filter-bar" class="flex flex-wrap gap-2">
            <a href="{{ route('arama.index', ['q' => $q, 'tip' => 'tumu']) }}" class="filter-pill {{ $aktifTip === 'tumu' ? 'active' : 'inactive' }}">Tümü</a>

            <a href="{{ route('arama.index', ['q' => $q, 'tip' => 'haber']) }}" class="filter-pill {{ $aktifTip === 'haber' ? 'active' : 'inactive' }}">
              <span style="display:flex;align-items:center;gap:5px;">
                <span style="width:7px;height:7px;border-radius:50%;background:#3B82F6;display:inline-block;"></span>
                Haberler
                <span style="border-radius:999px;background:rgba(59,130,246,.12);padding:2px 8px;font-size:11px;font-weight:700;color:#1D4ED8;">{{ $haberler->count() }}</span>
              </span>
            </a>

            <a href="{{ route('arama.index', ['q' => $q, 'tip' => 'etkinlik']) }}" class="filter-pill {{ $aktifTip === 'etkinlik' ? 'active' : 'inactive' }}">
              <span style="display:flex;align-items:center;gap:5px;">
                <span style="width:7px;height:7px;border-radius:50%;background:#FF9300;display:inline-block;"></span>
                Etkinlikler
                <span style="border-radius:999px;background:rgba(255,147,0,.12);padding:2px 8px;font-size:11px;font-weight:700;color:#C2410C;">{{ $etkinlikler->count() }}</span>
              </span>
            </a>

            <a href="{{ route('arama.index', ['q' => $q, 'tip' => 'sayfa']) }}" class="filter-pill {{ $aktifTip === 'sayfa' ? 'active' : 'inactive' }}">
              <span style="display:flex;align-items:center;gap:5px;">
                <span style="width:7px;height:7px;border-radius:50%;background:#28484C;display:inline-block;"></span>
                Sayfalar
                <span style="border-radius:999px;background:rgba(40,72,76,.12);padding:2px 8px;font-size:11px;font-weight:700;color:#166534;">{{ $sayfalar->count() }}</span>
              </span>
            </a>
          </div>

          <p class="font-jakarta text-[13.5px] text-teal-muted">
            <strong class="font-bold text-primary">{{ $toplamSonuc }}</strong> sonuç bulundu
            @if($q)
              <span class="font-semibold text-accent"> &quot;{{ $q }}&quot;</span> için
            @endif
          </p>
        </div>

        @if($toplamSonuc > 0)
          <div id="results-list" class="flex flex-col gap-2.5">
            @if($gosterHaber)
              @foreach($haberler as $haber)
                <a href="{{ route('haberler.show', $haber->slug) }}" class="result-card" data-tip="haber">
                  <div class="result-thumb" style="background:linear-gradient(135deg,#1e3a58,#0a1d30);">
                    @if($haber->gorsel_sm)
                      <img
                        src="{{ str_starts_with($haber->gorsel_sm, 'http') ? $haber->gorsel_sm : 'https://cdn.kestanepazari.org.tr/' . ltrim($haber->gorsel_sm, '/') }}"
                        alt="{{ $haber->baslik }}"
                        class="h-full w-full object-cover"
                        loading="lazy"
                        width="72"
                        height="72"
                      >
                    @else
                      <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.2)" stroke-width="1">
                        <rect x="3" y="3" width="18" height="18" rx="3" />
                        <circle cx="8.5" cy="8.5" r="1.5" />
                        <path d="M21 15l-5-5L5 21" />
                      </svg>
                    @endif
                  </div>

                  <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                      <span class="tip-badge" style="background:#EDF4FB;color:#1D4ED8;">
                        <span style="width:6px;height:6px;border-radius:50%;background:#3B82F6;display:inline-block;"></span>
                        Haber
                      </span>
                      @if($haber->kategori)
                        <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;">
                          {{ $haber->kategori->ad }}
                        </span>
                      @endif
                      <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;">
                        {{ $haber->yayin_tarihi?->format('d M Y') }}
                      </span>
                    </div>

                    <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:15px;color:#162E4B;margin-bottom:5px;line-height:1.35;">
                      {{ $haber->baslik }}
                    </h3>

                    @if($haber->ozet)
                      <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;color:#62868D;line-height:1.6;">
                        {{ \Illuminate\Support\Str::limit($haber->ozet, 120) }}
                      </p>
                    @endif
                  </div>

                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2.5" style="flex-shrink:0;margin-top:4px;">
                    <path stroke-linecap="round" d="M9 5l7 7-7 7" />
                  </svg>
                </a>
              @endforeach
            @endif

            @if($gosterEtkinlik)
              @foreach($etkinlikler as $etkinlik)
                <a href="{{ route('etkinlikler.show', $etkinlik->slug) }}" class="result-card" data-tip="etkinlik">
                  <div class="result-thumb" style="background:linear-gradient(135deg,#1a3a2e,#0d2419);">
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.2)" stroke-width="1">
                      <rect x="3" y="4" width="18" height="18" rx="2" />
                      <path d="M16 2v4M8 2v4M3 10h18" />
                    </svg>
                  </div>

                  <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                      <span class="tip-badge" style="background:#FFF3EE;color:#C2410C;">
                        <span style="width:6px;height:6px;border-radius:50%;background:#FF9300;display:inline-block;"></span>
                        Etkinlik
                      </span>
                      <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;">
                        {{ $etkinlik->baslangic_tarihi?->format('d M Y') }}
                      </span>
                    </div>

                    <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:15px;color:#162E4B;margin-bottom:5px;line-height:1.35;">
                      {{ $etkinlik->baslik }}
                    </h3>

                    @if($etkinlik->konum_ad || $etkinlik->ozet)
                      <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;color:#62868D;line-height:1.6;">
                        {{ $etkinlik->baslangic_tarihi?->format('H:i') }}
                        @if($etkinlik->konum_ad)
                          · {{ $etkinlik->konum_ad }}
                        @endif
                      </p>
                    @endif
                  </div>

                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2.5" style="flex-shrink:0;margin-top:4px;">
                    <path stroke-linecap="round" d="M9 5l7 7-7 7" />
                  </svg>
                </a>
              @endforeach
            @endif

            @if($gosterSayfa)
              @foreach($sayfalar as $sayfa)
                <a href="{{ route('kurumsal.show', $sayfa->slug) }}" class="result-card" data-tip="sayfa">
                  <div class="result-thumb" style="background:linear-gradient(135deg,#28484C,#1a2f31);">
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.2)" stroke-width="1">
                      <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>

                  <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                      <span class="tip-badge" style="background:#F0FDF4;color:#166534;">
                        <span style="width:6px;height:6px;border-radius:50%;background:#28484C;display:inline-block;"></span>
                        Sayfa
                      </span>
                    </div>

                    <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:15px;color:#162E4B;margin-bottom:5px;line-height:1.35;">
                      {{ $sayfa->ad }}
                    </h3>

                    @if($sayfa->ozet)
                      <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;color:#62868D;line-height:1.6;">
                        {{ \Illuminate\Support\Str::limit($sayfa->ozet, 120) }}
                      </p>
                    @endif
                  </div>

                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2.5" style="flex-shrink:0;margin-top:4px;">
                    <path stroke-linecap="round" d="M9 5l7 7-7 7" />
                  </svg>
                </a>
              @endforeach
            @endif
          </div>
        @endif

        @if(!$q)
          <div class="empty-state">
            <div class="mx-auto mb-5 flex h-[72px] w-[72px] items-center justify-center rounded-full border border-primary/10 bg-white">
              <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="1.5">
                <circle cx="11" cy="11" r="8" />
                <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
              </svg>
            </div>
            <h3 class="mb-2 font-baskerville text-[20px] font-bold text-primary">Aramak için yazmaya başlayın</h3>
            <p class="font-jakarta text-sm leading-[1.7] text-teal-muted">Haber, etkinlik veya sayfa aramak için yukarıdaki kutuyu kullanın.</p>
          </div>
        @elseif($toplamSonuc === 0)
          <div class="empty-state">
            <div class="mx-auto mb-5 flex h-[72px] w-[72px] items-center justify-center rounded-full border border-primary/10 bg-white">
              <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="1.5">
                <circle cx="11" cy="11" r="8" />
                <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
              </svg>
            </div>
            <h3 class="mb-2 font-baskerville text-[20px] font-bold text-primary">Sonuç bulunamadı</h3>
            <p class="mb-6 font-jakarta text-sm leading-[1.7] text-teal-muted">
              <strong>&quot;{{ $q }}&quot;</strong> için eşleşme bulunamadı.<br>
              Farklı anahtar kelimeler deneyebilirsiniz.
            </p>
            <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;">
              @foreach(['burs', 'etkinlik', 'kurban', 'kayıt', 'zekat', 'mezun'] as $tag)
                <a href="{{ route('arama.index', ['q' => $tag]) }}" class="popular-tag">{{ $tag }}</a>
              @endforeach
            </div>
          </div>
        @endif
      </div>

      <aside class="flex flex-col gap-4">
        <div class="sidebar-card">
          <h3 class="sidebar-title">Popüler Aramalar</h3>
          <div class="flex flex-wrap gap-2">
            @foreach($populerAramalar as $tag)
              <a href="{{ route('arama.index', ['q' => $tag]) }}" class="popular-tag">{{ $tag }}</a>
            @endforeach
          </div>
        </div>

        <div class="sidebar-card">
          <h3 class="sidebar-title">İçerik Türleri</h3>
          <div class="flex flex-col gap-2.5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span style="width:10px;height:10px;border-radius:50%;background:#3B82F6;display:inline-block;"></span>
                <span class="font-jakarta text-[13.5px] font-medium text-primary">Haberler</span>
              </div>
              <span class="rounded-full bg-[#EDF4FB] px-2.5 py-0.5 font-jakarta text-[13px] font-bold text-primary">{{ $haberler->count() }}</span>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span style="width:10px;height:10px;border-radius:50%;background:#FF9300;display:inline-block;"></span>
                <span class="font-jakarta text-[13.5px] font-medium text-primary">Etkinlikler</span>
              </div>
              <span class="rounded-full bg-[#FFF3EE] px-2.5 py-0.5 font-jakarta text-[13px] font-bold text-primary">{{ $etkinlikler->count() }}</span>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span style="width:10px;height:10px;border-radius:50%;background:#28484C;display:inline-block;"></span>
                <span class="font-jakarta text-[13.5px] font-medium text-primary">Sayfalar</span>
              </div>
              <span class="rounded-full bg-[#F0FDF4] px-2.5 py-0.5 font-jakarta text-[13px] font-bold text-primary">{{ $sayfalar->count() }}</span>
            </div>
          </div>
        </div>

        <div class="sidebar-card">
          <h3 class="sidebar-title">Hızlı Erişim</h3>
          <div class="flex flex-col gap-2">
            <a href="{{ route('ekayit.index') }}" class="flex items-center gap-2 rounded-lg bg-bg-soft px-3 py-2.5 transition-colors hover:bg-cream">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2">
                <path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
              <span class="font-jakarta text-[13.5px] font-semibold text-primary">E-Kayıt Formu</span>
            </a>

            <a href="{{ route('bagis.index') }}" class="flex items-center gap-2 rounded-lg bg-bg-soft px-3 py-2.5 transition-colors hover:bg-cream">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2">
                <path stroke-linecap="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
              <span class="font-jakarta text-[13.5px] font-semibold text-primary">Bağış Yap</span>
            </a>

            <a href="{{ route('etkinlikler.index') }}" class="flex items-center gap-2 rounded-lg bg-bg-soft px-3 py-2.5 transition-colors hover:bg-cream">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" />
                <path d="M16 2v4M8 2v4M3 10h18" />
              </svg>
              <span class="font-jakarta text-[13.5px] font-semibold text-primary">Etkinlik Takvimi</span>
            </a>

            <a href="{{ route('haberler.index') }}" class="flex items-center gap-2 rounded-lg bg-bg-soft px-3 py-2.5 transition-colors hover:bg-cream">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2">
                <path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
              </svg>
              <span class="font-jakarta text-[13.5px] font-semibold text-primary">Tüm Haberler</span>
            </a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>
@endsection
