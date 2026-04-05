@extends('layouts.checkout')

@php
    $bagisci = $bagis->odeyenKisi() ?? $bagis->kisiler->first();
    $bagisTuru = $bagis->kalemler->first()?->bagisTuru;
    $makbuzUrl = filled($bagis->makbuz_yol)
        ? 'https://cdn.kestanepazari.org.tr/'.ltrim($bagis->makbuz_yol, '/')
        : null;

    $anaSayfaUrl = route('home');
    $paylasimMetni = config('site.ad')." Derneği'ne bağış yaptım!";
    $facebookPaylasimUrl = 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($anaSayfaUrl);
    $xPaylasimUrl = 'https://twitter.com/intent/tweet?text='.urlencode($paylasimMetni).'&url='.urlencode($anaSayfaUrl);
    $whatsappPaylasimUrl = 'https://wa.me/?text='.urlencode($paylasimMetni.' '.$anaSayfaUrl);
@endphp

@section('title', 'Teşekkürler — Bağışınız Alındı')
@section('meta_description', 'Bağış işleminiz tamamlandı. Dijital makbuzunuz hazırlanıyor.')
@section('robots', 'noindex, nofollow')
@section('canonical', route('bagis.tesekkur'))

@section('checkout_progress')
    <div class="flex flex-wrap items-center justify-center gap-3 rounded-xl border border-green-200 bg-green-50 px-3 py-2">
        @foreach(['Bağış Bilgileri', 'Ödeme', 'Tamamlandı'] as $adim)
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-green-600 text-[11px] font-bold text-white">✓</span>
                <span class="font-jakarta text-[12.5px] font-semibold text-green-800">{{ $adim }}</span>
            </div>
            @if(! $loop->last)
                <span class="h-px w-5 bg-green-400/70"></span>
            @endif
        @endforeach
    </div>
@endsection

@section('checkout_actions')
    <a href="{{ route('home') }}"
       class="flex items-center gap-1.5 font-jakarta text-[13px] text-teal-muted no-underline transition-colors hover:text-primary">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Ana Sayfa
    </a>
@endsection

@section('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Order",
  "orderNumber": @json($bagis->bagis_no),
  "orderStatus": "https://schema.org/OrderDelivered",
  "seller": {
    "@@type": "Organization",
    "name": @json(config('site.ad').' Derneği')
  },
  "orderDate": @json($bagis->odeme_tarihi?->toIso8601String()),
  "price": @json((float) $bagis->toplam_tutar),
  "priceCurrency": "TRY"
}
</script>
@endsection

@section('content')
    <main class="flex min-h-screen flex-1 items-center justify-center px-6 pb-12 pt-[112px] md:px-8">
        <div class="w-full max-w-2xl">
            <section class="fade-up-1 relative mb-9 text-center">
                <div id="confetti-wrap" class="pointer-events-none absolute inset-0 overflow-hidden"></div>

                <div class="onay-cember mb-5 inline-flex h-[88px] w-[88px] items-center justify-center rounded-full bg-gradient-to-br from-green-600 to-green-700 shadow-[0_8px_32px_rgba(22,163,74,.3)]">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none">
                        <path class="onay-check" d="M5 13l4 4L19 7" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <h1 class="mb-3 font-baskerville text-[clamp(24px,4vw,32px)] font-bold leading-[1.25] text-primary">
                    Bağışınız için<br class="hidden sm:block"> teşekkürler!
                </h1>

                <p class="mx-auto max-w-[460px] font-jakarta text-[15px] leading-[1.7] text-teal-muted">
                    Ödemeniz başarıyla alındı.
                    @if($bagisci?->eposta)
                        Makbuzunuz
                        <strong class="font-semibold text-primary">{{ $bagisci->eposta }}</strong>
                        adresine gönderildi.
                    @else
                        Makbuzunuz kayıtlı e-posta adresinize gönderilecektir.
                    @endif
                </p>
            </section>

            <section class="makbuz-kutu fade-up-2 mb-5">
                <div class="flex items-center justify-between bg-gradient-to-br from-primary to-[#091420] px-5 py-4">
                    <div class="flex items-center gap-2">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#EBDFB5" stroke-width="2"><path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span class="font-jakarta text-sm font-bold text-[#EBDFB5]">Bağış Makbuzu</span>
                    </div>
                    <span class="font-jakarta text-xs text-[#EBDFB5]/60">#{{ $bagis->bagis_no }}</span>
                </div>

                <div class="px-5 py-2">
                    <div class="makbuz-row">
                        <span class="font-jakarta text-[13px] text-teal-muted">Bağışçı</span>
                        <span class="font-jakarta text-[13.5px] font-semibold text-primary">{{ $bagisci?->ad_soyad ?? '—' }}</span>
                    </div>
                    <div class="makbuz-row">
                        <span class="font-jakarta text-[13px] text-teal-muted">Bağış Türü</span>
                        <span class="inline-flex items-center gap-2 font-jakarta text-[13.5px] font-semibold text-primary">
                            <span class="inline-block h-2 w-2 rounded-full bg-accent"></span>
                            {{ $bagisTuru?->ad ?? '—' }}
                        </span>
                    </div>
                    <div class="makbuz-row">
                        <span class="font-jakarta text-[13px] text-teal-muted">Tutar</span>
                        <span class="font-baskerville text-[18px] font-bold text-primary">₺{{ number_format((float) $bagis->toplam_tutar, 2, ',', '.') }}</span>
                    </div>
                    <div class="makbuz-row">
                        <span class="font-jakarta text-[13px] text-teal-muted">Bağış No</span>
                        <span class="font-jakarta text-[13px] font-semibold tracking-[0.03em] text-primary">#{{ $bagis->bagis_no }}</span>
                    </div>
                    <div class="makbuz-row">
                        <span class="font-jakarta text-[13px] text-teal-muted">Tarih &amp; Saat</span>
                        <span class="font-jakarta text-[13px] font-semibold text-primary">{{ $bagis->odeme_tarihi?->translatedFormat('d M Y · H:i') ?? '—' }}</span>
                    </div>
                    <div class="makbuz-row border-b-0 pb-4">
                        <span class="font-jakarta text-[13px] text-teal-muted">Durum</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 font-jakarta text-xs font-bold text-green-700">
                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                            Ödendi
                        </span>
                    </div>
                </div>
            </section>

            <section class="fade-up-3 mb-7 flex flex-col gap-2.5">
                @if($makbuzUrl)
                    <a href="{{ $makbuzUrl }}"
                       target="_blank"
                       download
                       class="flex w-full items-center justify-center gap-2 rounded-[10px] bg-primary px-4 py-[13px] font-jakarta text-sm font-bold text-[#EBDFB5] transition-colors hover:bg-[#091420]">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Makbuzu İndir (PDF)
                    </a>
                @else
                    <button type="button"
                            disabled
                            class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-[10px] bg-primary px-4 py-[13px] font-jakarta text-sm font-bold text-[#EBDFB5] opacity-50">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Makbuz Hazırlanıyor...
                    </button>
                @endif

                <a href="{{ route('home') }}"
                   class="flex w-full items-center justify-center gap-2 rounded-[10px] border border-primary/15 bg-white px-4 py-[13px] font-jakarta text-sm font-semibold text-primary transition-colors hover:bg-bg-soft">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Ana Sayfaya Dön
                </a>
            </section>

            <section class="fade-up-4 rounded-2xl border border-primary/8 bg-white px-5 py-5 text-center">
                <p class="mb-1.5 font-baskerville text-base font-bold text-primary">İyiliği Yayalım</p>
                <p class="mb-4 font-jakarta text-[13.5px] leading-[1.6] text-teal-muted">
                    Bağışınızı paylaşarak daha fazla insanı bu güzel harekete davet edin.
                </p>

                <div class="flex flex-wrap justify-center gap-2.5">
                    <a href="{{ $facebookPaylasimUrl }}" target="_blank" rel="noopener" class="share-btn bg-[#1877F2] text-white">
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                        Facebook'ta Paylaş
                    </a>
                    <a href="{{ $xPaylasimUrl }}" target="_blank" rel="noopener" class="share-btn bg-black text-white">
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        X'te Paylaş
                    </a>
                    <a href="{{ $whatsappPaylasimUrl }}" target="_blank" rel="noopener" class="share-btn bg-[#25D366] text-white">
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.145.565 4.16 1.552 5.9L.057 23.928l6.204-1.628A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.793 9.793 0 01-5.002-1.374l-.36-.213-3.682.966.982-3.589-.233-.37A9.79 9.79 0 012.182 12C2.182 6.58 6.58 2.182 12 2.182S21.818 6.58 21.818 12 17.42 21.818 12 21.818z"/></svg>
                        WhatsApp
                    </a>
                </div>
            </section>
        </div>
    </main>
@endsection
