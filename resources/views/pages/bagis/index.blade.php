@extends('layouts.app')

@section('title', 'Bağış Yap')
@section('meta_description', 'Zekat, kurban, fitre, burs desteği ve genel bağış için güvenli online bağış. Dijital makbuz, şeffaf raporlama.')
@section('robots', 'index, follow')
@section('og_type', 'website')
@section('og_image', asset('img/og-bagis.jpg'))

@section('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "ItemList",
  "name": "Bağış Türleri — Kestanepazarı",
  "url": "{{ route('bagis.index') }}",
  "numberOfItems": {{ $bagisturleri->count() }},
  "itemListElement": [
    @foreach($bagisturleri as $i => $tur)
    {
      "@@type": "ListItem",
      "position": {{ $i + 1 }},
      "name": @json($tur->ad),
      "url": @json(route('bagis.show', $tur->slug))
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
    {
      "@@type": "ListItem",
      "position": 1,
      "name": "Ana Sayfa",
      "item": "{{ url('/') }}"
    },
    {
      "@@type": "ListItem",
      "position": 2,
      "name": "Bağış Yap",
      "item": "{{ route('bagis.index') }}"
    }
  ]
}
</script>
@endsection

@section('content')
<section class="border-b border-primary/10 bg-white pt-[106px] lg:pt-[114px]">
    <div class="mx-auto max-w-7xl px-6 pb-0 pt-8">
        @if(session('info'))
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 font-jakarta text-sm text-sky-800">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" class="mt-0.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8h.01M11 12h1v4h1m-1-12a9 9 0 100 18 9 9 0 000-18z"/></svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif
        <div class="mb-4 flex items-center gap-1.5">
            <a href="{{ route('home') }}" class="font-jakarta text-[13px] text-teal-muted transition-colors hover:text-accent">Ana Sayfa</a>
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
            <span class="font-jakarta text-[13px] font-medium text-primary">Bağış</span>
        </div>

        <div class="flex flex-wrap items-end justify-between gap-4 pb-7">
            <div>
                <p class="mb-2 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.1em] text-accent">İyiliğin Farklı Yüzleri</p>
                <h1 class="font-baskerville text-[clamp(26px,3.5vw,38px)] font-bold leading-[1.2] text-primary">Bağış Yap</h1>
            </div>

            <div class="flex flex-wrap gap-2.5">
                <div class="inline-flex items-center gap-1.5 rounded-lg border border-primary/10 bg-bg-soft px-3.5 py-2">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="font-jakarta text-[12.5px] font-medium text-primary">Güvenli Ödeme</span>
                </div>
                <div class="inline-flex items-center gap-1.5 rounded-lg border border-primary/10 bg-bg-soft px-3.5 py-2">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span class="font-jakarta text-[12.5px] font-medium text-primary">Dijital Makbuz</span>
                </div>
                <div class="inline-flex items-center gap-1.5 rounded-lg border border-primary/10 bg-bg-soft px-3.5 py-2">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><path stroke-linecap="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="font-jakarta text-[12.5px] font-medium text-primary">9.000+ Bağışçı</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-bg-soft py-[72px]">
    <div class="mx-auto max-w-7xl px-6">
        <div class="mb-12 text-center">
            <p class="mb-2.5 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.1em] text-accent">Bağış Kategorileri</p>
            <h2 class="mb-3.5 font-baskerville text-[clamp(24px,3vw,36px)] font-bold text-primary">Hangi İyiliği Yapmak İstersiniz?</h2>
            <p class="mx-auto max-w-[540px] font-jakarta text-[15px] leading-[1.7] text-teal-muted">Her bağış türünü ayrı ayrı inceleyerek dilediğiniz kategoriden destek olabilirsiniz.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($bagisturleri as $tur)
                @php($fiyat_tipi = $tur->fiyat_tipi?->value ?? $tur->fiyat_tipi)
                <a href="{{ route('bagis.show', $tur->slug) }}" class="bagis-kart">
                    <div class="bagis-foto">
                        @if($tur->gorsel_kare)
                            <img
                                src="https://cdn.kestanepazari.org.tr/{{ ltrim($tur->gorsel_kare, '/') }}"
                                alt="{{ $tur->ad }}"
                                class="absolute inset-0 h-full w-full object-cover"
                                loading="lazy"
                                width="400"
                                height="200"
                            >
                        @else
                            <div class="absolute inset-0" style="background:linear-gradient(135deg,#162E4B 0%,#091420 100%);"></div>
                        @endif

                        <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,.55) 0%,transparent 60%);"></div>

                        <div class="bagis-arrow">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="mb-2 font-jakarta text-[17px] font-bold text-primary">{{ $tur->ad }}</h3>
                        <p class="mb-4 flex-1 font-jakarta text-[13.5px] leading-[1.65] text-teal-muted">{{ $tur->aciklama }}</p>
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-jakarta text-[13px] font-bold tracking-[0.04em] text-accent">BAĞIŞ YAP</span>
                            @if($fiyat_tipi === 'sabit' && $tur->fiyat)
                                <span class="font-jakarta text-xs text-teal-muted">₺{{ number_format((float) $tur->fiyat, 0, ',', '.') }}</span>
                            @elseif($fiyat_tipi === 'serbest')
                                <span class="font-jakarta text-xs text-teal-muted">Serbest tutar</span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-1 py-16 text-center sm:col-span-2 lg:col-span-3">
                    <p class="font-jakarta text-sm text-teal-muted">Henüz bağış türü eklenmemiş.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section class="bg-bg-soft pb-[72px]">
    <div class="mx-auto max-w-7xl px-6">
        <div class="duzenli-band px-6 py-10 lg:px-[52px] lg:py-[52px]">
            <div class="pointer-events-none absolute -right-20 -top-20 h-[280px] w-[280px] rounded-full bg-white/8"></div>
            <div class="pointer-events-none absolute bottom-[-50px] left-[-50px] h-[200px] w-[200px] rounded-full bg-black/5"></div>
            <div class="pointer-events-none absolute right-[25%] top-1/2 h-[120px] w-[120px] -translate-y-1/2 rounded-full bg-white/5"></div>

            <div class="relative z-[1] grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <div class="mb-[18px] inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/15 px-3.5 py-[5px]">
                        <svg width="12" height="12" fill="#fff" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6L12 2z"/></svg>
                        <span class="font-jakarta text-xs font-semibold tracking-[0.04em] text-white">Düzenli Bağışçı Programı</span>
                    </div>

                    <h2 class="mb-4 font-baskerville text-[clamp(24px,2.8vw,36px)] font-bold leading-[1.25] text-white">
                        Her Ay Bir Öğrencinin
                        <br class="hidden sm:block">
                        Yanında Olun
                    </h2>

                    <p class="mb-7 max-w-[420px] font-jakarta text-[15px] leading-[1.75] text-white/80">
                        Aylık düzenli bağışlarınızla öğrencilerimize sürekli destek olun. Dilediğiniz zaman iptal edebilirsiniz.
                    </p>

                    <div class="mb-8 flex flex-col gap-[11px]">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full bg-white/20">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2.5"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="font-jakarta text-sm text-white/90">Otomatik aylık kesinti, zahmetsiz destek</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full bg-white/20">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2.5"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="font-jakarta text-sm text-white/90">Aylık e-posta raporu ile şeffaflık</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full bg-white/20">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2.5"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="font-jakarta text-sm text-white/90">İstediğiniz zaman durdurabilirsiniz</span>
                        </div>
                    </div>

                    <a href="{{ route('bagis.show', 'duzenli') }}" class="inline-flex items-center gap-2 rounded-[10px] bg-white px-7 py-[13px] font-jakarta text-sm font-bold text-orange-cta shadow-[0_4px_16px_rgba(0,0,0,.15)] transition-all hover:-translate-y-px hover:bg-cream">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        Düzenli Bağışçı Ol
                    </a>
                </div>

                <div class="rounded-2xl border border-white/25 bg-white/12 p-7 backdrop-blur-sm">
                    <p class="mb-4 font-jakarta text-[13.5px] font-semibold text-white/80">Aylık bağış tutarı seçin:</p>

                    <div id="tutar-grid" class="mb-3.5 grid grid-cols-2 gap-2.5">
                        <button type="button" class="tutar-btn rounded-[10px] border-2 border-white bg-white px-4 py-[13px] font-jakarta text-base font-bold text-orange-cta" data-tutar="100">₺100</button>
                        <button type="button" class="tutar-btn rounded-[10px] border border-white/30 bg-white/12 px-4 py-[13px] font-jakarta text-base font-semibold text-white transition-colors" data-tutar="250">₺250</button>
                        <button type="button" class="tutar-btn rounded-[10px] border border-white/30 bg-white/12 px-4 py-[13px] font-jakarta text-base font-semibold text-white transition-colors" data-tutar="500">₺500</button>
                        <button type="button" class="tutar-btn rounded-[10px] border border-white/30 bg-white/12 px-4 py-[13px] font-jakarta text-base font-semibold text-white transition-colors" data-tutar="1000">₺1.000</button>
                    </div>

                    <div class="mb-3.5 flex items-center gap-2 rounded-[10px] border border-white/30 bg-white/12 px-3.5 py-[11px]">
                        <span class="font-jakarta text-sm font-medium text-white/60">₺</span>
                        <input type="number" placeholder="Diğer tutar" class="w-full bg-transparent font-jakarta text-sm text-white outline-none placeholder:text-white/60">
                    </div>

                    <a href="{{ route('bagis.show', 'duzenli') }}" id="aylik-basla-btn" class="flex items-center justify-center gap-1.5 rounded-[10px] bg-white px-4 py-[13px] font-jakarta text-sm font-bold text-orange-cta shadow-[0_4px_14px_rgba(0,0,0,.15)] transition-colors hover:bg-cream">
                        <span id="aylik-basla-text">Aylık ₺100 ile Başla</span>
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <p class="mt-2.5 text-center font-jakarta text-[11.5px] text-white/50">İstediğiniz zaman iptal edebilirsiniz</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
