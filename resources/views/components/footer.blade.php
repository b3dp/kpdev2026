@php
    use App\Enums\KurumsalSablonu;
    $telefon = config('site.telefon');
    $telefon_link = preg_replace('/\D+/', '', (string) $telefon);
    $eposta = config('site.eposta', 'bilgi@kestanepazari.org.tr');
    $footerKurumlar = \App\Models\KurumsalSayfa::where('sablon', KurumsalSablonu::Kurum->value)
        ->where('durum', 'yayinda')->orderBy('sira')->get(['ad', 'slug']);
    $footerAtölyeler = \App\Models\KurumsalSayfa::where('sablon', KurumsalSablonu::Atolye->value)
        ->where('durum', 'yayinda')->orderBy('ad')->get(['ad', 'slug']);
    $footerBagislar = \App\Models\BagisTuru::where('aktif', true)->orderBy('ad')->get(['ad', 'slug']);
@endphp

<footer class="mt-12">
    {{-- Dekoratif çizgi --}}
    <div class="h-[3px] bg-[linear-gradient(to_right,transparent,#B27829_30%,#B27829_70%,transparent)] opacity-70"></div>

    {{-- ──────────────── 1. SATIR: Sosyal Medya + E-posta + Telefon ──────────────── --}}
    <div class="border-b border-white/10 bg-primary">
        <div class="mx-auto max-w-7xl px-6 py-4">
            <div class="grid grid-cols-1 items-center gap-4 text-center md:grid-cols-3 md:text-left">

                {{-- Sol: Sosyal medya --}}
                <div class="flex flex-wrap items-center justify-center gap-3 md:justify-start">
                    <span class="ftr-top-label">Sosyal Medya</span>
                    <div class="flex items-center gap-2">
                        @if(config('site.facebook'))
                            <a href="{{ config('site.facebook') }}" class="ftr-social-btn" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                            </a>
                        @endif
                        @if(config('site.instagram'))
                            <a href="{{ config('site.instagram') }}" class="ftr-social-btn" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor" stroke="none"/></svg>
                            </a>
                        @endif
                        @if(config('site.x'))
                            <a href="{{ config('site.x') }}" class="ftr-social-btn" target="_blank" rel="noopener noreferrer" aria-label="X / Twitter">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                        @endif
                        @if(config('site.youtube'))
                            <a href="{{ config('site.youtube') }}" class="ftr-social-btn" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 001.46 6.42 29.09 29.09 0 001 12a29.09 29.09 0 00.46 5.58 2.78 2.78 0 001.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 001.95-1.96A29.09 29.09 0 0023 12a29.09 29.09 0 00-.46-5.58zM9.75 15.02V8.98L15.5 12l-5.75 3.02z"/></svg>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Orta: E-posta --}}
                <div class="flex items-center justify-center">
                    <a href="mailto:{{ $eposta }}"
                       class="ftr-top-link inline-flex items-center gap-2">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ $eposta }}</span>
                    </a>
                </div>

                {{-- Sağ: Çağrı Merkezi --}}
                <a href="tel:{{ $telefon_link }}"
                   class="ftr-call-card ml-auto flex items-center gap-3 rounded-xl px-4 py-2.5 md:justify-self-end">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-accent">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="ftr-top-label">Çağrı Merkezi</p>
                        <p class="font-jakarta text-[14px] font-bold text-white">{{ $telefon }}</p>
                    </div>
                </a>

            </div>
        </div>
    </div>

    {{-- ──────────────── 2. SATIR: 6 Sütunlu Bağlantılar ──────────────── --}}
    <div class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 xl:grid-cols-6">

                <div>
                    <a href="{{ route('home') }}" class="inline-flex" aria-label="{{ config('site.ad') }}">
                        <img src="{{ asset('images/logo.svg') }}"
                             alt="{{ config('site.ad') }} logosu"
                             class="h-auto w-full max-w-[210px]"
                             loading="lazy">
                    </a>
                </div>

                <div>
                    <h3 class="ftr-col-head">Kurumsal</h3>
                    <ul class="ftr-link-list">
                        <li><a href="{{ route('kurumsal.show', ['slug' => 'hakkimizda']) }}" class="ftr-link">Hakkımızda</a></li>
                        <li><a href="{{ route('kurumsal.show', ['slug' => 'tarihce']) }}" class="ftr-link">Tarihçe</a></li>
                        <li><a href="{{ route('kurumsal.show', ['slug' => 'amacimiz']) }}" class="ftr-link">Amacımız</a></li>
                        <li><a href="{{ route('kurumsal.show', ['slug' => 'kurumlar']) }}" class="ftr-link">Kurumlar</a></li>
                        <li><a href="{{ route('kurumsal.show', ['slug' => 'atolyeler']) }}" class="ftr-link">Atölyeler</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="ftr-col-head">Kurumlar</h3>
                    <ul class="ftr-link-list">
                        @foreach($footerKurumlar as $kurum)
                            <li>
                                <a href="{{ route('kurumsal.show', ['slug' => $kurum->slug]) }}" class="ftr-link">
                                    {{ $kurum->ad }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h3 class="ftr-col-head">Atölyeler</h3>
                    <ul class="ftr-link-list">
                        @foreach($footerAtölyeler as $atolye)
                            <li>
                                <a href="{{ route('kurumsal.show', ['slug' => $atolye->slug]) }}" class="ftr-link">
                                    {{ $atolye->ad }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h3 class="ftr-col-head">Bağış Yap</h3>
                    <ul class="ftr-link-list">
                        @foreach($footerBagislar as $tur)
                            <li>
                                <a href="{{ route('bagis.show', $tur->slug) }}" class="ftr-link">
                                    {{ $tur->ad }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h3 class="ftr-col-head">Bilgi Al</h3>
                    <ul class="ftr-link-list">
                        <li><a href="{{ route('iletisim.index') }}" class="ftr-link">İletişim</a></li>
                        <li><a href="{{ route('kurumsal.show', ['slug' => 'kvkk']) }}" class="ftr-link">KVKK</a></li>
                        <li><a href="{{ route('kurumsal.show', ['slug' => 'banka-hesaplari']) }}" class="ftr-link">Banka Hesapları</a></li>
                        <li><a href="{{ route('kurumsal.show', ['slug' => 'gizlilik-politikasi']) }}" class="ftr-link">Gizlilik Politikası</a></li>
                        <li><a href="{{ route('kurumsal.show', ['slug' => 'cerez-politikasi']) }}" class="ftr-link">Çerez Politikası</a></li>
                    </ul>
                </div>

            </div>
        </div>

        <div class="mx-auto max-w-7xl px-6">
            <div class="h-px bg-primary/10"></div>
        </div>
    </div>

    {{-- ──────────────── BOTTOM: Telif Hakkı ──────────────── --}}
    <div class="bg-[#DED099]">
        <div class="mx-auto max-w-7xl px-6 py-4">
            <p class="text-center font-jakarta text-[12.5px] text-primary/60">
                &copy; {{ date('Y') }} — Kestanepazarı Öğrenci Yetiştirme Derneği. Tüm hakları saklıdır.
            </p>
        </div>
    </div>
</footer>
