@extends('layouts.ekayit-landing')

@section('title', 'Başvurunuz Alındı!')
@section('meta_description', 'E-Kayıt başvurunuz başarıyla alınmıştır.')
@section('robots', 'noindex, nofollow')

@section('content')
@php
  $sinifBaslik = $kayit?->sinif?->ad ?? 'E-Kayıt Başvurusu';
  $ogrenciAdSoyad = $kayit?->ogrenciBilgisi?->ad_soyad ?? '—';
  $kurumAdi = $kayit?->sinif?->kurum?->ad ?? 'Kestanepazarı Hatay Kur\'an Kursu';
  $veliTelefon = $kayit?->veliBilgisi?->telefon_1 ?? '—';
@endphp

<main class="mx-auto max-w-[680px] px-6 pb-20 pt-12">
  <div class="mb-9 text-center">
    <div class="onay-daire">
      <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
        <path class="onay-check" d="M9 18l7 7L27 12" stroke="#EBDFB5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>

    <h1 class="fade-up-1 mb-3 font-baskerville text-[clamp(22px,3vw,30px)] font-bold text-primary">Başvurunuz Alındı!</h1>
    <p class="fade-up-2 mx-auto max-w-[560px] font-jakarta text-[15px] leading-[1.7] text-teal-muted">
      Kayıt başvurunuz <strong class="text-primary">{{ $kurumAdi }}</strong>'na iletilmiştir.
      <strong class="text-primary">baris@b3dp.com</strong> adresine ve
      <strong class="text-primary">{{ $veliTelefon }}</strong> numarasına SMS bilgilendirme gönderilmiştir.
    </p>
  </div>

  <div class="fade-up-3 mb-5 overflow-hidden rounded-2xl border border-primary/10 bg-white">
    <div class="flex items-center justify-between bg-[linear-gradient(135deg,#162E4B,#28484C)] px-[22px] py-4">
      <div>
        <p class="mb-1 font-jakarta text-[11px] font-semibold uppercase tracking-[0.18em] text-cream/50">Başvuru Özeti</p>
        <p class="font-baskerville text-base font-bold text-cream">{{ $sinifBaslik }}</p>
      </div>
      <div class="text-right">
        <p class="mb-1 font-jakarta text-[11px] text-cream/50">Başvuru No</p>
        <p class="font-jakarta text-[14px] font-bold text-accent">#KP-{{ $kayit?->id ?? '—' }}</p>
      </div>
    </div>

    <div class="flex flex-col gap-3 px-[22px] py-5">
      <div class="flex items-center justify-between border-b border-primary/7 pb-3">
        <span class="font-jakarta text-[13px] text-teal-muted">Başvuru Tarihi</span>
        <span class="font-jakarta text-[13px] font-semibold text-primary">{{ $kayit?->created_at?->translatedFormat('d F Y, H:i') ?? now()->translatedFormat('d F Y, H:i') }}</span>
      </div>
      <div class="flex items-center justify-between border-b border-primary/7 pb-3">
        <span class="font-jakarta text-[13px] text-teal-muted">Öğrenci</span>
        <span class="font-jakarta text-[13px] font-semibold text-primary">{{ $ogrenciAdSoyad }}</span>
      </div>
      <div class="flex items-center justify-between">
        <span class="font-jakarta text-[13px] text-teal-muted">Durum</span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3 py-1 font-jakarta text-[12px] font-bold text-accent">
          <span class="inline-block h-1.5 w-1.5 rounded-full bg-accent"></span>
          İnceleme Bekliyor
        </span>
      </div>
    </div>
  </div>

  <div class="fade-up-4 mb-5 rounded-2xl border border-primary/10 bg-white p-[22px]">
    <h3 class="mb-4 font-baskerville text-[16px] font-bold text-primary">Başvuru Süreci</h3>

    @foreach([
      ['durum' => 'done',   'baslik' => 'Başvuru Alındı',   'aciklama' => 'Formunuz sisteme iletildi ve kayıt altına alındı.', 'badge' => 'Tamamlandı', 'badge_class' => 'text-green-700 bg-green-50'],
      ['durum' => 'active', 'baslik' => 'Belge İncelemesi', 'aciklama' => 'Dernek yönetimi başvuru bilgilerini inceleyecektir.', 'badge' => 'Beklemede', 'badge_class' => 'text-accent bg-accent/10'],
      ['durum' => 'wait',   'baslik' => 'Bilgilendirme',    'aciklama' => 'Başvuru sonucu e-posta ve telefon ile bildirilecektir.', 'badge' => 'Bekleniyor', 'badge_class' => 'text-primary/40 bg-primary/7'],
      ['durum' => 'wait',   'baslik' => 'Kayıt Onayı',      'aciklama' => 'Başvurunuz onaylandıktan sonra kaydınız aktif olacaktır.', 'badge' => 'Bekleniyor', 'badge_class' => 'text-primary/40 bg-primary/7'],
    ] as $adim)
      <div class="durum-adim">
        <div class="durum-dot {{ $adim['durum'] }}">
          @if($adim['durum'] === 'done')
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#15803d" stroke-width="2.5"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
          @elseif($adim['durum'] === 'active')
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4l3 3"/></svg>
          @else
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.35)" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
          @endif
        </div>

        <div class="flex-1">
          <p class="mb-0.5 font-jakarta text-[14px] font-bold text-primary">{{ $adim['baslik'] }}</p>
          <p class="font-jakarta text-[12.5px] text-teal-muted">{{ $adim['aciklama'] }}</p>
        </div>

        <span class="ml-auto shrink-0 whitespace-nowrap rounded-full px-[10px] py-[3px] font-jakarta text-[11px] font-bold {{ $adim['badge_class'] }}">
          {{ $adim['badge'] }}
        </span>
      </div>
    @endforeach
  </div>

  <div class="fade-up-4 mb-7 flex items-start gap-3 rounded-[14px] border border-primary/10 bg-white px-5 py-[18px]">
    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2" class="shrink-0 mt-0.5"><path stroke-linecap="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
    <div>
      <p class="mb-1 font-jakarta text-[13.5px] font-bold text-primary">Bilgilendirme Kanalları</p>
      <p class="font-jakarta text-[13px] leading-[1.65] text-teal-muted">Başvuru durumunuz hakkında formdaki e-posta adresinize ve telefon numaranıza bilgi gönderilecektir.</p>
    </div>
  </div>

  <div class="fade-up-5 flex flex-wrap justify-center gap-3">
    <a href="https://www.kestanepazari.org.tr" class="inline-flex items-center gap-2 rounded-[11px] bg-primary px-7 py-[13px] font-jakarta text-sm font-bold text-cream no-underline transition-colors hover:bg-primary-dark">
      <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Ana Sayfa
    </a>
    <a href="https://www.kestanepazari.org.tr/bagis" class="inline-flex items-center gap-2 rounded-[11px] bg-orange-cta px-6 py-[13px] font-jakarta text-sm font-bold text-white no-underline transition-colors hover:bg-[#c94620]">
      <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
      Bağış Yap
    </a>
  </div>
</main>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const payload = {
        kayit_id: @json($kayit?->id),
        sinif_adi: @json($sinifBaslik),
        kurum_adi: @json($kurumAdi),
      };

      window.kpCerez?.trackEvent?.('ekayit_tamamlandi', payload, 'analitik');
      window.kpCerez?.trackEvent?.('ekayit_tamamlandi', payload, 'pazarlama');
    }, { once: true });
  </script>
@endpush
