@extends('layouts.app')

@php
    $etkinlikTipi = $etkinlik->tip?->value ?? $etkinlik->tip;
    $posterUrl = filled($etkinlik->gorsel_lg)
        ? (str_starts_with((string) $etkinlik->gorsel_lg, 'http')
            ? $etkinlik->gorsel_lg
            : $etkinlik->gorsel_lg_cdn_url)
        : null;
    $ogImage = filled($etkinlik->gorsel_og)
        ? (str_starts_with((string) $etkinlik->gorsel_og, 'http')
            ? $etkinlik->gorsel_og
            : 'https://cdn.kestanepazari.org.tr/'.ltrim($etkinlik->gorsel_og, '/'))
        : asset('img/og-default.jpg');

    $baslangicUtc = $etkinlik->baslangic_tarihi?->copy()?->utc();
    $bitisUtc = $etkinlik->bitis_tarihi?->copy()?->utc() ?? $baslangicUtc;
    $gcStart = $baslangicUtc?->format('Ymd\THis\Z');
    $gcEnd = $bitisUtc?->format('Ymd\THis\Z') ?? $gcStart;
    $googleCalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text='.urlencode($etkinlik->baslik).'&dates='.$gcStart.'/'.$gcEnd.'&location='.urlencode($etkinlik->konum_ad ?? '');
    $hicriTarih = null;
    if ($etkinlik->baslangic_tarihi && class_exists(\IntlDateFormatter::class)) {
        $hicriFormatter = new \IntlDateFormatter(
            'tr_TR@calendar=islamic-umalqura',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE,
            config('app.timezone', 'Europe/Istanbul'),
            \IntlDateFormatter::TRADITIONAL,
            'd MMMM y, EEEE'
        );

        $formatlananHicriTarih = $hicriFormatter->format($etkinlik->baslangic_tarihi);
        $hicriTarih = $formatlananHicriTarih !== false ? $formatlananHicriTarih : null;
    }
    $yolTarifiUrl = null;
    if ($etkinlik->konum_lat && $etkinlik->konum_lng) {
        $yolTarifiUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($etkinlik->konum_lat . ',' . $etkinlik->konum_lng);
    } elseif ($etkinlik->konum_ad || $etkinlik->konum_adres) {
        $yolTarifiUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode(trim(($etkinlik->konum_ad ?? '') . ' ' . ($etkinlik->konum_adres ?? '')));
    }

@endphp

@section('title', $etkinlik->seo_baslik ?? $etkinlik->baslik)
@section('meta_description', $etkinlik->meta_description ?? ($etkinlik->ozet ?? $etkinlik->baslik.' etkinlik detayları.'))
@section('robots', $etkinlik->robots ?? 'index, follow')
@section('og_image', $ogImage)
@section('og_type', 'event')
@section('canonical', route('etkinlikler.show', $etkinlik->slug))

@section('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Event",
  "name": @json($etkinlik->baslik),
  "startDate": @json($etkinlik->baslangic_tarihi?->toIso8601String()),
  "endDate": @json($etkinlik->bitis_tarihi?->toIso8601String()),
  "eventStatus": "https://schema.org/EventScheduled",
  "eventAttendanceMode": "{{ $etkinlikTipi === 'online' ? 'https://schema.org/OnlineEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode' }}",
  "location": {
    "@@type": "Place",
    "name": @json($etkinlik->konum_ad),
    "address": {
      "@@type": "PostalAddress",
      "streetAddress": @json($etkinlik->konum_adres),
      "addressLocality": @json($etkinlik->konum_ilce),
      "addressRegion": @json($etkinlik->konum_il),
      "addressCountry": "TR"
    }
  },
  "organizer": {"@@type":"Organization","name": @json(config('site.ad').' Derneği')},
  "image": @json($ogImage)
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "BreadcrumbList",
  "itemListElement": [
    {"@@type":"ListItem","position":1,"name":"Ana Sayfa","item":"{{ url('/') }}"},
    {"@@type":"ListItem","position":2,"name":"Etkinlikler","item":"{{ route('etkinlikler.index') }}"},
    {"@@type":"ListItem","position":3,"name": @json($etkinlik->baslik)}
  ]
}
</script>
@endsection

@section('content')
<div class="border-b border-primary/10 bg-white pb-5 pt-0">
    <div class="mx-auto max-w-7xl px-6 pt-6">
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('home') }}" class="font-jakarta text-[13px] text-teal-muted transition-colors hover:text-accent">Ana Sayfa</a>
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('etkinlikler.index') }}" class="font-jakarta text-[13px] text-teal-muted transition-colors hover:text-accent">Etkinlikler</a>
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
            <span class="font-jakarta text-[13px] font-medium text-primary">{{ $etkinlik->baslik }}</span>
        </div>
    </div>
</div>

<main class="mx-auto max-w-7xl px-6 pb-20 pt-10">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)] xl:gap-10">
        <div class="min-w-0 space-y-7">
            <section class="grid gap-8 md:grid-cols-2 md:items-start">
                <div class="poster-wrap">
                    @if($posterUrl)
                        <img src="{{ $posterUrl }}"
                             alt="{{ $etkinlik->baslik }}"
                             class="absolute inset-0 h-full w-full object-cover"
                             style="aspect-ratio:4/5;"
                             loading="eager"
                             fetchpriority="high"
                             width="1080"
                             height="1350">
                    @else
                        <div class="absolute inset-0 bg-[linear-gradient(160deg,#162E4B,#091420)]"></div>
                    @endif

                    <div class="absolute inset-0 bg-[linear-gradient(to_bottom,rgba(0,0,0,.28)_0%,transparent_45%,rgba(0,0,0,.68)_100%)]"></div>

                    <div class="absolute left-4 right-4 top-4 z-[2] flex items-center justify-between gap-3">
                        @if($etkinlik->baslangic_tarihi?->isPast())
                            <span class="rounded-full bg-gray-100 px-3 py-1 font-jakarta text-[11px] font-bold text-gray-600">Tamamlandı</span>
                        @elseif($etkinlik->baslangic_tarihi?->isCurrentMonth())
                            <span class="rounded-full bg-green-100 px-3 py-1 font-jakarta text-[11px] font-bold text-green-700">Bu Ay</span>
                        @else
                            <span class="rounded-full bg-blue-100 px-3 py-1 font-jakarta text-[11px] font-bold text-blue-700">Gelecek</span>
                        @endif

                        <span class="flex h-7 w-7 items-center justify-center rounded-[7px] border border-[#EBDFB5]/25 bg-[#EBDFB5]/15 font-baskerville text-[13px] font-bold text-[#EBDFB5]">K</span>
                    </div>
                </div>

                <div>
                    <h1 class="mb-4 font-baskerville text-[clamp(22px,2.8vw,30px)] font-bold leading-[1.25] text-primary">{{ $etkinlik->baslik }}</h1>

                    @if($etkinlik->ozet)
                        <p class="mb-5 font-jakarta text-[15px] leading-[1.75] text-teal-muted">{{ $etkinlik->ozet }}</p>
                    @endif

                    <div class="mb-5 flex flex-col gap-2.5">
                        <div class="meta-chip">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2" class="shrink-0"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <div>
                                <p class="font-jakarta text-[11px] font-medium text-teal-muted">Tarih</p>
                                <p class="font-jakarta text-sm font-semibold text-primary">{{ $etkinlik->baslangic_tarihi?->translatedFormat('d F Y, l') ?? '—' }}</p>
                                @if($hicriTarih)
                                    <p class="mt-1 font-jakarta text-[12px] italic text-teal-muted">{{ $hicriTarih }} (Hicri)</p>
                                @endif
                            </div>
                        </div>

                        <div class="meta-chip">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2" class="shrink-0"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <div>
                                <p class="font-jakarta text-[11px] font-medium text-teal-muted">Saat</p>
                                <p class="font-jakarta text-sm font-semibold text-primary">
                                    {{ $etkinlik->baslangic_tarihi?->format('H:i') ?? '—' }}
                                    @if($etkinlik->bitis_tarihi) — {{ $etkinlik->bitis_tarihi->format('H:i') }} @endif
                                </p>
                            </div>
                        </div>

                        @if($etkinlik->konum_ad)
                            <div class="meta-chip">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2" class="shrink-0"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div>
                                    <p class="font-jakarta text-[11px] font-medium text-teal-muted">Konum</p>
                                    <p class="font-jakarta text-sm font-semibold text-primary">
                                        <a href="#etkinlik-harita" class="underline hover:text-accent transition-colors" onclick="event.preventDefault(); document.getElementById('etkinlik-harita').scrollIntoView({behavior: 'smooth'});">{{ $etkinlik->konum_ad }}@if($etkinlik->konum_adres), {{ $etkinlik->konum_adres }} @endif</a>
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if($etkinlik->kontenjan)
                            <div class="meta-chip">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2" class="shrink-0"><path stroke-linecap="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div>
                                    <p class="font-jakarta text-[11px] font-medium text-teal-muted">Kontenjan</p>
                                    <p class="font-jakarta text-sm font-semibold text-primary">{{ $etkinlik->kontenjan }} kişi</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2.5">
                        <a href="{{ $googleCalUrl }}" target="_blank" rel="noopener" class="cal-btn">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            Google Takvim
                        </a>
                        <a href="{{ route('etkinlikler.takvim', $etkinlik->slug) }}" class="cal-btn">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><path stroke-linecap="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            iCal / Apple
                        </a>

                        @if($etkinlikTipi === 'online' && $etkinlik->online_link)
                            <a href="{{ $etkinlik->online_link }}" target="_blank" rel="noopener" class="cal-btn">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><path stroke-linecap="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Online Katılım
                            </a>
                        @endif
                    </div>

                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <h4 class="mb-3 text-sm font-semibold text-gray-900">Katılım Durumu</h4>

                        @if(auth('uye')->check())
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('etkinlikler.katilim.guncelle', $etkinlik->slug) }}">
                                    @csrf
                                    <input type="hidden" name="durum" value="katiliyorum">
                                    <button type="submit" class="cal-btn {{ $uyeKatilimDurumu === 'katiliyorum' ? 'google' : '' }}">Katılıyorum ({{ $katilimSayilari['katiliyorum'] ?? 0 }})</button>
                                </form>

                                <form method="POST" action="{{ route('etkinlikler.katilim.guncelle', $etkinlik->slug) }}">
                                    @csrf
                                    <input type="hidden" name="durum" value="katilmiyorum">
                                    <button type="submit" class="cal-btn {{ $uyeKatilimDurumu === 'katilmiyorum' ? 'apple' : '' }}">Katılmıyorum ({{ $katilimSayilari['katilmiyorum'] ?? 0 }})</button>
                                </form>

                                <form method="POST" action="{{ route('etkinlikler.katilim.guncelle', $etkinlik->slug) }}">
                                    @csrf
                                    <input type="hidden" name="durum" value="belirsiz">
                                    <button type="submit" class="cal-btn {{ $uyeKatilimDurumu === 'belirsiz' ? 'outlook' : '' }}">Belirsiz ({{ $katilimSayilari['belirsiz'] ?? 0 }})</button>
                                </form>
                            </div>
                        @else
                            <div class="rounded-xl border border-blue-100 bg-blue-50 p-3 text-sm text-blue-900">
                                Bu etkinlik için katılım durumunu belirtmek ister misiniz?
                                <a href="{{ route('uye.kayit.form') }}" class="ml-1 font-semibold text-blue-700 underline underline-offset-2">Üye olun</a>
                                veya
                                <a href="{{ route('uye.giris.form') }}" class="ml-1 font-semibold text-blue-700 underline underline-offset-2">giriş yapın</a>.
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-primary/8 bg-white p-6 sm:p-7">
                <h2 class="mb-4 border-b border-primary/8 pb-3 font-baskerville text-[19px] font-bold text-primary">Etkinlik Hakkında</h2>
                <div class="etkinlik-detay-icerik prose max-w-none">
                    {!! $etkinlik->aciklama ?: '<p>'.e($etkinlik->ozet ?? 'Etkinlik detayları yakında paylaşılacaktır.').'</p>' !!}
                </div>
            </section>

            @if($etkinlik->konum_lat && $etkinlik->konum_lng)
                <section id="etkinlik-harita" class="rounded-2xl border border-primary/8 bg-white p-6 sm:p-7">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-primary/8 pb-3">
                        <h2 class="font-baskerville text-[19px] font-bold text-primary">Konum</h2>
                        @if($yolTarifiUrl)
                            <a href="{{ $yolTarifiUrl }}" target="_blank" rel="noopener" class="cal-btn">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 01.553-.894L9 2m0 18l6-2m-6 2V2m6 16l5.447 2.724A1 1 0 0021 19.382V8.618a1 1 0 00-.553-.894L15 5m0 13V5m0 0L9 2"/></svg>
                                Yol Tarifi
                            </a>
                        @endif
                    </div>
                    <div class="map-frame">
                        <iframe
                            src="https://maps.google.com/maps?q={{ $etkinlik->konum_lat }},{{ $etkinlik->konum_lng }}&z=15&output=embed"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </section>
            @endif

            @if($etkinlik->gorseller->isNotEmpty())
                <section class="rounded-2xl border border-primary/8 bg-white p-6 sm:p-7">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="font-baskerville text-[18px] font-bold text-primary">Etkinlik Galerisi</h2>
                        <span class="rounded-full bg-bg-soft px-3 py-1 font-jakarta text-[12px] font-semibold text-teal-muted">{{ $etkinlik->gorseller->count() }} görsel</span>
                    </div>

                    <div class="galeri-grid">
                        @forelse($etkinlik->gorseller->sortBy('sira') as $i => $gorsel)
                            @php
                                $gorselLgUrl = filled($gorsel->lg_yol)
                                    ? (str_starts_with((string) $gorsel->lg_yol, 'http')
                                        ? $gorsel->lg_yol
                                        : 'https://cdn.kestanepazari.org.tr/'.ltrim($gorsel->lg_yol, '/'))
                                    : null;
                                $gorselSmUrl = filled($gorsel->sm_yol)
                                    ? (str_starts_with((string) $gorsel->sm_yol, 'http')
                                        ? $gorsel->sm_yol
                                        : 'https://cdn.kestanepazari.org.tr/'.ltrim($gorsel->sm_yol, '/'))
                                    : $gorselLgUrl;
                            @endphp

                            @if($gorselLgUrl)
                                <div class="galeri-item {{ $i === 0 ? 'big' : '' }}" onclick="openLightbox(@js($gorselLgUrl))">
                                    <img src="{{ $gorselSmUrl }}"
                                         alt="{{ $gorsel->alt_text ?? $etkinlik->baslik }}"
                                         class="w-full h-auto object-contain mx-auto"
                                         loading="lazy"
                                         style="max-height:340px;"
                                         width="auto"
                                         height="auto">
                                    <div class="galeri-overlay">
                                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path stroke-linecap="round" d="M15 3h6m0 0v6m0-6L10 14"/><path stroke-linecap="round" d="M5 5v14h14"/></svg>
                                    </div>
                                </div>
                            @endif
                        @empty
                        @endforelse
                    </div>
                </section>
            @endif
        </div>

        <aside class="sidebar-sticky etkinlik-detay-sidebar">
            @include('components.sidebar')
        </aside>
    </div>
</main>

<div id="lightbox" onclick="closeLightbox()">
    <button type="button" class="lightbox-close" onclick="event.stopPropagation(); closeLightbox()" aria-label="Kapat">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <img id="lightbox-img" src="" alt="Etkinlik görseli" onclick="event.stopPropagation()">
</div>
@endsection