@extends('layouts.checkout')

@php
    $fiyatTipi = $bagisTuru->fiyat_tipi?->value ?? $bagisTuru->fiyat_tipi;
    $ozellik = $bagisTuru->ozellik?->value ?? $bagisTuru->ozellik;
  $adetModuAktif = $fiyatTipi === 'sabit' && $ozellik !== 'buyukbas_kurban';

    $oneriTutarlarHam = is_array($bagisTuru->oneri_tutarlar)
        ? $bagisTuru->oneri_tutarlar
        : json_decode($bagisTuru->oneri_tutarlar ?? '[]', true);

    $oneriTutarlar = collect($oneriTutarlarHam)
        ->filter(fn ($tutar) => is_numeric($tutar) && (float) $tutar > 0)
        ->map(fn ($tutar) => (float) $tutar)
        ->values()
        ->all();

    $varsayilanTutar = (float) ($bagisTuru->fiyat ?? $bagisTuru->minimum_tutar ?? 100);

    if (empty($oneriTutarlar)) {
      $oneriTutarlar = collect([$varsayilanTutar, 250, 500, 1000])
            ->filter(fn ($tutar) => (float) $tutar > 0)
            ->unique()
            ->values()
            ->all();
    }

    $onerilenAdetler = [1, 5, 10];

    $ilkTutar = (float) ($oneriTutarlar[0] ?? $varsayilanTutar);

    $aktifTurKey = match (true) {
        $bagisTuru->slug === 'zekat' => 'zekat',
        $bagisTuru->slug === 'fitre' => 'fitre',
        $ozellik === 'kucukbas_kurban' => 'kucukbas',
        $ozellik === 'buyukbas_kurban' => 'buyukbas',
        default => 'normal',
    };

    $turLinkleri = [
      'zekat' => route('bagis.show', 'zekat'),
      'normal' => route('bagis.show', 'genel-bagis'),
      'kucukbas' => route('bagis.show', 'kucukbas-kurban'),
      'buyukbas' => route('bagis.show', 'buyukbas-kurban-hissesi'),
      'fitre' => route('bagis.show', 'fitre'),
    ];

    $sepetAdet = count($sepet ?? []);
    $sepetToplam = (float) collect($sepet ?? [])->sum(fn ($satir) => (float) ($satir['toplam'] ?? 0));
    $testOdemeAktif = $testOdemeAktif ?? false;
    $testKartlari = $testKartlari ?? [];
@endphp

@section('title', $bagisTuru->seo_baslik ?? $bagisTuru->ad.' — Bağış Yap')
@section('meta_description', $bagisTuru->meta_description ?? $bagisTuru->aciklama)
@section('robots', 'index, follow')
@section('og_image', $bagisTuru->gorsel_kare
    ? 'https://cdn.kestanepazari.org.tr/'.ltrim($bagisTuru->gorsel_kare, '/')
    : asset('img/og-bagis.jpg'))

@section('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Product",
  "name": @json($bagisTuru->ad),
  "description": @json($bagisTuru->aciklama),
  "url": @json(route('bagis.show', $bagisTuru->slug)),
  "brand": {"@@type": "Organization", "name": "Kestanepazarı Derneği"}
  @if($fiyatTipi === 'sabit' && $bagisTuru->fiyat)
  ,"offers": {
    "@@type": "Offer",
    "price": @json((string) $bagisTuru->fiyat),
    "priceCurrency": "TRY",
    "availability": "https://schema.org/InStock"
  }
  @endif
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "BreadcrumbList",
  "itemListElement": [
    {"@@type": "ListItem", "position": 1, "name": "Ana Sayfa", "item": "{{ url('/') }}"},
    {"@@type": "ListItem", "position": 2, "name": "Bağış", "item": "{{ route('bagis.index') }}"},
    {"@@type": "ListItem", "position": 3, "name": @json($bagisTuru->ad)}
  ]
}
</script>
@endsection

@section('content')
<div style="padding-top:0;background:#fff;border-bottom:1px solid rgba(22,46,75,.07);">
  <div style="max-width:1280px;margin:0 auto;padding:16px 24px;">
    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
      <a href="{{ route('home') }}" class="font-jakarta text-[13px] text-teal-muted no-underline transition-colors hover:text-accent">Ana Sayfa</a>
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
      <a href="{{ route('bagis.index') }}" class="font-jakarta text-[13px] text-teal-muted no-underline transition-colors hover:text-accent">Bağış</a>
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="rgba(22,46,75,.25)" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
      <span id="breadcrumb-tur" class="font-jakarta text-[13px] font-medium text-primary">{{ $bagisTuru->ad }}</span>
    </div>
  </div>
</div>

<div style="max-width:1280px;margin:0 auto;padding:36px 24px 80px;">
  <div class="grid gap-8 lg:grid-cols-3">
    <div style="grid-column:span 2;">
      <div id="tur-tablar" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:28px;">
        <a href="{{ $turLinkleri['zekat'] }}" class="tur-tab {{ $aktifTurKey === 'zekat' ? 'active' : '' }}">Zekat</a>
        <a href="{{ $turLinkleri['normal'] }}" class="tur-tab {{ $aktifTurKey === 'normal' ? 'active' : '' }}">Normal Bağış</a>
        <a href="{{ $turLinkleri['kucukbas'] }}" class="tur-tab {{ $aktifTurKey === 'kucukbas' ? 'active' : '' }}">Küçükbaş Kurban</a>
        <a href="{{ $turLinkleri['buyukbas'] }}" class="tur-tab {{ $aktifTurKey === 'buyukbas' ? 'active' : '' }}">Büyükbaş Kurban</a>
        <a href="{{ $turLinkleri['fitre'] }}" class="tur-tab {{ $aktifTurKey === 'fitre' ? 'active' : '' }}">Fitre</a>
      </div>

      <div id="bagis-form"
           data-slug="{{ $bagisTuru->slug }}"
           data-baslik="{{ $bagisTuru->ad }}"
           data-aciklama="{{ $bagisTuru->aciklama }}"
         data-fiyat-tipi="{{ $fiyatTipi }}"
         data-adet-modu="{{ $adetModuAktif ? '1' : '0' }}"
         data-birim-fiyat="{{ (float) $varsayilanTutar }}"
           data-init-tur="{{ $aktifTurKey }}"
           data-sepet='@json($sepet)'
           data-sepet-url="{{ route('bagis.sepete-ekle') }}"
           data-sepetten-cikar-url="{{ url('/bagis/sepetten-cikar') }}"
           data-odeme-url="{{ route('bagis.odeme') }}"
           data-test-modu="{{ $testOdemeAktif ? '1' : '0' }}"
           style="background:#fff;border-radius:16px;border:1px solid rgba(22,46,75,.08);overflow:hidden;">

        <div style="padding:24px 24px 0;">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
            <div>
              <h1 id="tur-baslik" style="font-family:'Libre Baskerville',serif;font-weight:700;font-size:22px;color:#162E4B;">{{ $bagisTuru->ad }}</h1>
              <p id="tur-aciklama" style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;color:#62868D;margin-top:3px;">{{ $bagisTuru->aciklama }}</p>
            </div>
          </div>
        </div>

        <div style="padding:20px 24px 28px;display:flex;flex-direction:column;gap:20px;">
          <div>
            <p id="bagis-miktar-etiketi" style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:600;color:#162E4B;margin-bottom:10px;">{{ $aktifTurKey === 'buyukbas' ? 'Hisse Tutarı' : ($adetModuAktif ? 'Adet' : 'Tutar') }} <span style="color:#E95925;">*</span></p>
            <div id="tutar-butonlar" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:10px;">
              @if($aktifTurKey === 'buyukbas')
                <button type="button" class="tutar-btn selected" data-tutar="{{ (int) $varsayilanTutar }}" style="grid-column:span 4;cursor:default;">
                  ₺{{ number_format($varsayilanTutar, 0, ',', '.') }}
                </button>
              @elseif($adetModuAktif)
                @foreach($onerilenAdetler as $i => $adet)
                  <button type="button" class="tutar-btn {{ $i === 0 ? 'selected' : '' }}" data-adet="{{ $adet }}">
                    <span style="display:block;font-family:'Plus Jakarta Sans',sans-serif;font-size:17px;font-weight:700;color:inherit;">{{ $adet }} adet</span>
                    <span style="display:block;margin-top:4px;font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:500;color:inherit;opacity:.72;">₺{{ number_format($varsayilanTutar * $adet, 0, ',', '.') }}</span>
                  </button>
                @endforeach
                <div style="position:relative;">
                  <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;color:rgba(22,46,75,.4);font-weight:600;">#</span>
                  <input id="tutar-manuel" type="number" class="form-input" placeholder="Diğer adet" min="1" max="30" style="padding-left:30px;height:100%;" />
                </div>
              @else
                @foreach($oneriTutarlar as $i => $tutar)
                  <button type="button" class="tutar-btn {{ $i === 0 ? 'selected' : '' }}" data-tutar="{{ (int) $tutar }}">
                    ₺{{ number_format((float) $tutar, 0, ',', '.') }}
                  </button>
                @endforeach
              @endif
            </div>
            @if($aktifTurKey !== 'buyukbas' && ! $adetModuAktif)
            <div style="position:relative;">
              <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;color:rgba(22,46,75,.4);font-weight:600;">₺</span>
              <input id="tutar-manuel" type="number" class="form-input" placeholder="Diğer tutar girin" @if($bagisTuru->minimum_tutar) min="{{ (float) $bagisTuru->minimum_tutar }}" @endif style="padding-left:30px;" />
            </div>
            @endif
          </div>

          <div class="section-divider"></div>

          <div id="kisi-bolum">
            <div id="panel-zekat-normal" style="display:{{ $aktifTurKey === 'kucukbas' || $aktifTurKey === 'buyukbas' ? 'none' : 'block' }};">
              <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:600;color:#162E4B;margin-bottom:10px;">Kimin adına? <span style="color:#E95925;">*</span></p>
              <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px;">
                <div class="radio-opt selected" id="radio-kendi" onclick="selectRadio('kendi')">
                  <div class="radio-circle"><div class="radio-dot"></div></div>
                  <div>
                    <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:600;color:#162E4B;">Kendi adıma</p>
                    <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12.5px;color:#62868D;margin-top:1px;">Ödeyenle sahip aynı kişi</p>
                  </div>
                </div>
                <div class="radio-opt" id="radio-baskasi" onclick="selectRadio('baskasi')">
                  <div class="radio-circle"><div class="radio-dot"></div></div>
                  <div>
                    <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:600;color:#162E4B;">Başkası adına</p>
                    <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12.5px;color:#62868D;margin-top:1px;">Sahip bilgileri + vekalet formu</p>
                  </div>
                </div>
              </div>

              <div id="vekalet-form" style="display:none;background:#F7F5F0;border:1px solid rgba(22,46,75,.1);border-radius:12px;padding:16px;margin-bottom:16px;">
                <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:600;color:#162E4B;margin-bottom:12px;">Sahip Bilgileri</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                  <div class="form-group">
                    <label class="form-label">Ad Soyad <span>*</span></label>
                    <input type="text" name="sahip_ad_soyad" class="form-input" autocomplete="name" placeholder="Sahip adı" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="sahip_telefon" class="form-input" autocomplete="tel" inputmode="numeric" pattern="[0-9]*" placeholder="05XX XXX XX XX" />
                  </div>
                  <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">Vekalet Notu</label>
                    <input type="text" name="sahip_vekalet_notu" class="form-input" placeholder="Ör: Annem Fatma Hanım adına" />
                  </div>
                </div>
              </div>
            </div>

            <div id="panel-kucukbas" style="display:{{ $aktifTurKey === 'kucukbas' ? 'block' : 'none' }};">
              <div class="kisi-kart">
                <div class="kisi-kart-header">
                  <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;font-weight:600;color:#162E4B;display:flex;align-items:center;gap:6px;">
                    <span style="width:24px;height:24px;border-radius:50%;background:#162E4B;color:#EBDFB5;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">1</span>
                    Kurban Sahibi Bilgileri
                  </p>
                  <span class="badge-tur" style="background:rgba(178,120,41,.1);color:#B27829;">Küçükbaş</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                  <div class="form-group">
                    <label class="form-label">Ad Soyad <span>*</span></label>
                    <input type="text" name="kucukbas_ad_soyad" class="form-input" autocomplete="name" placeholder="Ad Soyad" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">E-posta <span>*</span></label>
                    <input type="email" name="kucukbas_eposta" class="form-input" autocomplete="email" placeholder="ornek@mail.com" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Telefon <span>*</span></label>
                    <input type="tel" name="kucukbas_telefon" class="form-input" autocomplete="tel" inputmode="numeric" pattern="[0-9]*" placeholder="05XX XXX XX XX" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">TC Kimlik <span style="color:#62868D;font-weight:400;">(opsiyonel)</span></label>
                    <input type="text" name="kucukbas_tc" class="form-input" inputmode="numeric" maxlength="11" placeholder="XXXXXXXXXXX" />
                  </div>
                </div>
              </div>
            </div>

            <div id="panel-buyukbas" style="display:{{ $aktifTurKey === 'buyukbas' ? 'block' : 'none' }};">
              <div style="margin-bottom:16px;">
                <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:600;color:#162E4B;margin-bottom:10px;">Hisse Sayısı <span style="color:#E95925;">*</span></p>
                <div id="hisse-sayisi-btns" style="display:flex;gap:8px;flex-wrap:wrap;">
                  @foreach(range(1, 7) as $hisse)
                    <button type="button" class="tutar-btn {{ $hisse === 1 ? 'selected' : '' }}" data-hisse="{{ $hisse }}" onclick="setHisse({{ $hisse }})" style="min-width:44px;">{{ $hisse }}</button>
                  @endforeach
                </div>
                <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;margin-top:8px;">Her hisse için ayrı hissedar bilgisi girilecektir.</p>
              </div>
              <div id="hissedar-listesi" style="display:flex;flex-direction:column;gap:12px;"></div>
            </div>
          </div>

          <div class="section-divider"></div>

          <div>
            <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;color:#162E4B;margin-bottom:4px;display:flex;align-items:center;gap:8px;">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/></svg>
              Ödeme Bilgileri
            </p>
            <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12.5px;color:#62868D;margin-bottom:16px;">Makbuz ve bildirimler bu bilgilere gönderilecektir.</p>
            <div id="kopyala-toggle-wrap" style="background:#F7F5F0;border:1px solid rgba(22,46,75,.1);border-radius:10px;padding:12px 14px;margin-bottom:16px;display:{{ $aktifTurKey === 'kucukbas' || $aktifTurKey === 'buyukbas' ? 'block' : 'none' }};">
              <div class="toggle-wrap" onclick="toggleKopyala()">
                <div class="toggle-track" id="kopyala-track"><div class="toggle-thumb"></div></div>
                <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;font-weight:500;color:#162E4B;">Sahip bilgilerimi ödeme bilgisi olarak kullan</span>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
              <div class="form-group">
                <label class="form-label">Ad Soyad <span>*</span></label>
                <input type="text" id="odeyen-ad" name="odeyen_ad_soyad" class="form-input" autocomplete="name" placeholder="Ad Soyad" />
              </div>
              <div class="form-group">
                <label class="form-label">TC Kimlik</label>
                <input type="text" id="odeyen-tc" name="odeyen_tc" class="form-input" inputmode="numeric" maxlength="11" placeholder="XXXXXXXXXXX" />
              </div>
              <div class="form-group">
                <label class="form-label">E-posta <span>*</span></label>
                <input type="email" id="odeyen-email" name="odeyen_eposta" class="form-input" autocomplete="email" placeholder="ornek@mail.com" />
              </div>
              <div class="form-group">
                <label class="form-label">Telefon <span>*</span></label>
                <input type="tel" id="odeyen-tel" name="odeyen_telefon" class="form-input" autocomplete="tel" inputmode="numeric" pattern="[0-9]*" placeholder="05XX XXX XX XX" />
              </div>
            </div>
          </div>

          <div class="section-divider"></div>

          <div>
            <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:600;color:#162E4B;margin-bottom:12px;">Ödeme Yöntemi</p>
            <div style="display:flex;flex-direction:column;gap:8px;">
              <div class="radio-opt selected" id="odeme-albaraka" onclick="selectOdeme('albaraka')">
                <div class="radio-circle"><div class="radio-dot"></div></div>
                <div style="flex:1;">
                  <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:600;color:#162E4B;">Kredi / Banka Kartı</p>
                  <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;margin-top:1px;">@if($testOdemeAktif) Test ödeme modu aktif — gerçek tahsilat yapılmaz @else Albaraka Türk güvencesiyle — Visa, Mastercard, Troy @endif</p>
                </div>
                <div style="display:flex;gap:4px;flex-shrink:0;">
                  <div style="width:32px;height:20px;background:#1a1f71;border-radius:4px;display:flex;align-items:center;justify-content:center;">
                    <span style="font-size:8px;font-weight:800;color:#fff;letter-spacing:-.5px;">VISA</span>
                  </div>
                  <div style="width:32px;height:20px;background:#fff;border:1px solid #e5e7eb;border-radius:4px;display:flex;align-items:center;justify-content:center;">
                    <svg width="18" height="12" viewBox="0 0 38 24"><circle cx="15" cy="12" r="10" fill="#EB001B"/><circle cx="23" cy="12" r="10" fill="#F79E1B"/><path d="M19 5.268A10 10 0 0 1 23 12a10 10 0 0 1-4 6.732A10 10 0 0 1 15 12a10 10 0 0 1 4-6.732z" fill="#FF5F00"/></svg>
                  </div>
                  <div style="width:32px;height:20px;background:#005BAA;border-radius:4px;display:flex;align-items:center;justify-content:center;">
                    <span style="font-size:7px;font-weight:800;color:#fff;letter-spacing:-.3px;">TROY</span>
                  </div>
                </div>
              </div>
            </div>

            @if($testOdemeAktif)
              <div style="margin-top:14px;border:1px solid rgba(22,163,74,.2);background:#ecfdf5;border-radius:14px;padding:14px;">
                <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:700;color:#166534;margin-bottom:4px;">Test ödeme modu</p>
                <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;line-height:1.6;color:#166534;margin-bottom:12px;">Aşağıdaki kartlardan biri ile normal ödeme adımlarını deneyebilirsiniz. Gerçek çekim yapılmaz; sadece başarı / hata akışı simüle edilir.</p>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;">
                  @foreach($testKartlari as $kart)
                    <button type="button"
                            onclick="testKartiniDoldur('{{ $kart['kart_no'] }}')"
                            style="text-align:left;border:1px solid rgba(22,46,75,.08);background:#fff;border-radius:12px;padding:10px 12px;cursor:pointer;">
                      <span style="display:block;font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:700;color:#162E4B;">{{ $kart['etiket'] }}</span>
                      <span style="display:block;font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;margin-top:2px;">{{ $kart['kart_no'] }}</span>
                      <span style="display:inline-flex;margin-top:8px;border-radius:999px;padding:3px 8px;font-family:'Plus Jakarta Sans',sans-serif;font-size:11px;font-weight:700;{{ $kart['sonuc'] === 'basarili' ? 'background:#dcfce7;color:#166534;' : 'background:#fef2f2;color:#b91c1c;' }}">{{ $kart['sonuc'] === 'basarili' ? 'Başarılı' : 'Hata' }}</span>
                    </button>
                  @endforeach
                </div>
              </div>
            @endif

            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:10px;margin-top:14px;">
              <div class="form-group">
                <label class="form-label">Kart Üzerindeki İsim <span>*</span></label>
                <input type="text" id="kart-sahibi" class="form-input" autocomplete="cc-name" placeholder="Ad Soyad" />
              </div>
              <div class="form-group">
                <label class="form-label">Son Kullanma Ay <span>*</span></label>
                <input type="text" id="kart-ay" class="form-input" inputmode="numeric" maxlength="2" placeholder="12" />
              </div>
              <div class="form-group">
                <label class="form-label">Son Kullanma Yıl <span>*</span></label>
                <input type="text" id="kart-yil" class="form-input" inputmode="numeric" maxlength="4" placeholder="2030" />
              </div>
              <div class="form-group" style="grid-column:span 2;">
                <label class="form-label">Kart Numarası <span>*</span></label>
                <input type="text" id="kart-no" class="form-input" inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000" />
              </div>
              <div class="form-group">
                <label class="form-label">CVV <span>*</span></label>
                <input type="text" id="kart-cvv" class="form-input" inputmode="numeric" maxlength="4" autocomplete="cc-csc" placeholder="123" />
              </div>
            </div>
          </div>

          <button type="button" id="sepete-ekle-btn" onclick="sepeteEkle()" class="mt-1 flex w-full cursor-pointer items-center justify-center gap-2.5 rounded-xl border-none bg-orange-cta py-4 font-jakarta text-[15px] font-bold text-white shadow-[0_4px_16px_rgba(233,89,37,.25)] transition-all hover:-translate-y-px hover:bg-[#c94620]">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Sepete Ekle
          </button>

          <button type="button" id="odeme-tamamla-btn" onclick="odemeyiTamamla()" class="flex w-full cursor-pointer items-center justify-center gap-2.5 rounded-xl border-none bg-[#162E4B] py-4 font-jakarta text-[15px] font-bold text-white shadow-[0_4px_16px_rgba(22,46,75,.18)] transition-all hover:-translate-y-px hover:bg-[#0f2238]">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            {{ $testOdemeAktif ? 'Test Ödemeyi Tamamla' : 'Ödemeyi Tamamla' }}
          </button>

          <div id="sepet-mesaj" style="display:none;" class="rounded-xl border px-4 py-3 font-jakarta text-[13px]"></div>

          <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:-8px;">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#62868D" stroke-width="2"><path stroke-linecap="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;">256-bit SSL şifreleme · Güvenli ödeme altyapısı</span>
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="sepet-sticky">
        <div class="sepet-kart" style="margin-bottom:16px;">
          <div class="sepet-header">
            <div style="display:flex;align-items:center;gap:8px;">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#EBDFB5" stroke-width="2"><path stroke-linecap="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;color:#EBDFB5;">Sepet Özeti</span>
            </div>
            <span id="sepet-adet" style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:rgba(235,223,181,.6);">{{ $sepetAdet > 0 ? $sepetAdet.' kalem' : '1 kalem' }}</span>
          </div>
          <div style="padding:16px 20px 12px;border-bottom:1px solid rgba(22,46,75,.06);">
            <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#62868D;margin-bottom:10px;">Seçili Bağış</p>
            <div id="sepet-secili-onizleme" style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
              <div>
                <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;font-weight:600;color:#162E4B;">{{ $bagisTuru->ad }}</p>
                <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;margin-top:2px;">Kendi adıma</p>
              </div>
              <p id="sepet-tutar-goster" style="font-family:'Libre Baskerville',serif;font-weight:700;font-size:16px;color:#162E4B;white-space:nowrap;">₺{{ number_format($ilkTutar, 0, ',', '.') }}</p>
            </div>
          </div>

          <div style="padding:14px 20px 10px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;">
              <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#62868D;">Sepettekiler</p>
              <a href="{{ route('bagis.sepet') }}" style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:700;color:#B27829;text-decoration:none;">Sepete Git</a>
            </div>
            <div id="sepet-icerik" style="display:flex;flex-direction:column;gap:8px;">
              @forelse ($sepet as $satir)
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;border:1px solid rgba(22,46,75,.08);border-radius:12px;padding:10px 12px;background:#fff;">
                  <div>
                    <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:700;color:#162E4B;">{{ $satir['ad'] ?? 'Bağış Kalemi' }}</p>
                    <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:11.5px;color:#62868D;margin-top:2px;">
                      {{ ($satir['adet'] ?? 1) > 1 ? ($satir['adet'].' adet / hisse') : '1 adet' }} · {{ ($satir['sahip_tipi'] ?? 'kendi') === 'baskasi' ? 'Başkası adına' : 'Kendi adıma' }}
                    </p>
                  </div>
                  <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                    <span style="font-family:'Libre Baskerville',serif;font-weight:700;font-size:15px;color:#162E4B;white-space:nowrap;">₺{{ number_format((float) ($satir['toplam'] ?? 0), 2, ',', '.') }}</span>
                    <button type="button" onclick="sepettenCikar({{ (int) ($satir['satir_id'] ?? 0) }})" style="border:none;background:transparent;padding:0;font-family:'Plus Jakarta Sans',sans-serif;font-size:11px;font-weight:700;color:#dc2626;cursor:pointer;">Sil</button>
                  </div>
                </div>
              @empty
                <div id="sepet-bos" style="border:1px dashed rgba(22,46,75,.12);border-radius:12px;padding:12px;background:#F7F5F0;font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;">
                  Henüz sepetinizde ekli bir bağış bulunmuyor. Seçtiğiniz bağışı “Sepete Ekle” ile burada biriktirebilirsiniz.
                </div>
              @endforelse
            </div>
          </div>

          <div style="padding:12px 20px 16px;border-top:1px solid rgba(22,46,75,.06);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
              <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;color:#62868D;">Toplam</span>
              <span id="sepet-toplam" style="font-family:'Libre Baskerville',serif;font-weight:700;font-size:22px;color:#162E4B;">₺{{ number_format($sepetToplam > 0 ? $sepetToplam : $ilkTutar, 2, ',', '.') }}</span>
            </div>
            <button type="button" id="odeme-ozet-btn" onclick="odemeyiTamamla()" class="flex w-full items-center justify-center gap-2 rounded-[10px] border-none bg-orange-cta px-4 py-[13px] font-jakarta text-sm font-bold text-white transition-colors hover:bg-[#c94620]">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              {{ $testOdemeAktif ? 'Test Ödemeyi Tamamla' : 'Ödemeyi Tamamla' }}
            </button>
          </div>
        </div>

        <div style="background:#fff;border-radius:14px;border:1px solid rgba(22,46,75,.08);padding:16px;">
          <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12.5px;font-weight:600;color:#162E4B;margin-bottom:12px;">Neden Kestanepazarı?</p>
          <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach([
              '58 yıllık köklü dernek güvencesi',
              'Anında dijital makbuz (e-posta + SMS)',
              '%100 şeffaf harcama raporu',
              'Albaraka Türk güvenceli ödeme altyapısı',
            ] as $madde)
              <div style="display:flex;align-items:flex-start;gap:8px;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#B27829" stroke-width="2" style="margin-top:2px;flex-shrink:0;"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12.5px;color:#62868D;line-height:1.5;">{{ $madde }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
