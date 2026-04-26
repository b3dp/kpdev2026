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
<section class="border-b border-primary/10 bg-white pt-0">
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
                    <span class="font-jakarta text-[12.5px] font-medium text-primary">Her Yıl 600 Öğrenci</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-bg-soft py-[72px]">
    <div class="mx-auto max-w-7xl px-6">
        <div class="mb-12 text-center">
            <p class="mb-2.5 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.1em] text-accent">Hadis-i Şerif</p>
            <p class="mb-3.5 font-baskerville text-[clamp(12px,3vw,18px)] font-bold text-primary">Kim, helâl kazancından bir hurma kadar sadaka verirse, - ki Allah, helâlden başkasını kabul etmez - Allah o sadakayı kabul eder. Sonra onu dağ gibi oluncaya kadar, herhangi birinizin tayını büyüttüğü gibi, sahibi adına ihtimamla büyütür.</p>
            <p class="mx-auto max-w-[540px] font-jakarta text-[15px] leading-[1.7] text-teal-muted">(Buhârî, Zekât 8; Tevhîd 23; Müslim, Zekât 63, 64)</p>
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

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const payload = {
            page_type: 'bagis_index',
            bagis_tur_sayisi: @json($bagisturleri->count()),
        };

        window.kpCerez?.trackEvent?.('bagis_sayfa_goruntuleme', payload, 'analitik');
        window.kpCerez?.trackEvent?.('bagis_sayfa_goruntuleme', payload, 'pazarlama');
    }, { once: true });
</script>
@endpush
