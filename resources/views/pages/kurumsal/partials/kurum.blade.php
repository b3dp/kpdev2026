@php
    $icerikHtml = trim((string) $sayfa->icerik);
    $galeriGorselleri = $sayfa->gorseller;
    $tumKurumsalGorsellerJson = $galeriGorselleri->map(fn($gorsel) => [
        'lg' => $gorsel->orijinalUrl(),
        'alt' => $gorsel->alt_text ?: $sayfa->ad,
    ])->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
@endphp

<section class="kurumsal-section-card">
    <div class="mb-5 flex items-start justify-between gap-3">
        <h2 class="kurumsal-section-title">{{ $sayfa->ad }}</h2>
        @if($galeriGorselleri->count())
            <a href="#foto-galeri" class="kurumsal-galeri-kisayol" aria-label="Foto galeriye git">
                <span>Foto galeri</span>
            </a>
        @endif
    </div>

    <div class="kurumsal-prose">
        @if($icerikHtml)
            {!! $icerikHtml !!}
        @else
            <p>{{ $sayfa->ozet ?: 'Bu kuruma ait içerik kısa süre içinde güncellenecektir.' }}</p>
        @endif
    </div>

    @if($galeriGorselleri->count())
        <div id="foto-galeri" class="mt-8 border-t border-primary/10 pt-8">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="kurumsal-eyebrow">Galeri</p>
                    <h3 class="kurumsal-section-title">Kurum Galerisi</h3>
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
                    ac(i) { this.aktifIndex = i; this.acik = true; const sw = window.innerWidth - document.documentElement.clientWidth; document.body.style.overflow = 'hidden'; document.body.style.paddingRight = sw + 'px'; },
                    kapat() { this.acik = false; document.body.style.overflow = ''; document.body.style.paddingRight = ''; },
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

@if($ilgiliHaberler->isNotEmpty())
    <section class="kurumsal-section-card">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <p class="kurumsal-eyebrow">Bağlantılı içerikler</p>
                <h2 class="kurumsal-section-title">İlgili haberler</h2>
            </div>
            <a href="{{ route('haberler.index') }}" class="text-sm font-semibold text-[#b27829] transition hover:text-[#e95925]">Tüm haberler</a>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($ilgiliHaberler as $haber)
                <a href="{{ route('haberler.show', $haber->slug) }}" class="kurumsal-list-card">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#62868d]">{{ $haber->kategori?->ad ?? 'Haber' }}</p>
                        <h3 class="mt-2 text-base font-bold text-[#162e4b]">{{ $haber->baslik }}</h3>
                        <p class="mt-2 text-sm leading-6 text-[#62868d]">{{ \Illuminate\Support\Str::limit(strip_tags((string) $haber->ozet), 120, '...') }}</p>
                    </div>
                    <span class="mt-4 inline-flex text-sm font-semibold text-[#b27829]">İncele</span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@if($yaklasanEtkinlikler->isNotEmpty())
    <section class="kurumsal-section-card">
        <p class="kurumsal-eyebrow">Takvim</p>
        <h2 class="kurumsal-section-title">Yaklaşan etkinlikler</h2>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($yaklasanEtkinlikler as $etkinlik)
                <a href="{{ route('etkinlikler.show', $etkinlik->slug) }}" class="kurumsal-list-card">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#62868d]">{{ $etkinlik->baslangic_tarihi?->translatedFormat('d M Y') }}</p>
                        <h3 class="mt-2 text-base font-bold text-[#162e4b]">{{ $etkinlik->baslik }}</h3>
                        <p class="mt-2 text-sm leading-6 text-[#62868d]">{{ $etkinlik->konum_ad ?: 'Konum bilgisi yakında paylaşılacaktır.' }}</p>
                    </div>
                    <span class="mt-4 inline-flex text-sm font-semibold text-[#b27829]">Detaya git</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
