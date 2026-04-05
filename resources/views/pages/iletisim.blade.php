@extends('layouts.app')

@php
    $telefon = config('iletisim.telefon', config('site.telefon'));
    $telefonLink = config('iletisim.telefon_link') ?: ('+' . preg_replace('/\D+/', '', (string) $telefon));
    $eposta = config('iletisim.merkez_eposta', config('site.eposta'));
    $calismaSaatleri = 'Hafta içi 09:00 – 17:00';
    $ilkLokasyon = $lokasyonlar->first();
    $metaDescription = $sayfa->meta_description ?: 'Kestanepazarı iletişim bilgileri, şubeler ve mesaj gönderim formu bu sayfada yer alır.';
    $schemaData = [
        '@context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'name' => 'İletişim',
        'description' => $metaDescription,
        'url' => route('iletisim.index'),
        'mainEntity' => [
            '@type' => 'Organization',
            'name' => config('site.ad') . ' ' . config('site.aciklama'),
            'telephone' => $telefon,
            'email' => $eposta,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => config('site.adres'),
                'addressCountry' => 'TR',
            ],
        ],
    ];
@endphp

@section('title', 'İletişim')
@section('meta_description', $metaDescription)
@section('robots', 'index, follow')
@section('canonical', route('iletisim.index'))
@section('og_image', $sayfa->ogGorselUrl() ?: 'https://cdn.kestanepazari.org.tr/logo.png')

@section('schema')
<script type="application/ld+json">
@json($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
</script>
@endsection

@section('content')
    <section class="border-b border-primary/8 bg-white pt-[102px]">
        <div class="mx-auto max-w-7xl px-4 pb-6 pt-7 lg:px-6">
            <div class="mb-4 flex items-center gap-2 text-[13px] text-teal-muted">
                <a href="{{ route('home') }}" class="transition hover:text-accent">Ana Sayfa</a>
                <span>/</span>
                <span class="font-medium text-primary">İletişim</span>
            </div>

            <p class="mb-2 text-[12.5px] font-semibold uppercase tracking-[0.12em] text-accent">Bize Ulaşın</p>
            <h1 class="font-baskerville text-[clamp(28px,3.6vw,40px)] font-bold text-primary">İletişim</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-muted md:text-[15px]">
                {{ strip_tags((string) ($sayfa->icerik ?: 'Soru, öneri ve iş birliği talepleriniz için bizimle iletişime geçebilirsiniz. Ekibimiz size en kısa sürede dönüş yapacaktır.')) }}
            </p>
        </div>
    </section>

    <section class="iletisim-shared-band">
        <div class="mx-auto max-w-7xl px-4 lg:px-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <a href="tel:{{ $telefonLink }}" class="shared-contact-item">
                    <span class="shared-contact-icon">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </span>
                    <span>
                        <small>Telefon</small>
                        <strong>{{ $telefon }}</strong>
                    </span>
                </a>

                <a href="mailto:{{ $eposta }}" class="shared-contact-item">
                    <span class="shared-contact-icon">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    <span>
                        <small>E-posta</small>
                        <strong>{{ $eposta }}</strong>
                    </span>
                </a>

                <div class="shared-contact-item is-static">
                    <span class="shared-contact-icon">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4l3 3"/></svg>
                    </span>
                    <span>
                        <small>Çalışma Saatleri</small>
                        <strong>{{ $calismaSaatleri }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 lg:px-6 lg:py-12">
        @if(session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <div id="iletisim-sayfa" data-selected-lokasyon="{{ old('lokasyon', $ilkLokasyon['ad'] ?? '') }}" class="grid gap-8 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <div>
                    <p class="iletisim-label">Şubelerimiz</p>
                    <div id="lok-tabs" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($lokasyonlar as $index => $lokasyon)
                            <button type="button" class="lokasyon-tab {{ $index === 0 ? 'active' : '' }}" data-lokasyon-index="{{ $index }}">
                                <span class="lok-number">{{ $lokasyon['kod'] }}</span>
                                <span class="lok-name">{{ $lokasyon['ad'] }}</span>
                                <span class="lok-short">{{ $lokasyon['kisa_ad'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="overflow-hidden rounded-[18px] border border-primary/8 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-primary/8 px-5 py-4">
                        <div>
                            <p id="lok-label" class="text-[11px] font-semibold uppercase tracking-[0.08em] text-accent">
                                {{ $ilkLokasyon['kod'] ?? '01' }} — {{ $ilkLokasyon['ad'] ?? 'Genel Merkez' }}
                            </p>
                            <h2 id="lok-title" class="mt-1 font-baskerville text-[20px] font-bold text-primary">
                                {{ $ilkLokasyon['baslik'] ?? (config('site.ad') . ' İletişim') }}
                            </h2>
                        </div>

                        <a id="lok-directions" href="{{ $ilkLokasyon['yon_tarifi_url'] ?? '#' }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-[10px] border border-primary/15 bg-bg-soft px-4 py-2 text-[13px] font-semibold text-primary transition hover:border-accent hover:bg-cream">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Yol Tarifi Al
                        </a>
                    </div>

                    <div id="map-container" class="map-frame">
                        @if(!empty($ilkLokasyon['harita_url']))
                            <iframe src="{{ $ilkLokasyon['harita_url'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        @else
                            <div class="map-placeholder">
                                <div class="map-grid"></div>
                                <div class="map-pin">
                                    <svg width="36" height="44" viewBox="0 0 36 44" fill="none"><path d="M18 0C8.059 0 0 8.059 0 18c0 13.5 18 26 18 26S36 31.5 36 18C36 8.059 27.941 0 18 0z" fill="#162E4B"/><circle cx="18" cy="18" r="7" fill="#B27829"/></svg>
                                </div>
                                <div class="map-pin-shadow"></div>
                                <p id="map-name-label" class="map-name-label">{{ $ilkLokasyon['baslik'] ?? 'İletişim Noktası' }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4 px-5 py-5">
                        <div class="info-row">
                            <div class="info-icon bg-bg-soft">
                                <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <p class="info-meta-label">Adres</p>
                                <p id="lok-adres" class="info-meta-value">{{ $ilkLokasyon['adres'] ?? config('site.adres') }}</p>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-icon bg-bg-soft">
                                <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="info-meta-label">E-posta</p>
                                <a id="lok-eposta" href="mailto:{{ $ilkLokasyon['eposta'] ?? $eposta }}" class="info-meta-link">{{ $ilkLokasyon['eposta'] ?? $eposta }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="sticky top-[88px] overflow-hidden rounded-[18px] border border-primary/8 bg-white shadow-sm">
                    <div class="bg-[linear-gradient(135deg,#162E4B,#28484C)] px-5 py-5">
                        <h3 class="font-baskerville text-[18px] font-bold text-cream">Mesaj Gönderin</h3>
                        <p class="mt-1 text-[12.5px] text-cream/65">En kısa sürede size geri döneceğiz.</p>
                    </div>

                    <form action="{{ route('iletisim.store') }}" method="POST" class="space-y-4 px-5 py-5">
                        @csrf

                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-group">
                                <label class="form-label" for="ad">Ad <span>*</span></label>
                                <input id="ad" name="ad" type="text" value="{{ old('ad') }}" class="form-input" placeholder="Adınız">
                                @error('ad') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="soyad">Soyad <span>*</span></label>
                                <input id="soyad" name="soyad" type="text" value="{{ old('soyad') }}" class="form-input" placeholder="Soyadınız">
                                @error('soyad') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="eposta">E-posta <span>*</span></label>
                            <input id="eposta" name="eposta" type="email" value="{{ old('eposta') }}" class="form-input" placeholder="ornek@mail.com">
                            @error('eposta') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="telefon">Telefon</label>
                            <input id="telefon" name="telefon" type="text" value="{{ old('telefon') }}" class="form-input" placeholder="05XX XXX XX XX">
                            @error('telefon') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="konu">Konu <span>*</span></label>
                            <select id="konu" name="konu" class="form-select">
                                <option value="">Konu seçin...</option>
                                @foreach(['Burs Başvurusu','Bağış Hakkında','E-Kayıt','Etkinlik Bilgisi','Üyelik','Kurumsal İşbirliği','Diğer'] as $konu)
                                    <option value="{{ $konu }}" @selected(old('konu') === $konu)>{{ $konu }}</option>
                                @endforeach
                            </select>
                            @error('konu') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="form-lokasyon">Şube / Lokasyon</label>
                            <select id="form-lokasyon" name="lokasyon" class="form-select">
                                <option value="">Şube seçin (opsiyonel)</option>
                                @foreach($lokasyonlar as $lokasyon)
                                    <option value="{{ $lokasyon['ad'] }}" @selected(old('lokasyon') === $lokasyon['ad'])>{{ $lokasyon['kod'] }} — {{ $lokasyon['ad'] }}</option>
                                @endforeach
                            </select>
                            @error('lokasyon') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="mesaj">Mesajınız <span>*</span></label>
                            <textarea id="mesaj" name="mesaj" rows="5" class="form-input" style="resize:vertical;">{{ old('mesaj') }}</textarea>
                            @error('mesaj') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <label class="kvkk-box">
                            <input type="checkbox" name="kvkk" value="1" @checked(old('kvkk'))>
                            <span> <a href="{{ route('kurumsal.show', 'kvkk') }}">KVKK Aydınlatma Metni</a>'ni okudum, kişisel verilerimin işlenmesine onay veriyorum. <strong>*</strong></span>
                        </label>
                        @error('kvkk') <p class="form-error">{{ $message }}</p> @enderror

                        <button type="submit" class="submit-btn">
                            <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            Mesaj Gönder
                        </button>

                        <p class="text-center text-[11.5px] text-teal-muted">Genellikle 1 iş günü içinde yanıtlarız.</p>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script id="iletisim-lokasyonlar" type="application/json">@json($lokasyonlar->values(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>
@endsection
