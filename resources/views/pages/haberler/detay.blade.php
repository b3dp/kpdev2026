@extends('layouts.app')

@section('title', $haber->seo_baslik ?? $haber->baslik)
@section('meta_description', $haber->meta_description ?? $haber->ozet)
@section('robots', $haber->robots ?? 'index, follow')

@section('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
    "@@type": "NewsArticle",
  "headline": "{{ $haber->baslik }}",
  "image": "{{ $haber->gorsel_og ? 'https://cdn.kestanepazari.org.tr/'.$haber->gorsel_og : asset('img/og-default.jpg') }}",
  "datePublished": "{{ $haber->yayin_tarihi?->toIso8601String() }}",
  "dateModified": "{{ $haber->updated_at?->toIso8601String() }}",
  "description": "{{ $haber->meta_description ?? $haber->ozet }}",
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
    </style>

    <div class="border-b border-primary/10 bg-white pb-5 pt-[102px]">
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
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="mb-6">
                    @if($haber->kategori)
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
                            {{ $haber->yayin_tarihi?->translatedFormat('d F Y') }}
                        </div>
                        <div class="flex items-center gap-1.5 font-jakarta text-[13px] text-teal-muted">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            3 dk okuma
                        </div>
                        <div class="flex items-center gap-1.5 font-jakarta text-[13px] text-teal-muted">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            {{ number_format((int) $haber->goruntuleme, 0, ',', '.') }} görüntülenme
                        </div>
                    </div>
                </div>

                <div class="relative mb-8 flex h-[400px] items-center justify-center overflow-hidden rounded-2xl bg-[linear-gradient(160deg,#1e3a58_0%,#0a1d30_100%)]">
                    @if($haber->gorsel_lg)
                        <img
                            src="https://cdn.kestanepazari.org.tr/{{ $haber->gorsel_lg }}"
                            alt="{{ $haber->baslik }}"
                            class="h-full w-full object-cover"
                            loading="eager"
                            fetchpriority="high"
                            width="920"
                            height="400"
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
                                                src="https://cdn.kestanepazari.org.tr/{{ $ilgili->gorsel_sm }}"
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
                                        <span class="font-jakarta text-xs text-teal-muted">{{ $ilgili->yayin_tarihi?->translatedFormat('d F Y') }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside>
                @include('components.sidebar')
            </aside>
        </div>
    </div>
@endsection