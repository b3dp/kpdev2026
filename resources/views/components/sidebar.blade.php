@php
    $aktifKategori = request('kategori');
@endphp

<div class="mx-auto max-w-xs space-y-5 px-4 py-10">
    <div class="bagis-box">
        <div class="mb-3.5 flex h-10 w-10 items-center justify-center rounded-[10px] bg-accent/20">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2">
                <path stroke-linecap="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
        </div>

        <h3 class="relative z-[1] mb-2 font-baskerville text-[17px] font-bold leading-[1.3] text-cream">
            Bir Öğrencinin Geleceğine Ortak Ol
        </h3>

        <p class="relative z-[1] mb-[18px] font-jakarta text-[13px] leading-[1.65] text-cream/60">
            Bağışlarınız doğrudan öğrencilerin burs, kırtasiye ve barınma ihtiyaçlarına aktarılmaktadır.
        </p>

        <a
            href="{{ route('bagis.index') }}"
            class="relative z-[1] flex items-center justify-center gap-2 rounded-lg bg-orange-cta px-4 py-[11px] font-jakarta text-[13px] font-bold text-white shadow-[0_3px_10px_rgba(233,89,37,0.35)] transition-colors hover:bg-[#c94620]"
        >
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            Bağış Yap
        </a>
    </div>

    <div class="sidebar-card">
        <h3 class="sidebar-section-title">Son Haberler</h3>

        <div class="mt-4">
            @forelse($sonHaberler as $haber)
                <a href="{{ route('haberler.show', $haber->slug) }}" class="news-card">
                    <div class="news-thumb">
                        @if($haber->gorsel_sm)
                            <img
                                src="{{ $haber->gorselSmUrl() }}"
                                alt="{{ $haber->baslik }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                                width="64"
                                height="64"
                            >
                        @else
                            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,0.25)" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="3"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <path stroke-linecap="round" d="M21 15l-5-5L5 21"/>
                            </svg>
                        @endif
                    </div>

                    <div style="flex:1; min-width:0;">
                        <p class="news-title" style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:600; font-size:13px; color:#162E4B; margin:0 0 5px; line-height:1.4; transition:color .2s;">
                            {{ $haber->baslik }}
                        </p>
                        <span style="font-family:'Plus Jakarta Sans',sans-serif; font-size:11.5px; color:rgba(22,46,75,0.4); display:flex; align-items:center; gap:4px;">
                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            {{ $haber->gosterim_tarihi?->translatedFormat('d F Y') }}
                        </span>
                    </div>
                </a>
            @empty
                <p class="py-4 text-center font-jakarta text-sm text-teal-muted">Henüz haber yok.</p>
            @endforelse
        </div>

        <a href="{{ route('haberler.index') }}" class="mt-3.5 flex items-center justify-center gap-1.5 rounded-lg border border-primary/15 p-[9px] font-jakarta text-[13px] font-semibold text-primary transition-colors hover:border-primary/25 hover:bg-bg-soft">
            Tüm Haberler
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    <div class="sidebar-card">
        <h3 class="sidebar-section-title">Kategoriler</h3>

        <div class="mt-4 flex flex-wrap gap-[7px]">
            <a href="{{ route('haberler.index') }}" class="cat-pill {{ !$aktifKategori ? 'active' : 'inactive' }}">
                Tümü
            </a>

            @foreach($kategoriler as $kategori)
                <a
                    href="{{ route('haberler.index', ['kategori' => $kategori->slug]) }}"
                    class="cat-pill {{ $aktifKategori === $kategori->slug ? 'active' : 'inactive' }}"
                    style="{{ $aktifKategori !== $kategori->slug && $kategori->renk ? 'border-left: 3px solid '.$kategori->renk : '' }}"
                >
                    {{ $kategori->ad }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="sidebar-card">
        <h3 class="sidebar-section-title">Yaklaşan Etkinlik</h3>

        <div class="mt-4 flex flex-col gap-2.5">
            @forelse($yaklasanEtkinlikler as $etkinlik)
                <div class="event-box">
                    <div style="display:flex; gap:12px; align-items:flex-start;">
                        <div class="event-date-badge">
                            <p style="font-family:'Libre Baskerville',serif; font-weight:700; font-size:20px; line-height:1; margin:0; color:#EBDFB5;">
                                {{ $etkinlik->baslangic_tarihi?->format('d') }}
                            </p>
                            <p style="font-family:'Plus Jakarta Sans',sans-serif; font-size:10px; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:rgba(235,223,181,0.65); margin:2px 0 0;">
                                {{ $etkinlik->baslangic_tarihi?->translatedFormat('M') }}
                            </p>
                        </div>

                        <div style="flex:1; min-width:0;">
                            <p style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:13.5px; color:#162E4B; margin:0 0 5px; line-height:1.35;">
                                {{ $etkinlik->baslik }}
                            </p>
                            <div style="display:flex; flex-direction:column; gap:3px;">
                                <span style="font-size:12px; color:rgba(22,46,75,0.5); display:flex; align-items:center; gap:5px;">
                                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                    {{ $etkinlik->baslangic_tarihi?->format('H:i') }}
                                    @if($etkinlik->bitis_tarihi) - {{ $etkinlik->bitis_tarihi->format('H:i') }} @endif
                                </span>
                                @if($etkinlik->konum_ad)
                                    <span style="font-size:12px; color:rgba(22,46,75,0.5); display:flex; align-items:center; gap:5px;">
                                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ $etkinlik->konum_ad }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('etkinlikler.show', $etkinlik->slug) }}" class="mt-3 inline-flex items-center gap-1 font-jakarta text-[12.5px] font-semibold text-accent transition-colors hover:text-orange-cta">
                        Detaylar
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            @empty
                <p class="py-4 text-center font-jakarta text-sm text-teal-muted">Yaklaşan etkinlik yok.</p>
            @endforelse
        </div>

        <a href="{{ route('etkinlikler.index') }}" class="mt-3.5 flex items-center justify-center gap-1.5 rounded-lg border border-primary/15 p-[9px] font-jakarta text-[13px] font-semibold text-primary transition-colors hover:border-primary/25 hover:bg-bg-soft">
            Tüm Takvim
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
</div>