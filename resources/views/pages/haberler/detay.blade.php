@extends('layouts.app')

@php
    $kelimeSayisi = count(preg_split('/\s+/', trim(strip_tags($haber->icerik ?? '')), -1, PREG_SPLIT_NO_EMPTY));
    $okumaSuresi = max(1, (int) ceil($kelimeSayisi / 200));
    $tumGorsellerJson = $haber->gorseller->map(fn($g) => [
        'lg'  => $g->lgUrl(),
        'alt' => $g->alt_text ?: $haber->baslik,
    ])->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    $yayinTarihi = $haber->yayin_tarihi ?? $haber->created_at;
    $metaAciklama = html_entity_decode($haber->meta_description ?? $haber->ozet ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $robotsDeger = $haber->robots ?? 'index, follow';
    $kategoriAdlari = $haber->kategoriler->pluck('ad')->filter()->unique()->values();
    if ($robotsDeger && !str_contains($robotsDeger, 'follow') && !str_contains($robotsDeger, 'nofollow')) {
        $robotsDeger .= ', follow';
    }
@endphp

@section('og_type', 'article')
@section('og_image', $haber->gorselLgUrl() ?: $haber->gorselOgUrl() ?: asset('img/og-default.jpg'))

@section('title', $haber->seo_baslik ?? $haber->baslik)
@section('meta_description', $metaAciklama)
@section('robots', $robotsDeger)

@section('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "NewsArticle",
  "headline": "{{ $haber->baslik }}",
  "image": ["{{ $haber->gorselLgUrl() ?: $haber->gorselOgUrl() ?: asset('img/og-default.jpg') }}"],
  "datePublished": "{{ $yayinTarihi?->toIso8601String() }}",
  "dateModified": "{{ $haber->updated_at?->toIso8601String() }}",
  "description": "{{ $metaAciklama }}",
    "articleSection": @json($kategoriAdlari->first() ?: $haber->kategori?->ad),
    "keywords": @json($kategoriAdlari->all()),
  "wordCount": {{ $kelimeSayisi }},
  "timeRequired": "PT{{ $okumaSuresi }}M",
  "speakable": {
    "@@type": "SpeakableSpecification",
    "cssSelector": ["h1", ".haber-ozet"]
  },
  "author": {"@@type": "Organization", "name": "Kestanepazarı Derneği"},
  "publisher": {
    "@@type": "Organization",
    "name": "Kestanepazarı Derneği",
    "logo": {"@@type": "ImageObject", "url": "https://cdn.kestanepazari.org.tr/logo.png"}
  }
}
</script>
@endsection

@section('content')
    <style>
        .article-body { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; color: #2c3e50; line-height: 1.85; }
        .article-body p { margin-bottom: 20px; }
        .article-body h2 { font-family: 'Libre Baskerville', serif; font-size: 22px; color: #162E4B; margin: 32px 0 14px; font-weight: 700; }
        .article-body h3 { font-family: 'Libre Baskerville', serif; font-size: 18px; color: #162E4B; margin: 24px 0 10px; font-weight: 700; }
        .article-body blockquote {
            border-left: 3px solid #B27829;
            margin: 28px 0;
            padding: 16px 20px;
            background: rgba(178,120,41,.05);
            border-radius: 0 10px 10px 0;
            font-style: italic;
            color: #162E4B;
            font-size: 16.5px;
        }
        .article-body > p:first-of-type::first-letter {
            float: left;
            font-family: 'Libre Baskerville', serif;
            font-size: 68px;
            line-height: .78;
            font-weight: 700;
            color: #162E4B;
            margin: 6px 10px 0 0;
        }
        .tag-pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 13px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 600;
            border: 1.5px solid rgba(22,46,75,.15);
            color: rgba(22,46,75,.65);
            text-decoration: none;
            transition: background .15s, color .15s, border-color .15s;
        }
        .tag-pill:hover { background: #162E4B; color: #EBDFB5; border-color: #162E4B; }
        .share-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity .2s, transform .15s;
            cursor: pointer;
            border: none;
        }
        .share-btn:hover { opacity: .88; transform: translateY(-1px); }
        .rel-card {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(22,46,75,.07);
            transition: box-shadow .2s, transform .2s;
            text-decoration: none;
            display: flex;
            flex-direction: column;
        }
        .rel-card:hover { box-shadow: 0 6px 24px rgba(22,46,75,.12); transform: translateY(-2px); }
        .rel-foto { height: 160px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
        .rel-overlay { position: absolute; inset: 0; background: linear-gradient(to top,rgba(8,16,28,.7) 0%,transparent 60%); }
        .haber-detay-sidebar > div { max-width: none; margin: 0; padding: 0; }
        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }
        .galeri-item {
            position: relative;
            overflow: hidden;
            border-radius: 22px;
            background: linear-gradient(160deg,#1e3a58 0%,#0a1d30 100%);
            min-height: 220px;
        }
        .galeri-item--buyuk {
            grid-column: span 2;
            min-height: 375px;
        }
        .galeri-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .25s;
        }
        .galeri-item:hover img { transform: scale(1.03); }
        .galeri-ek-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(9,20,32,.72);
            color: #EBDFB5;
        }
        @media (max-width: 767px) {
            .galeri-grid { grid-template-columns: 1fr; }
            .galeri-item,
            .galeri-item--buyuk { grid-column: span 1; min-height: 220px; }
        }
        /* Okuma progress bar */
        #okuma-cubugu {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #B27829 0%, #162E4B 100%);
            z-index: 9999;
            transition: width .08s linear;
            pointer-events: none;
            border-radius: 0 2px 2px 0;
        }
        /* Galeri lightbox */
        .lightbox {
            position: fixed;
            inset: 0;
            z-index: 9990;
            background: rgba(5, 10, 18, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(6px);
        }
        .lightbox-kapat {
            position: absolute;
            top: 18px;
            right: 18px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,.12);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            transition: background .15s;
        }
        .lightbox-kapat:hover { background: rgba(255,255,255,.24); }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,.12);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s;
            z-index: 1;
        }
        .lightbox-nav:hover { background: rgba(255,255,255,.24); }
        .lightbox-nav--sol { left: 18px; }
        .lightbox-nav--sag { right: 18px; }
        .lightbox-icerik {
            max-width: min(90vw, 1200px);
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .lightbox-gorsel {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,.5);
        }
        .lightbox-alt {
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: rgba(255,255,255,.75);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13.5px;
            width: 100%;
        }
        .lightbox-sayac {
            white-space: nowrap;
            color: rgba(255,255,255,.5);
            font-size: 13px;
        }
        @media (max-width: 640px) {
            .lightbox-nav--sol { left: 8px; }
            .lightbox-nav--sag { right: 8px; }
        }
    </style>

    <div class="border-b border-primary/10 bg-white pb-5 pt-0">
        <div class="mx-auto max-w-7xl px-6 pt-6">
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('home') }}" class="font-jakarta text-[13px] text-teal-muted transition-colors hover:text-accent">Ana Sayfa</a>
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('haberler.index') }}" class="font-jakarta text-[13px] text-teal-muted transition-colors hover:text-accent">Haberler</a>
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
                <span class="font-jakarta text-[13px] font-medium text-primary">{{ $haber->baslik }}</span>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 pb-20 pt-10">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-start xl:gap-10">
            <div class="min-w-0 flex-1">
                <div class="mb-6">
                    @if($haber->kategoriler->isNotEmpty())
                        <div class="mb-3 flex flex-wrap gap-2">
                            @foreach($haber->kategoriler as $kategori)
                                <span class="inline-block rounded-full px-3 py-1 font-jakarta text-xs font-bold text-white" style="background:{{ $kategori->renk ?? '#3B82F6' }};">{{ $kategori->ad }}</span>
                            @endforeach
                        </div>
                    @elseif($haber->kategori)
                        <span class="mb-3 inline-block rounded-full px-3 py-1 font-jakarta text-xs font-bold text-white" style="background:{{ $haber->kategori->renk ?? '#3B82F6' }};">{{ $haber->kategori->ad }}</span>
                    @endif

                    <h1 class="mb-5 font-baskerville text-[clamp(24px,3vw,44px)] font-bold leading-[1.2] text-primary">{{ $haber->baslik }}</h1>

                    <div class="flex flex-wrap items-center gap-4 border-b border-t border-primary/10 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-[38px] w-[38px] items-center justify-center rounded-full bg-[linear-gradient(135deg,#162E4B,#28484C)] font-baskerville text-sm font-bold text-cream">K</div>
                            <div>
                                <p class="m-0 font-jakarta text-[13px] font-semibold text-primary">Kestanepazarı Derneği</p>
                                <p class="m-0 font-jakarta text-xs text-teal-muted">Haber Yayını</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 font-jakarta text-[13px] text-teal-muted">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            {{ $yayinTarihi?->translatedFormat('d F Y') }}
                        </div>
                        <div class="flex items-center gap-1.5 font-jakarta text-[13px] text-teal-muted">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            {{ $okumaSuresi }} dk okuma
                        </div>
                        <div class="flex items-center gap-1.5 font-jakarta text-[13px] text-teal-muted">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            {{ number_format((int) $haber->goruntuleme, 0, ',', '.') }} görüntülenme
                        </div>
                    </div>
                </div>

                @if($haber->ozet)
                    <div class="haber-ozet mb-7 rounded-xl border-l-[3px] border-accent bg-[#F9F6EF] px-6 py-4">
                        <p class="mb-1.5 font-jakarta text-[11px] font-bold uppercase tracking-[0.12em] text-accent">Özet</p>
                        <p class="m-0 font-jakarta text-[15px] leading-relaxed text-primary/80">{{ $haber->ozet }}</p>
                    </div>
                @endif

                <div class="relative mb-8 flex items-center justify-center overflow-hidden rounded-2xl bg-[linear-gradient(160deg,#1e3a58_0%,#0a1d30_100%)]" style="min-height:200px;">
                    @if($haber->gorsel_lg)
                        <img
                            src="{{ $haber->gorselLgUrl() }}"
                            alt="{{ $haber->baslik }}"
                            class="w-full block"
                            loading="eager"
                            fetchpriority="high"
                        >
                    @else
                        <svg width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.1)" stroke-width=".6"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    @endif
                    <div class="absolute bottom-4 right-4 rounded-md bg-black/50 px-2.5 py-1 font-jakarta text-[11px] text-white backdrop-blur">Fotoğraf: Kestanepazarı Derneği Arşivi</div>
                </div>

                @php
                    $icerikHtml = trim((string) $haber->icerik);
                @endphp

                @if($icerikHtml !== '')
                    <div class="article-body">
                        {!! $icerikHtml !!}
                    </div>
                @elseif($haber->ozet)
                    <div class="article-body">
                        <p>{{ $haber->ozet }}</p>
                    </div>
                @endif

                @if($haber->gorseller->count())
                    @php
                        $galeriGorselleri = $haber->gorseller->take(4)->values();
                        $kalanGorselSayisi = max($haber->gorseller->count() - 4, 0);
                    @endphp

                    <div class="mt-14 border-t border-primary/10 pt-8">
                        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="font-baskerville text-[clamp(24px,3vw,32px)] font-bold text-primary">Haber Galerisi</h2>
                                <p class="mt-1 font-jakarta text-[15px] leading-[1.6] text-teal-muted">Habere ait fotoğraf kareleri</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-[#F4EFE5] px-4 py-2 font-jakarta text-[15px] font-medium text-teal-muted">
                                {{ $haber->gorseller->count() }} fotoğraf
                            </span>
                        </div>

                        <div
                            x-data="{
                                acik: false,
                                aktifIndex: 0,
                                gorseller: {{ $tumGorsellerJson }},
                                ac(i) { this.aktifIndex = i; this.acik = true; document.body.style.overflow = 'hidden'; },
                                kapat() { this.acik = false; document.body.style.overflow = ''; },
                                onceki() { this.aktifIndex = (this.aktifIndex - 1 + this.gorseller.length) % this.gorseller.length; },
                                sonraki() { this.aktifIndex = (this.aktifIndex + 1) % this.gorseller.length; }
                            }"
                            @keydown.escape.window="kapat()"
                            @keydown.arrow-left.window="if(acik) onceki()"
                            @keydown.arrow-right.window="if(acik) sonraki()"
                        >
                            <div class="galeri-grid">
                                @foreach($galeriGorselleri as $index => $gorsel)
                                    <button
                                        type="button"
                                        @click="ac({{ $index }})"
                                        class="galeri-item {{ $index === 0 ? 'galeri-item--buyuk' : '' }}"
                                        aria-label="{{ $haber->baslik }} fotoğraf {{ $index + 1 }}"
                                    >
                                        <img
                                            src="{{ $index === 0 ? $gorsel->lgUrl() : ($gorsel->smUrl() ?: $gorsel->lgUrl()) }}"
                                            alt="{{ $gorsel->alt_text ?: $haber->baslik }}"
                                            loading="lazy"
                                            width="{{ $index === 0 ? '820' : '360' }}"
                                            height="{{ $index === 0 ? '375' : '220' }}"
                                            class="w-full"
                                            style="object-fit: {{ $index === 0 ? 'contain' : 'cover' }}; height: {{ $index === 0 ? 'auto' : '100%' }}; max-height: {{ $index === 0 ? '375px' : '220px' }}; background: #fff;"
                                        >

                                        @if($index === 3 && $kalanGorselSayisi > 0)
                                            <div class="galeri-ek-overlay">
                                                <span class="font-baskerville text-[44px] font-bold leading-none">+{{ $kalanGorselSayisi }}</span>
                                                <span class="mt-2 font-jakarta text-[15px] font-medium">fotoğraf daha</span>
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>

                            {{-- Lightbox overlay --}}
                            <div
                                x-show="acik"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="lightbox"
                                style="display:none;"
                                role="dialog"
                                aria-modal="true"
                                @click.self="kapat()"
                            >
                                <button type="button" @click="kapat()" class="lightbox-kapat" aria-label="Kapat">
                                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>

                                <button type="button" @click.stop="onceki()" class="lightbox-nav lightbox-nav--sol" aria-label="Önceki" x-show="gorseller.length > 1">
                                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
                                </button>

                                <div class="lightbox-icerik" @click.stop>
                                    <img :src="gorseller[aktifIndex]?.lg" :alt="gorseller[aktifIndex]?.alt" class="lightbox-gorsel">
                                    <div class="lightbox-alt">
                                        <span x-text="gorseller[aktifIndex]?.alt" class="truncate"></span>
                                        <span x-show="gorseller.length > 1" class="lightbox-sayac" x-text="(aktifIndex + 1) + ' / ' + gorseller.length"></span>
                                    </div>
                                </div>

                                <button type="button" @click.stop="sonraki()" class="lightbox-nav lightbox-nav--sag" aria-label="Sonraki" x-show="gorseller.length > 1">
                                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                @if($haber->etiketler->count())
                    <div class="mt-8 border-t border-primary/10 pt-6">
                        <p class="mb-2.5 font-jakarta text-[13px] font-semibold uppercase tracking-[0.04em] text-teal-muted">Etiketler</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($haber->etiketler as $etiket)
                                <a href="{{ route('haberler.index', ['q' => $etiket->ad]) }}" class="tag-pill">{{ $etiket->ad }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($haber->onaylanmisKisiler->count() || $haber->onaylanmisKurumlar->count())
                    <div class="mt-5 border-t border-primary/10 pt-5">
                        <p class="mb-2.5 font-jakarta text-[13px] font-semibold uppercase tracking-[0.04em] text-teal-muted">Bu Haberde</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($haber->onaylanmisKisiler as $kisi)
                                <a
                                    href="{{ route('haberler.index', ['kisi_id' => $kisi->id]) }}"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-primary/15 bg-white px-3 py-[5px] font-jakarta text-[12.5px] font-semibold text-primary transition-colors hover:border-primary hover:bg-primary hover:text-cream"
                                >
                                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                    {{ $kisi->full_ad ?: trim(($kisi->ad ?? '') . ' ' . ($kisi->soyad ?? '')) ?: 'İsimsiz kişi' }}
                                </a>
                            @endforeach
                            @foreach($haber->onaylanmisKurumlar as $kurum)
                                <a
                                    href="{{ route('haberler.index', ['kurum' => $kurum->slug]) }}"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-accent/30 bg-[#FBF6ED] px-3 py-[5px] font-jakarta text-[12.5px] font-semibold text-accent transition-colors hover:border-accent hover:bg-accent hover:text-white"
                                >
                                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path stroke-linecap="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                                    {{ $kurum->ad }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-7 border-t border-primary/10 pt-6">
                    <p class="mb-3 font-jakarta text-[13px] font-semibold uppercase tracking-[0.04em] text-teal-muted">Paylaş</p>
                    <div class="flex flex-wrap gap-2.5">
                        <a
                            href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                            target="_blank"
                            rel="noopener"
                            class="share-btn bg-[#1877F2] text-white"
                        >
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                            Facebook'ta Paylaş
                        </a>
                        <a
                            href="https://x.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($haber->baslik) }}"
                            target="_blank"
                            rel="noopener"
                            class="share-btn bg-black text-white"
                        >
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            X'te Paylaş
                        </a>
                        <a
                            href="https://wa.me/?text={{ urlencode($haber->baslik.' '.url()->current()) }}"
                            target="_blank"
                            rel="noopener"
                            class="share-btn bg-[#25D366] text-white"
                        >
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.145.565 4.16 1.552 5.9L.057 23.928l6.204-1.628A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.793 9.793 0 01-5.002-1.374l-.36-.213-3.682.966.982-3.589-.233-.37A9.79 9.79 0 012.182 12C2.182 6.58 6.58 2.182 12 2.182S21.818 6.58 21.818 12 17.42 21.818 12 21.818z"/></svg>
                            WhatsApp
                        </a>
                        <button
                            type="button"
                            class="share-btn border border-primary/15 bg-bg-soft text-primary"
                            onclick="navigator.clipboard.writeText(window.location.href)"
                        >
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Linki Kopyala
                        </button>
                    </div>
                </div>

                @if($ilgiliHaberler->count())
                    <div class="mt-14">
                        <h2 class="mb-6 border-b border-primary/10 pb-2.5 font-baskerville text-[22px] font-bold text-primary">İlgili Haberler</h2>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                            @foreach($ilgiliHaberler as $ilgili)
                                <a href="{{ route('haberler.show', $ilgili->slug) }}" class="rel-card">
                                    <div class="rel-foto" style="background:linear-gradient(160deg,#1a3d30 0%,#0c2018 100%);">
                                        @if($ilgili->gorsel_sm)
                                            <img
                                                src="{{ $ilgili->gorselSmUrl() }}"
                                                alt="{{ $ilgili->baslik }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                                width="320"
                                                height="160"
                                            >
                                        @else
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.1)" stroke-width=".7"><rect x="3" y="3" width="18" height="18" rx="3"/></svg>
                                        @endif
                                        <div class="rel-overlay"></div>
                                        @if($ilgili->kategori)
                                            <div class="absolute bottom-2.5 left-2.5">
                                                <span class="inline-block rounded-full px-2.5 py-0.5 font-jakarta text-[10px] font-bold text-white" style="background:{{ $ilgili->kategori->renk ?? '#FF9300' }};">{{ $ilgili->kategori->ad }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="p-3.5">
                                        <h3 class="mb-1.5 line-clamp-2 font-jakarta text-[13.5px] font-bold leading-[1.4] text-primary">{{ $ilgili->baslik }}</h3>
                                        <span class="font-jakarta text-xs text-teal-muted">{{ ($ilgili->yayin_tarihi ?? $ilgili->created_at)?->translatedFormat('d F Y') }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside class="haber-detay-sidebar w-full lg:sticky lg:w-80 lg:flex-none lg:self-start" style="top: 132px;">
                @include('components.sidebar')
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const cubuk = document.getElementById('okuma-cubugu');
    if (!cubuk) return;
    window.addEventListener('scroll', function () {
        const toplam = document.documentElement.scrollHeight - window.innerHeight;
        cubuk.style.width = toplam > 0 ? (window.scrollY / toplam * 100) + '%' : '100%';
    }, { passive: true });
})();
</script>
@endpush