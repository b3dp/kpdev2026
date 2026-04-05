<section class="kurumsal-section-card">
    <p class="kurumsal-eyebrow">İletişim</p>
    <h2 class="kurumsal-section-title">Bizimle iletişime geçin</h2>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
        <div class="kurumsal-prose">
            @if($sayfa->icerik)
                {!! $sayfa->icerik !!}
            @else
                <p>Kurumumuzla ilgili soru, öneri ve iş birliği talepleriniz için aşağıdaki iletişim bilgilerinden bize ulaşabilirsiniz.</p>
            @endif
        </div>

        <div class="rounded-[20px] border border-[#162e4b]/10 bg-[#f7f5f0] p-4 text-sm text-[#162e4b]/80">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#62868d]">Sabit bilgiler</p>
            <div class="mt-3 space-y-3">
                <div>
                    <strong class="block text-[#162e4b]">Telefon</strong>
                    <a href="tel:{{ config('iletisim.telefon_link') }}" class="transition hover:text-[#b27829]">{{ config('iletisim.telefon') }}</a>
                </div>
                <div>
                    <strong class="block text-[#162e4b]">E-posta</strong>
                    <a href="mailto:{{ config('iletisim.merkez_eposta') }}" class="transition hover:text-[#b27829]">{{ config('iletisim.merkez_eposta') }}</a>
                </div>
                <div>
                    <strong class="block text-[#162e4b]">Adres</strong>
                    <span>{{ config('site.adres') }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

@if($sayfa->lokasyonlar->isNotEmpty())
    <section class="kurumsal-section-card">
        <p class="kurumsal-eyebrow">Lokasyonlar</p>
        <h2 class="kurumsal-section-title">İletişim noktaları</h2>

        <div class="space-y-4">
            @foreach($sayfa->lokasyonlar as $lokasyon)
                <article class="rounded-[20px] border border-[#162e4b]/10 bg-white p-4 shadow-sm">
                    <div class="grid gap-4 lg:grid-cols-[320px_minmax(0,1fr)] lg:items-start">
                        @if($lokasyon->konum_lat && $lokasyon->konum_lng)
                            <iframe
                                src="https://www.google.com/maps?q={{ $lokasyon->konum_lat }},{{ $lokasyon->konum_lng }}&z=15&output=embed"
                                loading="lazy"
                                class="h-56 w-full rounded-2xl border border-[#162e4b]/10"
                                referrerpolicy="no-referrer-when-downgrade"
                            ></iframe>
                        @else
                            <div class="flex h-56 items-center justify-center rounded-2xl border border-dashed border-[#162e4b]/15 bg-[#f7f5f0] text-sm text-[#62868d]">
                                Harita bilgisi yakında eklenecek
                            </div>
                        @endif

                        <div>
                            <h3 class="text-lg font-bold text-[#162e4b]">{{ $lokasyon->lokasyon_adi }}</h3>
                            <p class="mt-3 text-sm leading-6 text-[#62868d]">{{ $lokasyon->adres }}</p>
                            @if($lokasyon->eposta)
                                <a href="mailto:{{ $lokasyon->eposta }}" class="mt-3 inline-flex text-sm font-semibold text-[#b27829] transition hover:text-[#e95925]">
                                    {{ $lokasyon->eposta }}
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
