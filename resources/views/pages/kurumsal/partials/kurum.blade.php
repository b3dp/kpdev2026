<section class="kurumsal-section-card">
    <p class="kurumsal-eyebrow">Kurum sayfası</p>
    <h2 class="kurumsal-section-title">{{ $sayfa->kurum?->ad ?? $sayfa->ad }}</h2>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
        <div class="kurumsal-prose">
            @if($sayfa->icerik)
                {!! $sayfa->icerik !!}
            @else
                <p>{{ $sayfa->ozet ?: 'Bu kuruma ait içerik kısa süre içinde güncellenecektir.' }}</p>
            @endif
        </div>

        <div class="rounded-[20px] border border-[#162e4b]/10 bg-[#f7f5f0] p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#62868d]">Kurum bilgisi</p>
            <div class="mt-3 space-y-3 text-sm text-[#162e4b]/80">
                @if($sayfa->kurum?->tip)
                    <div><strong class="block text-[#162e4b]">Tür</strong><span>{{ $sayfa->kurum->tip }}</span></div>
                @endif
                @if($sayfa->kurum?->telefon)
                    <div><strong class="block text-[#162e4b]">Telefon</strong><a href="tel:{{ preg_replace('/\s+/', '', $sayfa->kurum->telefon) }}" class="transition hover:text-[#b27829]">{{ $sayfa->kurum->telefon }}</a></div>
                @endif
                @if($sayfa->kurum?->eposta)
                    <div><strong class="block text-[#162e4b]">E-posta</strong><a href="mailto:{{ $sayfa->kurum->eposta }}" class="transition hover:text-[#b27829]">{{ $sayfa->kurum->eposta }}</a></div>
                @endif
                @if($sayfa->kurum?->web_sitesi)
                    <div><strong class="block text-[#162e4b]">Web sitesi</strong><a href="{{ $sayfa->kurum->web_sitesi }}" target="_blank" rel="noopener" class="break-all transition hover:text-[#b27829]">{{ $sayfa->kurum->web_sitesi }}</a></div>
                @endif
                @if($sayfa->kurum?->adres)
                    <div><strong class="block text-[#162e4b]">Adres</strong><span>{{ $sayfa->kurum->adres }}</span></div>
                @endif
            </div>
        </div>
    </div>
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
