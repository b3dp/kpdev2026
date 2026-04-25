@php
  $telefon = config('site.telefon');
  $telefon_link = preg_replace('/\D+/', '', (string) $telefon);
  $eposta = config('site.eposta');
@endphp

<footer class="mt-12">
  <div class="h-[3px] bg-[linear-gradient(to_right,transparent,#B27829_30%,#B27829_70%,transparent)] opacity-70"></div>

  <div class="bg-cream">
    <div class="mx-auto max-w-7xl px-6 py-14">
      <div class="grid grid-cols-2 gap-10 lg:grid-cols-4">
        <div class="col-span-2 lg:col-span-1">
          <a href="{{ route('home') }}" class="mb-5 flex items-center gap-3 no-underline">
            <span class="logo-kf flex h-[42px] w-[42px] items-center justify-center rounded-xl text-xl">K</span>
            <span>
              <span class="block font-baskerville text-base font-bold leading-tight text-primary">{{ config('site.ad') }}</span>
              <span class="mt-0.5 block text-[11px] text-primary/50">{{ config('site.aciklama') }}</span>
            </span>
          </a>

          <p class="mb-6 max-w-[260px] text-[13px] leading-7 text-primary/55">
            1966'dan bu yana Seferihisar genclerinin egitim hayatina destek oluyoruz.
          </p>

          <div class="flex gap-2">
            @if (config('site.facebook'))
              <a href="{{ config('site.facebook') }}" class="social-btn" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
              </a>
            @endif
            @if (config('site.instagram'))
              <a href="{{ config('site.instagram') }}" class="social-btn" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor" stroke="none"/></svg>
              </a>
            @endif
            @if (config('site.x'))
              <a href="{{ config('site.x') }}" class="social-btn" target="_blank" rel="noopener noreferrer" aria-label="X">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
              </a>
            @endif
          </div>
        </div>

        <div>
          <h3 class="col-head">Kurumsal</h3>
          <ul class="flex list-none flex-col gap-2.5">
            <li><a href="{{ route('kurumsal.show', ['slug' => 'hakkimizda']) }}" class="footer-link">Hakkımızda</a></li>
            <li><a href="{{ route('kurumsal.show', ['slug' => 'yonetim-kurulu']) }}" class="footer-link">Yönetim Kurulu</a></li>
            <li><a href="{{ route('kurumsal.show', ['slug' => 'tuzuk']) }}" class="footer-link">Dernek Tüzüğü</a></li>
            <li><a href="{{ route('kurumsal.show', ['slug' => 'faaliyet-raporlari']) }}" class="footer-link">Faaliyet Raporları</a></li>
            <li><a href="{{ route('kurumsal.show', ['slug' => 'sss']) }}" class="footer-link">Sıkça Sorulan Sorular</a></li>
          </ul>
        </div>

        <div>
          <h3 class="col-head">Faaliyetler</h3>
          <ul class="flex list-none flex-col gap-2.5">
            <li><a href="{{ route('haberler.index') }}" class="footer-link">Haberler</a></li>
            <li><a href="{{ route('etkinlikler.index') }}" class="footer-link">Etkinlikler</a></li>
            <li><a href="{{ route('bagis.index') }}" class="footer-link">Bağış Yap</a></li>
            <li><a href="{{ route('mezunlar.index') }}" class="footer-link">Mezunlar</a></li>
            <li><a href="{{ route('ekayit.index') }}" class="footer-link">E-Kayıt</a></li>
          </ul>
        </div>

        <div>
          <h3 class="col-head">İletişim</h3>
          <div class="flex flex-col gap-4">
            <a href="tel:{{ $telefon_link }}" class="contact-row">
              <svg class="cicon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
              <span>{{ $telefon }}</span>
            </a>

            <a href="mailto:{{ $eposta }}" class="contact-row">
              <svg class="cicon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <span>{{ $eposta }}</span>
            </a>

            <div class="contact-row">
              <svg class="cicon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <span>{{ config('site.adres') }}</span>
            </div>
          </div>

          <a href="{{ route('bagis.index') }}" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-orange-cta px-4 py-2.5 text-center text-[12.5px] font-bold text-white no-underline transition hover:bg-[#c94620]">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            BAĞIŞ YAP
          </a>
        </div>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-6">
      <div class="h-px bg-primary/12"></div>
    </div>
  </div>

  <div class="bg-[#DED099]">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-6 py-3.5">
      <p class="text-xs text-primary/60">© {{ date('Y') }} {{ config('site.ad') }}. Tüm hakları saklıdır.</p>

      <div class="flex flex-wrap items-center gap-4">
        <a href="{{ route('kurumsal.show', ['slug' => 'gizlilik-politikasi']) }}" class="bottom-link">Gizlilik Politikası</a>
        <span class="text-[10px] text-primary/20">|</span>
        <a href="{{ route('kurumsal.show', ['slug' => 'cerez-politikasi']) }}" class="bottom-link">Çerez Politikası</a>
        <span class="text-[10px] text-primary/20">|</span>
        <a href="{{ route('iletisim.index') }}" class="bottom-link">İletişim</a>
      </div>
    </div>
  </div>
</footer>
