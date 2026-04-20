@extends('layouts.app')

@section('title', 'Haberler')
@section('meta_description', 'Kestanepazarı haberleri ve duyurular')
@section('robots', 'index, follow')
@section('og_type', 'website')
@section('og_image', $oneCikanHaber?->gorselLgUrl() ?: asset('img/og-default.jpg'))

@section('content')
<section class="border-b border-primary/10 bg-white pb-0 pt-0">
    <div class="mx-auto max-w-7xl px-6 pb-0 pt-8">
        <div class="mb-4 flex items-center gap-1.5">
            <a href="{{ route('home') }}" class="font-jakarta text-[13px] text-teal-muted transition-colors hover:text-accent">Ana Sayfa</a>
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
            <span class="font-jakarta text-[13px] font-medium text-primary">Haberler</span>
        </div>

        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="mb-2 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.1em] text-accent">Güncel Gelişmeler</p>
                <h1 class="font-baskerville text-[clamp(26px,3.5vw,38px)] font-bold leading-[1.2] text-primary">Haberler &amp; Duyurular</h1>
            </div>

            <form method="GET" action="{{ route('haberler.index') }}" class="relative w-full max-w-[260px]">
                @if($kategoriSlug)
                    <input type="hidden" name="kategori" value="{{ $kategoriSlug }}">
                @endif
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-primary/35" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input
                    type="search"
                    name="q"
                    value="{{ $arama }}"
                    placeholder="Haber ara..."
                    class="w-full rounded-[10px] border border-primary/15 bg-white py-[9px] pl-10 pr-3 font-jakarta text-[13.5px] text-primary outline-none transition-all focus:border-accent focus:ring-2 focus:ring-accent/10"
                >
            </form>
        </div>

        <div class="flex gap-2 overflow-x-auto pb-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <a
                href="{{ route('haberler.index', array_filter(['q' => $arama])) }}"
                class="whitespace-nowrap rounded-full border px-4 py-[7px] font-jakarta text-[13px] font-semibold transition-colors {{ !$kategoriSlug ? 'border-primary bg-primary text-cream' : 'border-primary/15 bg-white text-primary/65 hover:border-primary/30 hover:bg-bg-soft hover:text-primary' }}"
            >
                Tümü
            </a>
            @foreach($kategoriler as $kategori)
                <a
                    href="{{ route('haberler.index', array_filter(['kategori' => $kategori->slug, 'q' => $arama])) }}"
                    class="whitespace-nowrap rounded-full border px-4 py-[7px] font-jakarta text-[13px] font-semibold transition-colors {{ $kategoriSlug === $kategori->slug ? 'border-primary bg-primary text-cream' : 'border-primary/15 bg-white text-primary/65 hover:border-primary/30 hover:bg-bg-soft hover:text-primary' }}"
                >
                    {{ $kategori->ad }}
                </a>
            @endforeach
        </div>

        <div class="mt-0 h-[3px] rounded-none bg-primary/6">
            <div class="h-[3px] w-[60px] rounded-[2px] bg-accent"></div>
        </div>
    </div>
</section>

<main class="mx-auto max-w-7xl px-6 pb-20 pt-10">
    @if($oneCikanHaber)
        <a href="{{ route('haberler.show', $oneCikanHaber->slug) }}" class="mb-12 block overflow-hidden rounded-[18px] no-underline shadow-[0_1px_0_rgba(22,46,75,.06)] transition-shadow hover:shadow-[0_12px_40px_rgba(22,46,75,.18)]" style="height:420px; position:relative;">
            @if($oneCikanHaber->gorsel_sm)
                <img
                    src="{{ $oneCikanHaber->gorselSmUrl() }}"
                    alt="{{ $oneCikanHaber->baslik }}"
                    class="h-full w-full object-cover object-center"
                    loading="eager"
                    fetchpriority="high"
                    width="1280"
                    height="420"
                >
            @else
                <div class="flex h-full w-full items-center justify-center" style="background:linear-gradient(160deg,#1e3a58 0%,#0a1d30 100%);">
                    <svg width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.08)" stroke-width=".6"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                </div>
            @endif

            <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(8,16,28,.92) 0%, rgba(8,16,28,.4) 50%, transparent 100%);"></div>

            <div class="absolute left-5 top-5 flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-orange-cta px-3 py-[5px] font-jakarta text-[11.5px] font-bold text-white">
                    <svg width="10" height="10" fill="currentColor" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Öne Çıkan
                </span>
                @if($oneCikanHaber->kategori)
                    <span class="inline-block rounded-full px-3 py-[5px] font-jakarta text-[11.5px] font-bold text-white" style="background:{{ $oneCikanHaber->kategori->renk ?? '#3B82F6' }};">
                        {{ $oneCikanHaber->kategori->ad }}
                    </span>
                @endif
            </div>

            <div class="absolute inset-x-0 bottom-0 p-8">
                <h2 class="mb-3 max-w-[740px] font-baskerville text-[clamp(20px,2.5vw,30px)] font-bold leading-[1.3] text-white">{{ $oneCikanHaber->baslik }}</h2>
                @if($oneCikanHaber->ozet)
                    <p class="mb-4 max-w-[680px] font-jakarta text-[14.5px] leading-[1.65] text-white/65">{{ $oneCikanHaber->ozet }}</p>
                @endif
                <span class="flex items-center gap-1.5 font-jakarta text-[13px] text-white/55">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    {{ $oneCikanHaber->gosterim_tarihi?->translatedFormat('d F Y') }}
                </span>
            </div>
        </a>
    @endif

    @if($haberler->count())
        <div class="mb-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($haberler as $haber)
            <a href="{{ route('haberler.show', $haber->slug) }}" class="flex flex-col overflow-hidden rounded-2xl border border-primary/10 bg-white no-underline shadow-[0_1px_0_rgba(22,46,75,.05)] transition-all hover:-translate-y-[3px] hover:shadow-[0_8px_28px_rgba(22,46,75,.12)]">
                    <div class="relative h-[190px] overflow-hidden">
                        @if($haber->gorsel_sm)
                            <div
                                class="absolute inset-0"
                                style="background-image:url('{{ $haber->gorselSmUrl() }}'); background-position:center; background-repeat:no-repeat; background-size:cover; transform:scale(1.08); filter:blur(16px);"
                                aria-hidden="true"
                            ></div>
                            <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(8,16,28,.2) 0%, rgba(8,16,28,.08) 100%);" aria-hidden="true"></div>
                            <img
                                src="{{ $haber->gorselSmUrl() }}"
                                alt="{{ $haber->baslik }}"
                                class="relative z-10 h-full w-full"
                                style="object-fit:contain; object-position:center;"
                                loading="lazy"
                                width="420"
                                height="190"
                            >
                        @else
                            <div class="flex h-full w-full items-center justify-center" style="background:linear-gradient(160deg,#1a3d30 0%,#0c2018 100%);">
                                <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.1)" stroke-width=".7"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                        @endif

                        <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(8,16,28,.75) 0%, rgba(8,16,28,.1) 55%, transparent 100%);"></div>

                        @if($haber->kategori)
                            <div class="absolute bottom-0 left-0 right-0 p-3">
                                <span class="inline-block rounded-full px-[10px] py-[2px] font-jakarta text-[10.5px] font-bold text-white" style="background:{{ $haber->kategori->renk ?? '#FF9300' }};">
                                    {{ $haber->kategori->ad }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col p-[18px]">
                        <h3 class="mb-2 line-clamp-2 font-jakarta text-[15px] font-bold leading-[1.4] text-primary">{{ $haber->baslik }}</h3>
                        @if($haber->ozet)
                            <p class="mb-4 line-clamp-2 flex-1 font-jakarta text-[13px] leading-[1.6] text-teal-muted">{{ $haber->ozet }}</p>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 font-jakarta text-[12px] text-teal-muted">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                {{ $haber->gosterim_tarihi?->translatedFormat('d F Y') }}
                            </span>
                            <span class="font-jakarta text-[12px] font-semibold text-accent">2 dk →</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        @if($haberler->hasPages())
            @php
                $current = $haberler->currentPage();
                $last = $haberler->lastPage();
                $pages = [];

                if ($last <= 7) {
                    $pages = range(1, $last);
                } else {
                    $pages[] = 1;
                    if ($current > 3) {
                        $pages[] = '...';
                    }
                    for ($i = max(2, $current - 1); $i <= min($last - 1, $current + 1); $i++) {
                        $pages[] = $i;
                    }
                    if ($current < $last - 2) {
                        $pages[] = '...';
                    }
                    $pages[] = $last;
                }
            @endphp

            <div class="flex items-center justify-center gap-1.5">
                @if($haberler->onFirstPage())
                    <span class="flex h-9 items-center justify-center gap-1 rounded-lg border border-primary/15 px-3 font-jakarta text-[13px] font-semibold text-primary/35">Önceki</span>
                @else
                    <a href="{{ $haberler->previousPageUrl() }}" class="flex h-9 items-center justify-center gap-1 rounded-lg border border-primary/15 px-3 font-jakarta text-[13px] font-semibold text-primary/65 transition-colors hover:border-primary/30 hover:bg-bg-soft hover:text-primary">Önceki</a>
                @endif

                @foreach($pages as $page)
                    @if($page === '...')
                        <span class="flex h-9 w-9 items-center justify-center font-jakarta text-[13px] text-primary/40">…</span>
                    @elseif($page === $current)
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-primary bg-primary font-jakarta text-[13px] font-semibold text-cream">{{ $page }}</span>
                    @else
                        <a href="{{ $haberler->url($page) }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-primary/15 font-jakarta text-[13px] font-semibold text-primary/65 transition-colors hover:border-primary/30 hover:bg-bg-soft hover:text-primary">{{ $page }}</a>
                    @endif
                @endforeach

                @if($haberler->hasMorePages())
                    <a href="{{ $haberler->nextPageUrl() }}" class="flex h-9 items-center justify-center gap-1 rounded-lg border border-primary/15 px-3 font-jakarta text-[13px] font-semibold text-primary/65 transition-colors hover:border-primary/30 hover:bg-bg-soft hover:text-primary">Sonraki</a>
                @else
                    <span class="flex h-9 items-center justify-center gap-1 rounded-lg border border-primary/15 px-3 font-jakarta text-[13px] font-semibold text-primary/35">Sonraki</span>
                @endif
            </div>
        @endif
    @else
        <div class="rounded-2xl border border-primary/10 bg-white p-8 text-center font-jakarta text-sm text-teal-muted">
            Kriterlere uygun haber bulunamadı.
        </div>
    @endif
</main>
@endsection
