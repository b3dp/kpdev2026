@extends('layouts.checkout')

@section('title', 'Ödeme Adımı')
@section('meta_description', 'Sepetteki bağışlarınız için güvenli ödeme adımı.')
@section('robots', 'noindex, nofollow')

@section('content')
    <main class="mx-auto max-w-6xl px-4 pb-14 pt-[112px] sm:px-6 lg:px-8">
        <div id="bagis-odeme-form"
             data-odeme-url="{{ route('bagis.odeme') }}"
             data-slug="{{ $odemeSlug }}"
             data-tutar="{{ $odemeTutar }}"
             data-adet="{{ $odemeAdet }}"
             data-sahip-tipi="{{ $odemeSahipTipi }}"
             data-form-verisi='@json($odemeFormVerisi)'
             data-redirect-url="{{ route('bagis.tesekkur') }}">
        </div>

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-baskerville text-[28px] font-bold text-primary">Ödeme Adımı</h1>
                <p class="mt-1 font-jakarta text-sm text-teal-muted">Bağış kalemleriniz sepetinizde hazır. Kart bilgilerinizi girip güvenli ödeme adımını tamamlayabilirsiniz.</p>
            </div>
            <a href="{{ route('bagis.sepet') }}" class="inline-flex items-center justify-center rounded-[10px] border border-primary/10 bg-white px-4 py-2 font-jakarta text-sm font-semibold text-primary transition-colors hover:bg-bg-soft">Sepete dön</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.55fr,0.95fr]">
            <section class="rounded-2xl border border-primary/10 bg-white p-4 shadow-sm sm:p-5">
                <p class="font-jakarta text-sm font-bold uppercase tracking-[0.08em] text-teal-muted">Ödeyen Bilgileri</p>
                <p class="mt-1 font-jakarta text-xs text-teal-muted">Makbuz ve ödeme bildirimleri bu kişiye gönderilir.</p>

                <label class="mt-3 flex cursor-pointer items-center gap-3 rounded-xl border border-primary/10 bg-bg-soft px-3 py-2.5">
                    <input id="kopyala-odeme-toggle" type="checkbox" class="h-4 w-4 rounded border-primary/30 text-accent focus:ring-accent">
                    <span class="font-jakarta text-sm font-medium text-primary">Sahip bilgilerimi ödeme bilgisi olarak kullan</span>
                </label>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-jakarta text-xs font-semibold text-primary">Ad Soyad <span class="text-orange-cta">*</span></label>
                        <input type="text" id="odeyen-ad" class="w-full rounded-xl border border-primary/15 bg-white px-3 py-2.5 font-jakarta text-sm text-primary outline-none ring-0 transition-colors focus:border-accent" value="{{ (string) ($odemeFormVerisi['odeyen_ad_soyad'] ?? '') }}" placeholder="Ad Soyad">
                    </div>
                    <div>
                        <label class="mb-1 block font-jakarta text-xs font-semibold text-primary">TC Kimlik</label>
                        <input type="text" id="odeyen-tc" maxlength="11" class="w-full rounded-xl border border-primary/15 bg-white px-3 py-2.5 font-jakarta text-sm text-primary outline-none ring-0 transition-colors focus:border-accent" value="{{ (string) ($odemeFormVerisi['odeyen_tc'] ?? '') }}" placeholder="XXXXXXXXXXX">
                    </div>
                    <div>
                        <label class="mb-1 block font-jakarta text-xs font-semibold text-primary">E-posta <span class="text-orange-cta">*</span></label>
                        <input type="email" id="odeyen-email" class="w-full rounded-xl border border-primary/15 bg-white px-3 py-2.5 font-jakarta text-sm text-primary outline-none ring-0 transition-colors focus:border-accent" value="{{ (string) ($odemeFormVerisi['odeyen_eposta'] ?? '') }}" placeholder="ornek@mail.com">
                    </div>
                    <div>
                        <label class="mb-1 block font-jakarta text-xs font-semibold text-primary">Telefon <span class="text-orange-cta">*</span></label>
                        <input type="tel" id="odeyen-tel" class="w-full rounded-xl border border-primary/15 bg-white px-3 py-2.5 font-jakarta text-sm text-primary outline-none ring-0 transition-colors focus:border-accent" value="{{ (string) ($odemeFormVerisi['odeyen_telefon'] ?? '') }}" placeholder="05XX XXX XX XX">
                    </div>
                </div>

                <div class="my-5 h-px bg-slate-100"></div>

                <p class="font-jakarta text-sm font-bold uppercase tracking-[0.08em] text-teal-muted">Kart Bilgileri</p>

                @if($testOdemeAktif)
                    <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                        <p class="font-jakarta text-xs font-semibold text-emerald-800">Test ödeme modu aktif</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach($testKartlari as $kart)
                                <button type="button"
                                        data-kart-no="{{ $kart['kart_no'] }}"
                                        class="test-kart-btn rounded-lg border border-white/70 bg-white px-2.5 py-2 text-left font-jakarta text-xs text-primary transition-colors hover:bg-bg-soft">
                                    <span class="block font-semibold">{{ $kart['etiket'] }}</span>
                                    <span class="mt-0.5 block text-[11px] text-teal-muted">{{ $kart['kart_no'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-4">
                    <label class="mb-1 block font-jakarta text-xs font-semibold text-primary">Kart Numarası <span class="text-orange-cta">*</span></label>
                    <input type="text" id="kart-no" class="w-full rounded-xl border border-primary/15 bg-white px-3 py-2.5 font-jakarta text-sm text-primary outline-none ring-0 transition-colors focus:border-accent" placeholder="0000 0000 0000 0000">
                </div>

                <div class="mt-3 grid gap-3 sm:grid-cols-[2fr,1fr,1fr]">
                    <div>
                        <label class="mb-1 block font-jakarta text-xs font-semibold text-primary">Kart Üzerindeki İsim <span class="text-orange-cta">*</span></label>
                        <input type="text" id="kart-sahibi" class="w-full rounded-xl border border-primary/15 bg-white px-3 py-2.5 font-jakarta text-sm text-primary outline-none ring-0 transition-colors focus:border-accent" placeholder="Ad Soyad">
                    </div>
                    <div>
                        <label class="mb-1 block font-jakarta text-xs font-semibold text-primary">Son Kullanma <span class="text-orange-cta">*</span></label>
                        <input type="text" id="kart-son-kullanma" maxlength="7" class="w-full rounded-xl border border-primary/15 bg-white px-3 py-2.5 font-jakarta text-sm text-primary outline-none ring-0 transition-colors focus:border-accent" placeholder="AA/YY">
                    </div>
                    <div>
                        <label class="mb-1 block font-jakarta text-xs font-semibold text-primary">CVV <span class="text-orange-cta">*</span></label>
                        <input type="text" id="kart-cvv" maxlength="4" class="w-full rounded-xl border border-primary/15 bg-white px-3 py-2.5 font-jakarta text-sm text-primary outline-none ring-0 transition-colors focus:border-accent" placeholder="123">
                    </div>
                </div>

                <div id="odeme-mesaj" class="mt-4 hidden rounded-xl border px-4 py-3 font-jakarta text-[13px]"></div>

                <button type="button" id="odeme-tamamla-btn" class="mt-4 flex w-full items-center justify-center gap-2 rounded-[10px] bg-orange-cta px-4 py-3 font-jakarta text-sm font-bold text-white transition-colors hover:bg-[#c94620]">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    {{ $testOdemeAktif ? 'Test Ödemeyi Tamamla' : 'Ödemeyi Tamamla' }}
                </button>
            </section>

            <aside class="rounded-2xl border border-primary/10 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="font-jakarta text-sm font-bold uppercase tracking-[0.08em] text-teal-muted">Sepet Özeti</h2>

                <div class="mt-4 space-y-2.5">
                    @foreach($sepet as $satir)
                        <div class="rounded-xl border border-primary/10 bg-bg-soft px-3 py-2.5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-jakarta text-[13px] font-semibold text-primary">{{ $satir['ad'] ?? 'Bağış Kalemi' }}</p>
                                    <p class="mt-0.5 font-jakarta text-[11px] text-teal-muted">{{ ($satir['adet'] ?? 1) > 1 ? ($satir['adet'].' adet / hisse') : '1 adet' }}</p>
                                </div>
                                <span class="font-baskerville text-base font-bold text-primary">₺{{ number_format((float) ($satir['toplam'] ?? 0), 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 rounded-xl border border-primary/10 bg-white px-3 py-2.5">
                    <div class="flex items-center justify-between text-sm text-teal-muted">
                        <span>Toplam</span>
                        <span class="font-baskerville text-xl font-bold text-primary">₺{{ number_format((float) $sepetToplam, 2, ',', '.') }}</span>
                    </div>
                </div>
            </aside>
        </div>
    </main>
@endsection
