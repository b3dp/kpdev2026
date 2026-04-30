@extends('layouts.ekayit-landing')

@section('title', 'E-Kayıt — Online Başvuru')
@section('meta_description', 'Kestanepazarı Derneği öğrenci kayıt başvurusu. Sınıfınızı seçin ve online formu doldurun.')
@section('robots', 'index, follow')

@section('content')
<div style="max-width: 960px; margin: 0 auto; padding: 48px 24px 80px;">

  {{-- Başlık --}}
  <div style="text-align: center; margin-bottom: 48px;">
    <p style="font-size: 12px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #e85d21; margin-bottom: 10px;">
      Online Başvuru
    </p>
    <h1 class="font-baskerville" style="font-size: clamp(28px, 4vw, 42px); font-weight: 700; color: #162E4B; line-height: 1.2; margin-bottom: 14px;">
      E-Kayıt Başvurusu
    </h1>
    @if($aktifDonem)
      <p style="font-size: 15px; color: #5a7284; max-width: 480px; margin: 0 auto;">
        {{ $aktifDonem->ad }} dönemi için başvurmak istediğiniz sınıfı aşağıdan seçin.
        @if($aktifDonem->bitis)
          <br><strong style="color: #162E4B;">Son başvuru: {{ $aktifDonem->bitis->translatedFormat('d F Y') }}</strong>
        @endif
      </p>
    @endif
  </div>

  {{-- Hata / Bilgi mesajları --}}
  @if(session('error'))
    <div style="margin-bottom: 24px; padding: 14px 18px; border-radius: 12px; border: 1px solid #fecaca; background: #fef2f2; font-size: 14px; color: #dc2626;">
      {{ session('error') }}
    </div>
  @endif
  @if(session('info'))
    <div style="margin-bottom: 24px; padding: 14px 18px; border-radius: 12px; border: 1px solid #d1fae5; background: #f0fdf4; font-size: 14px; color: #15803d;">
      {{ session('info') }}
    </div>
  @endif

  {{-- Kayıt dönemi kapalı --}}
  @if(!$aktifDonem)
    <div style="text-align: center; padding: 64px 24px;">
      <div style="width: 72px; height: 72px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.3)" stroke-width="1.5">
          <rect x="3" y="4" width="18" height="18" rx="2"/>
          <path d="M16 2v4M8 2v4M3 10h18"/>
        </svg>
      </div>
      <h2 class="font-baskerville" style="font-size: 24px; font-weight: 700; color: #162E4B; margin-bottom: 10px;">Kayıt Dönemi Kapalı</h2>
      <p style="font-size: 14px; color: #5a7284;">Şu an aktif bir kayıt dönemi bulunmamaktadır. Yeni dönem açıldığında duyurulacaktır.</p>
    </div>

  {{-- Kayıt süresi doldu --}}
  @elseif($aktifDonem->bitis && now()->isAfter($aktifDonem->bitis))
    <div style="text-align: center; padding: 64px 24px;">
      <div style="width: 72px; height: 72px; border-radius: 50%; background: #fffbeb; border: 1px solid #fde68a; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
        <svg width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="#d97706" stroke-width="1.8">
          <path stroke-linecap="round" d="M12 8v4l2.5 2.5"/>
          <circle cx="12" cy="12" r="9"/>
        </svg>
      </div>
      <h2 class="font-baskerville" style="font-size: 24px; font-weight: 700; color: #162E4B; margin-bottom: 10px;">Kayıt Süresi Doldu</h2>
      <p style="font-size: 14px; color: #5a7284;">{{ $aktifDonem->ad }} kayıt dönemi {{ $aktifDonem->bitis->translatedFormat('d F Y') }} tarihinde sona erdi.</p>
    </div>

  {{-- Aktif dönem — sınıf listesi --}}
  @else

    {{-- Duyuru bandı --}}
    <div style="display: flex; align-items: flex-start; gap: 14px; background: linear-gradient(135deg, #162E4B, #28484C); border-radius: 14px; padding: 18px 22px; margin-bottom: 40px;">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#EBDFB5" stroke-width="2" style="flex-shrink:0; margin-top:2px;">
        <path stroke-linecap="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p style="font-size: 13.5px; color: rgba(235,223,181,0.9); line-height: 1.6; margin: 0;">
        Kayıt olmak istediğiniz sınıfı seçin, ardından başvuru formunu doldurun.
        @if($aktifDonem->bitis)
          <strong style="color: #EBDFB5;">Son Başvuru: {{ $aktifDonem->bitis->translatedFormat('d F Y') }}</strong>
        @endif
      </p>
    </div>

    @php($varsayilanGorsel = 'https://cdn.kestanepazari.org.tr/logo.png')

    @foreach($gruplar as $grup)
      @if($grup['ad'])
        <p style="font-size: 11px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #8fa3b0; margin: 0 0 16px;">
          {{ $grup['ad'] }}
        </p>
      @endif

      <div class="grid grid-cols-1 sm:grid-cols-2" style="gap: 16px; margin-bottom: 36px;">
        @foreach($grup['siniflar'] as $sinif)
          <a href="{{ route('ekayit.form', ['sinif_id' => $sinif['id']]) }}"
             style="border-radius: 16px; border: 1px solid #e5e7eb; background: #fff; overflow: hidden; text-decoration: none; display: block; box-shadow: 0 1px 3px rgba(16,24,40,0.06); transition: transform 0.15s, box-shadow 0.15s;"
             onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 20px rgba(16,24,40,0.12)';"
             onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 1px 3px rgba(16,24,40,0.06)';">
            {{-- 1:1 Görsel --}}
            <div style="position: relative; width: 100%; padding-top: 100%; background: #f8fafc;">
              <img
                src="{{ $sinif['kart_gorsel'] ?: $varsayilanGorsel }}"
                alt="{{ $sinif['kart_ad'] }}"
                loading="lazy"
                style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;"
              >
            </div>
            {{-- İsim --}}
            <div style="padding: 14px 16px; text-align: center; background: #fff; border-top: 1px solid #f3f4f6;">
              <span style="font-size: 14px; font-weight: 700; color: #162E4B; line-height: 1.4; display: block;">
                {{ $sinif['kart_ad'] }}
              </span>
            </div>
          </a>
        @endforeach
      </div>

      @if(!$loop->last)
        <hr style="border: none; border-top: 1px solid #eef0f3; margin-bottom: 36px;">
      @endif
    @endforeach

    @if($siniflar->isEmpty())
      <div style="text-align: center; padding: 64px 24px; border-radius: 16px; border: 1px dashed #d0d9e0; background: #fff;">
        <p style="font-size: 14px; color: #5a7284;">Bu dönem için sınıf tanımlanmamış.</p>
      </div>
    @endif

  @endif

</div>
@endsection
