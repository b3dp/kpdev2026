@extends('layouts.app')

@section('title', 'E-Kayıt — Online Başvuru')
@section('meta_description', 'Kestanepazarı Derneği öğrenci kayıt başvurusu. Sınıfınızı seçin ve online form doldurun.')
@section('robots', 'index, follow')

@section('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "BreadcrumbList",
  "itemListElement": [
    {"@@type":"ListItem","position":1,"name":"Ana Sayfa","item":"{{ url('/') }}"},
    {"@@type":"ListItem","position":2,"name":"E-Kayıt","item":"{{ route('ekayit.index') }}"}
  ]
}
</script>
@endsection

@section('content')
<div class="pt-[106px] lg:pt-[114px]">
  <section class="border-b border-primary/10 bg-white">
    <div class="mx-auto flex max-w-7xl flex-wrap items-end justify-between gap-4 px-6 py-7">
      <div>
        <div class="mb-4 flex items-center gap-1.5 text-[13px] font-jakarta text-teal-muted">
          <a href="{{ route('home') }}" class="transition-colors hover:text-accent">Ana Sayfa</a>
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="text-primary/25">
            <path stroke-linecap="round" d="M9 5l7 7-7 7"/>
          </svg>
          <span class="font-medium text-primary">E-Kayıt</span>
        </div>

        <p class="mb-1.5 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.18em] text-accent">Online Başvuru</p>
        <h1 class="font-baskerville text-[clamp(24px,3vw,34px)] font-bold text-primary">E-Kayıt</h1>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-primary/10 bg-white px-4 py-2.5 font-jakarta text-[13px] font-semibold text-primary transition-colors hover:bg-bg-soft hover:text-accent">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Ana Sayfa
        </a>
        <a href="{{ route('bagis.index') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-orange-cta px-4 py-2.5 font-jakarta text-[13px] font-bold text-white transition-colors hover:bg-[#c94620]">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
          </svg>
          Bağış Yap
        </a>
      </div>
    </div>
  </section>

  <section class="mx-auto max-w-7xl px-6 pb-20 pt-10">
    @if (session('error'))
      <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 font-jakarta text-sm text-red-700">
        {{ session('error') }}
      </div>
    @endif

    @if (session('info'))
      <div class="mb-6 rounded-2xl border border-primary/10 bg-white px-4 py-3 font-jakarta text-sm text-primary/80">
        {{ session('info') }}
      </div>
    @endif

    @if(!$aktifDonem)
      <div class="mx-auto max-w-3xl px-6 py-16 text-center">
        <div class="mx-auto mb-5 flex h-[72px] w-[72px] items-center justify-center rounded-full border border-primary/10 bg-bg-soft">
          <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.3)" stroke-width="1.5">
            <rect x="3" y="4" width="18" height="18" rx="2"/>
            <path d="M16 2v4M8 2v4M3 10h18"/>
          </svg>
        </div>
        <h2 class="mb-3 font-baskerville text-2xl font-bold text-primary">Kayıt Dönemi Kapalı</h2>
        <p class="font-jakarta text-sm text-teal-muted">Şu an aktif bir kayıt dönemi bulunmamaktadır. Yeni dönem açıldığında duyurulacaktır.</p>
        <a href="{{ route('home') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-primary px-7 py-3 font-jakarta text-sm font-bold text-cream transition-colors hover:bg-primary-dark">Ana Sayfaya Dön</a>
      </div>
    @elseif($aktifDonem->bitis && now()->isAfter($aktifDonem->bitis))
      <div class="mx-auto max-w-3xl px-6 py-16 text-center">
        <div class="mx-auto mb-5 flex h-[72px] w-[72px] items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-600">
          <svg width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" d="M12 8v4l2.5 2.5"/>
            <circle cx="12" cy="12" r="9"/>
          </svg>
        </div>
        <h2 class="mb-3 font-baskerville text-2xl font-bold text-primary">Kayıt Süresi Doldu</h2>
        <p class="font-jakarta text-sm text-teal-muted">
          {{ $aktifDonem->ad }} kayıt dönemi {{ $aktifDonem->bitis->translatedFormat('d F Y') }} tarihinde sona erdi.
        </p>
        <a href="{{ route('home') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-primary px-7 py-3 font-jakarta text-sm font-bold text-cream transition-colors hover:bg-primary-dark">Ana Sayfaya Dön</a>
      </div>
    @else
      <div class="mb-10 flex flex-wrap items-center gap-3 rounded-[14px] bg-[linear-gradient(135deg,#162E4B,#28484C)] px-[22px] py-[18px]">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#EBDFB5" stroke-width="2" class="shrink-0">
          <path stroke-linecap="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="flex-1">
          <p class="font-jakarta text-[13.5px] leading-relaxed text-cream/85">
            Kayıt olmak istediğiniz sınıfı seçin.
            @if($aktifDonem->bitis)
              <strong class="text-cream">Son Başvuru: {{ $aktifDonem->bitis->translatedFormat('d F Y') }}</strong>
            @endif
          </p>
        </div>
      </div>

      @foreach($gruplar as $grup)
        <p class="grup-baslik">{{ $grup['ad'] }}</p>
        <div class="mb-10 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
          @foreach($grup['siniflar'] as $sinif)
            <a href="{{ route('ekayit.form', ['sinif_id' => $sinif['id']]) }}" class="sinif-kart">
              <span class="sk-num">{{ $sinif['kart_baslik'] }}</span>
              <span class="sk-label">{{ $sinif['kart_alt_baslik'] }}</span>
              <span class="sk-badge">{{ $sinif['kart_rozet'] }}</span>
              <span class="sk-name">{{ $sinif['kart_ad'] }}</span>
              <svg class="sk-arrow" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" d="M9 5l7 7-7 7"/>
              </svg>
            </a>
          @endforeach
        </div>
      @endforeach

      @if($siniflar->isEmpty())
        <div class="rounded-2xl border border-dashed border-primary/10 bg-white px-6 py-14 text-center">
          <p class="font-jakarta text-sm text-teal-muted">Bu dönem için sınıf tanımlanmamış.</p>
        </div>
      @endif
    @endif
  </section>
</div>
@endsection
