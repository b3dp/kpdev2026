@php
    $icerikHtml = trim((string) $sayfa->icerik);
    $galeriGorselleri = $sayfa->gorseller;
    $bannerGorseli = $sayfa->bannerMasaustuUrl() ?: $sayfa->gorselLgUrl();
    $tumKurumsalGorsellerJson = $galeriGorselleri->map(fn($gorsel) => [
        'lg' => $gorsel->orijinalUrl(),
        'alt' => $gorsel->alt_text ?: $sayfa->ad,
    ])->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
@endphp

<section class="kurumsal-section-card">
    @if($galeriGorselleri->count())
        <div class="mb-4 flex justify-end">
            <a href="#foto-galeri" class="kurumsal-galeri-kisayol" aria-label="Foto galeriye git">
                <span>Foto galeri</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l-6-6m6 6l6-6"/>
                </svg>
            </a>
        </div>
    @endif

    @if($bannerGorseli)
        <div class="mb-6 overflow-hidden rounded-[20px] border border-[#162e4b]/10 bg-[#f1f5f9]">
            <div style="position:relative;width:100%;padding-top:56.25%;">
                <img
                    src="{{ $bannerGorseli }}"
                    alt="{{ $sayfa->ad }}"
                    loading="lazy"
                    style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"
                >
            </div>
        </div>
    @endif

    <div class="kurumsal-prose">
        @if($icerikHtml)
            {!! $icerikHtml !!}
        @else
            <p>{{ $sayfa->ozet ?: 'Bu sayfaya ait kurumsal içerik yakında güncellenecektir.' }}</p>
        @endif
    </div>

    @if($sayfa->video_embed_url)
        <div class="mt-6 overflow-hidden rounded-[20px] border border-[#162e4b]/10 bg-white shadow-sm">
            <iframe
                src="{{ $sayfa->video_embed_url }}"
                title="{{ $sayfa->ad }} videosu"
                class="aspect-video w-full"
                loading="lazy"
                allowfullscreen
            ></iframe>
        </div>
    @endif

    @if($galeriGorselleri->count())
        <div id="foto-galeri" class="mt-8 border-t border-primary/10 pt-8">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="kurumsal-eyebrow">Galeri</p>
                    <h3 class="kurumsal-section-title">Sayfa Galerisi</h3>
                </div>
                <span class="inline-flex items-center rounded-full bg-[#F4EFE5] px-4 py-2 font-jakarta text-[15px] font-medium text-teal-muted">
                    {{ $galeriGorselleri->count() }} görsel
                </span>
            </div>

            <div
                x-data="{
                    acik: false,
                    aktifIndex: 0,
                    gorseller: {{ $tumKurumsalGorsellerJson }},
                    ac(i) { this.aktifIndex = i; this.acik = true; document.body.style.overflow = 'hidden'; },
                    kapat() { this.acik = false; document.body.style.overflow = ''; },
                    onceki() { this.aktifIndex = (this.aktifIndex - 1 + this.gorseller.length) % this.gorseller.length; },
                    sonraki() { this.aktifIndex = (this.aktifIndex + 1) % this.gorseller.length; }
                }"
                @keydown.escape.window="kapat()"
                @keydown.arrow-left.window="if(acik) onceki()"
                @keydown.arrow-right.window="if(acik) sonraki()"
            >
                <div class="kurumsal-galeri-grid">
                    @foreach($galeriGorselleri as $index => $gorsel)
                        <button
                            type="button"
                            @click="ac({{ $index }})"
                            class="kurumsal-galeri-item"
                            aria-label="{{ $sayfa->ad }} görsel {{ $index + 1 }}"
                        >
                            <img
                                src="{{ $gorsel->orijinalUrl() }}"
                                alt="{{ $gorsel->alt_text ?: $sayfa->ad }}"
                                loading="lazy"
                                width="360"
                                height="240"
                                class="kurumsal-galeri-gorsel"
                            >
                        </button>
                    @endforeach
                </div>

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
</section>
