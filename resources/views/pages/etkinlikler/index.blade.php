@extends('layouts.app')

@php
    $aktifFiltre = request('filtre', 'tumu');
    $ilkEtkinlik = $etkinlikler->first();
    $ogImage = filled($ilkEtkinlik?->gorsel_og)
        ? (str_starts_with((string) $ilkEtkinlik->gorsel_og, 'http')
            ? $ilkEtkinlik->gorsel_og
            : 'https://cdn.kestanepazari.org.tr/'.ltrim($ilkEtkinlik->gorsel_og, '/'))
        : asset('img/og-default.jpg');
@endphp

@section('title', 'Etkinlikler')
@section('meta_description', 'Kestanepazarı Derneği etkinlikleri — konserler, törenler, buluşmalar ve daha fazlası.')
@section('robots', 'index, follow')
@section('og_type', 'website')
@section('og_image', $ogImage)

@section('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "ItemList",
  "name": "Etkinlikler — Kestanepazarı",
  "url": "{{ route('etkinlikler.index') }}",
  "numberOfItems": {{ $etkinlikler->total() }},
  "itemListElement": [
    @foreach($etkinlikler as $i => $e)
    {
      "@@type": "ListItem",
      "position": {{ $i + 1 }},
      "name": @json($e->baslik),
      "url": @json(route('etkinlikler.show', $e->slug))
    }{{ !$loop->last ? ',' : '' }}
    @endforeach
  ]
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "BreadcrumbList",
  "itemListElement": [
    {"@@type":"ListItem","position":1,"name":"Ana Sayfa","item":"{{ url('/') }}"},
    {"@@type":"ListItem","position":2,"name":"Etkinlikler","item":"{{ route('etkinlikler.index') }}"}
  ]
}
</script>
@endsection

@section('content')
<section class="border-b border-primary/10 bg-white pt-0">
    <div class="mx-auto max-w-7xl px-6 pb-0 pt-7">
        <div class="mb-4 flex items-center gap-1.5">
            <a href="{{ route('home') }}" class="font-jakarta text-[13px] text-teal-muted transition-colors hover:text-accent">Ana Sayfa</a>
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
            <span class="font-jakarta text-[13px] font-medium text-primary">Etkinlikler</span>
        </div>

        <div class="pb-5">
            <p class="mb-2 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.1em] text-accent">Takvim</p>
            <h1 class="font-baskerville text-[clamp(24px,3vw,34px)] font-bold text-primary">Etkinlikler</h1>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pb-5">
            <div class="flex flex-wrap gap-2 overflow-x-auto">
                <a href="{{ route('etkinlikler.index', ['filtre' => 'tumu']) }}"
                   class="filter-pill {{ $aktifFiltre === 'tumu' ? 'active' : 'inactive' }}">Tümü</a>
                <a href="{{ route('etkinlikler.index', ['filtre' => 'bu-ay']) }}"
                   class="filter-pill {{ $aktifFiltre === 'bu-ay' ? 'active' : 'inactive' }}">Bu Ay</a>
                <a href="{{ route('etkinlikler.index', ['filtre' => 'gelecek']) }}"
                   class="filter-pill {{ $aktifFiltre === 'gelecek' ? 'active' : 'inactive' }}">Gelecek</a>
                <a href="{{ route('etkinlikler.index', ['filtre' => 'gecmis']) }}"
                   class="filter-pill {{ $aktifFiltre === 'gecmis' ? 'active' : 'inactive' }}">Geçmiş</a>
            </div>

            <div class="hidden items-center gap-2 sm:flex">
                <span class="view-btn active" aria-hidden="true">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
                </span>
            </div>
        </div>
    </div>
</section>

<main class="mx-auto max-w-7xl px-6 py-9">
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        @forelse($etkinlikler as $etkinlik)
            @php
                $gecmis = $etkinlik->baslangic_tarihi?->isPast();
                $gorselUrl = filled($etkinlik->gorsel_lg)
                    ? (str_starts_with((string) $etkinlik->gorsel_lg, 'http')
                        ? $etkinlik->gorsel_lg
                        : $etkinlik->gorsel_lg_cdn_url)
                    : null;
            @endphp
            <a href="{{ route('etkinlikler.show', $etkinlik->slug) }}"
               class="etk-yatay-kart {{ $gecmis ? 'opacity-70' : '' }}">

                {{-- SOL: Görsel --}}
                <div class="etk-yatay-foto">
                    @if($gorselUrl)
                        <img src="{{ $gorselUrl }}"
                             alt="{{ $etkinlik->baslik }}"
                             loading="lazy"
                             width="540"
                             height="720">
                    @else
                        <div class="absolute inset-0 bg-[linear-gradient(160deg,#162E4B,#091420)]"></div>
                    @endif

                    <div class="etk-yatay-overlay"></div>

                    <div class="etk-yatay-badges">
                        @if($gecmis)
                            <span class="rounded-full bg-black/30 px-2.5 py-1 font-jakarta text-[10.5px] font-bold text-white/70 backdrop-blur-sm">Tamamlandı</span>
                        @elseif($etkinlik->baslangic_tarihi?->isCurrentMonth())
                            <span class="rounded-full bg-green-500/80 px-2.5 py-1 font-jakarta text-[10.5px] font-bold text-white backdrop-blur-sm">Bu Ay</span>
                        @else
                            <span class="rounded-full bg-blue-500/70 px-2.5 py-1 font-jakarta text-[10.5px] font-bold text-white backdrop-blur-sm">Gelecek</span>
                        @endif

                        <span class="flex h-6 w-6 items-center justify-center rounded-[6px] border border-[#EBDFB5]/30 bg-[#EBDFB5]/20 font-baskerville text-[12px] font-bold text-[#EBDFB5] backdrop-blur-sm">K</span>
                    </div>
                </div>

                {{-- SAĞ: Bilgiler --}}
                <div class="etk-yatay-icerik">
                    <div>
                        @if($etkinlik->baslangic_tarihi)
                            <div class="etk-yatay-tarih">
                                <span class="etk-yatay-tarih-gun">{{ $etkinlik->baslangic_tarihi->format('d') }}</span>
                                <div class="etk-yatay-tarih-ay">
                                    <span>{{ $etkinlik->baslangic_tarihi->translatedFormat('M') }}</span>
                                    <span>{{ $etkinlik->baslangic_tarihi->format('Y') }}</span>
                                </div>
                            </div>
                        @endif

                        <h3 class="etk-yatay-baslik">{{ $etkinlik->baslik }}</h3>

                        <div class="etk-yatay-meta">
                            @if($etkinlik->baslangic_tarihi)
                                <span>
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                    {{ $etkinlik->baslangic_tarihi->format('H:i') }}@if($etkinlik->bitis_tarihi) — {{ $etkinlik->bitis_tarihi->format('H:i') }}@endif
                                </span>
                            @endif

                            @if($etkinlik->konum_ad)
                                <span>
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $etkinlik->konum_ad }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <span class="detay-link">Detay &amp; Kayıt →</span>
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-primary/10 bg-white px-6 py-16 text-center">
                <p class="font-jakarta text-sm text-teal-muted">Bu filtrede etkinlik bulunamadı.</p>
                <a href="{{ route('etkinlikler.index') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-[10px] border border-primary/15 bg-bg-soft px-4 py-2 font-jakarta text-sm font-semibold text-primary transition-colors hover:border-primary/30 hover:bg-cream">
                    Tümünü Göster
                </a>
            </div>
        @endforelse
    </div>

    @if($etkinlikler->hasPages())
        <div class="mt-12">
            {{ $etkinlikler->appends(request()->query())->links() }}
        </div>
    @endif
</main>
@endsection
