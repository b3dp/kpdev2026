@extends('layouts.app')

@section('title', 'E-Kayıt Başvuru Formu')
@section('meta_description', 'Kestanepazarı öğrenci e-kayıt başvuru formu.')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="ekayit-form-wrap px-6 pb-20 pt-5 md:pt-6">
  <div class="mx-auto max-w-4xl">
    <div class="mb-5 flex items-center gap-1.5 text-[13px] font-jakarta text-teal-muted">
      <a href="{{ route('home') }}" class="transition-colors hover:text-accent">Ana Sayfa</a>
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="text-primary/25">
        <path stroke-linecap="round" d="M9 5l7 7-7 7"/>
      </svg>
      <a href="{{ route('ekayit.index') }}" class="transition-colors hover:text-accent">E-Kayıt</a>
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="text-primary/25">
        <path stroke-linecap="round" d="M9 5l7 7-7 7"/>
      </svg>
      <span class="font-medium text-primary">Başvuru Formu</span>
    </div>

    <div class="mb-8 space-y-4">
      @if($sinif)
        <div class="rounded-[22px] border border-accent/20 bg-[linear-gradient(135deg,#fff,#f7f5f0)] px-5 py-4 shadow-sm">
          <p class="font-jakarta text-[11px] font-semibold uppercase tracking-[0.18em] text-accent">Seçilen Sınıf</p>
          <p class="mt-1 font-baskerville text-[clamp(24px,3vw,30px)] font-bold text-primary">{{ $sinif->ad }}</p>
          <p class="mt-1 font-jakarta text-sm text-teal-muted">Başvurunuz bu sınıf için oluşturulacaktır.</p>
        </div>
      @endif

      <div>
        <p class="mb-1.5 font-jakarta text-[12.5px] font-semibold uppercase tracking-[0.18em] text-accent">Online Başvuru</p>
        <h1 class="font-baskerville text-[clamp(24px,3vw,34px)] font-bold text-primary">E-Kayıt Başvuru Formu</h1>
        <p class="mt-2 font-jakarta text-sm text-teal-muted">Öğrenci, veli ve okul bilgilerini eksiksiz doldurup başvurunuzu tamamlayın.</p>
      </div>
    </div>

    @if(session('error'))
      <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 font-jakarta text-sm text-red-700">
        {{ session('error') }}
      </div>
    @endif

    @if($errors->any())
      <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 font-jakarta text-sm text-red-700">
        <p class="mb-2 font-semibold">Lütfen işaretli alanları kontrol edin.</p>
        <ul class="list-inside list-disc space-y-1">
          @foreach($errors->all() as $hata)
            <li>{{ $hata }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div id="adim-gostergesi" class="relative mb-10 flex items-center justify-between">
      <div class="absolute left-0 right-0 top-4 h-0.5 bg-primary/10 z-0"></div>
      @foreach(['Öğrenci', 'Veli', 'Okul', 'Onay'] as $i => $adimAd)
        <div class="relative z-10 flex flex-col items-center gap-2" data-adim="{{ $i + 1 }}">
          <div class="adim-daire {{ $i === 0 ? 'aktif' : '' }}" id="adim-daire-{{ $i + 1 }}">{{ $i + 1 }}</div>
          <span class="hidden font-jakarta text-[11px] font-semibold text-teal-muted sm:block">{{ $adimAd }}</span>
        </div>
      @endforeach
    </div>

    <form action="{{ route('ekayit.store') }}" method="POST" id="ekayit-form" novalidate>
      @csrf

      <input type="hidden" name="donem_id" value="{{ $aktifDonem->id }}">

      <div id="adim-panel-1" class="adim-panel">
        <div class="mb-5 rounded-2xl border border-primary/10 bg-white p-7">
          <h2 class="mb-1 font-baskerville text-xl font-bold text-primary">Öğrenci Bilgileri</h2>
          <p class="mb-6 font-jakarta text-sm text-teal-muted">Başvurusu yapılacak öğrenciye ait temel kimlik ve iletişim bilgileri.</p>

          <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="form-group">
              <label class="form-label">Ad <span>*</span></label>
              <input type="text" name="ogrenci_ad" lang="tr" class="form-input uppercase-input @error('ogrenci_ad') border-red-400 @enderror" autocomplete="given-name" pattern="[A-ZÇĞİÖŞÜa-zçğıöşü\s]+" placeholder="ÖĞRENCİ ADI" required value="{{ old('ogrenci_ad') }}">
            </div>

            <div class="form-group">
              <label class="form-label">Soyad <span>*</span></label>
              <input type="text" name="ogrenci_soyad" lang="tr" class="form-input uppercase-input @error('ogrenci_soyad') border-red-400 @enderror" autocomplete="family-name" pattern="[A-ZÇĞİÖŞÜa-zçğıöşü\s]+" placeholder="ÖĞRENCİ SOYADI" required value="{{ old('ogrenci_soyad') }}">
            </div>

            <div class="form-group">
              <label class="form-label">TC Kimlik No <span>*</span></label>
              <input type="text" name="ogrenci_tc" lang="tr" class="form-input @error('ogrenci_tc') border-red-400 @enderror" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" placeholder="XXXXXXXXXXX" required value="{{ old('ogrenci_tc') }}">
            </div>

            <div class="form-group">
              <label class="form-label">Doğum Tarihi <span>*</span></label>
              <input type="date" name="ogrenci_dogum_tarihi" lang="tr" class="form-input @error('ogrenci_dogum_tarihi') border-red-400 @enderror" autocomplete="bday" required value="{{ old('ogrenci_dogum_tarihi') }}">
            </div>

            <div class="form-group">
              <label class="form-label">Cep Telefonu <span>*</span></label>
              <input type="tel" name="ogrenci_telefon" lang="tr" class="form-input @error('ogrenci_telefon') border-red-400 @enderror" autocomplete="tel" inputmode="numeric" pattern="[0-9\s]+" placeholder="05XX XXX XX XX" required value="{{ old('ogrenci_telefon') }}">
            </div>

            <div class="form-group">
              <label class="form-label">E-posta <span>*</span></label>
              <input type="email" name="ogrenci_eposta" lang="tr" class="form-input @error('ogrenci_eposta') border-red-400 @enderror" autocomplete="email" placeholder="ogrenci@mail.com" required value="{{ old('ogrenci_eposta') }}">
            </div>

            <div class="form-group">
              <label class="form-label">Doğum Yeri</label>
              <input type="text" name="ogrenci_dogum_yeri" lang="tr" class="form-input uppercase-input" pattern="[A-ZÇĞİÖŞÜa-zçğıöşü\s]+" placeholder="İL / İLÇE" value="{{ old('ogrenci_dogum_yeri') }}">
            </div>

            <div class="form-group">
              <label class="form-label">Cinsiyet <span>*</span></label>
              <select name="ogrenci_cinsiyet" lang="tr" class="form-select @error('ogrenci_cinsiyet') border-red-400 @enderror" required>
                <option value="">Seçiniz</option>
                <option value="E" @selected(old('ogrenci_cinsiyet') === 'E')>Erkek</option>
                <option value="K" @selected(old('ogrenci_cinsiyet') === 'K')>Kız</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Baba Adı</label>
              <input type="text" name="ogrenci_baba_adi" lang="tr" class="form-input uppercase-input" pattern="[A-ZÇĞİÖŞÜa-zçğıöşü\s]+" placeholder="BABA ADI" value="{{ old('ogrenci_baba_adi') }}">
            </div>

            <div class="form-group">
              <label class="form-label">Anne Adı</label>
              <input type="text" name="ogrenci_anne_adi" lang="tr" class="form-input uppercase-input" pattern="[A-ZÇĞİÖŞÜa-zçğıöşü\s]+" placeholder="ANNE ADI" value="{{ old('ogrenci_anne_adi') }}">
            </div>

            @if(!$sinif)
              <div class="form-group">
                <label class="form-label">Sınıf <span>*</span></label>
                <select name="sinif_id" lang="tr" class="form-select @error('sinif_id') border-red-400 @enderror" required>
                  <option value="">Sınıf Seçiniz</option>
                  @foreach($sinifSecenekleri as $s)
                    <option value="{{ $s->id }}" @selected(old('sinif_id') == $s->id)>{{ $s->ad }}</option>
                  @endforeach
                </select>
              </div>
            @else
              <input type="hidden" name="sinif_id" value="{{ $sinif->id }}">
            @endif

            <div class="form-group sm:col-span-2">
              <label class="form-label">Adres</label>
              <textarea name="ogrenci_adres" lang="tr" class="form-input uppercase-input min-h-[96px]" placeholder="AÇIK ADRES BİLGİSİ">{{ old('ogrenci_adres') }}</textarea>
            </div>

            <div class="form-group">
              <label class="form-label">İkamet İli</label>
              <select name="ogrenci_ikamet_il" id="ogrenci_ikamet_il" lang="tr" class="form-select @error('ogrenci_ikamet_il') border-red-400 @enderror" data-il-select="ogrenci">
                <option value="">Seçiniz</option>
                @foreach($iller as $il => $etiket)
                  <option value="{{ $il }}" @selected(old('ogrenci_ikamet_il') === $il)>{{ $etiket }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">İkamet İlçesi</label>
              <select name="ogrenci_ikamet_ilce" id="ogrenci_ikamet_ilce" lang="tr" class="form-select @error('ogrenci_ikamet_ilce') border-red-400 @enderror" data-ilce-select="ogrenci" data-selected="{{ old('ogrenci_ikamet_ilce') }}" @disabled(!old('ogrenci_ikamet_il'))>
                <option value="">{{ old('ogrenci_ikamet_il') ? 'İlçe Seçiniz' : 'Önce il seçiniz' }}</option>
                @foreach($ogrenciIlceleri as $ilce => $etiket)
                  <option value="{{ $ilce }}" @selected(old('ogrenci_ikamet_ilce') === $ilce)>{{ $etiket }}</option>
                @endforeach
              </select>
            </div>

            <div class="sm:col-span-2 mt-2 border-t border-primary/10 pt-5">
              <label class="flex items-start gap-3 rounded-2xl border border-primary/10 bg-bg-soft px-4 py-3 cursor-pointer">
                <input type="checkbox" name="eski_tip_kimlik_var" id="eski_tip_kimlik_var" value="1" class="mt-0.5 h-4 w-4 cursor-pointer" {{ old('eski_tip_kimlik_var') ? 'checked' : '' }}>
                <span>
                  <span class="block font-jakarta text-[13px] font-semibold text-primary">Eski tip kimlik kullanıyorum</span>
                  <span class="mt-1 block font-jakarta text-[12px] leading-relaxed text-teal-muted">Eski nüfus cüzdanı kullanan öğrenciler için aşağıdaki nüfus kayıt alanlarını doldurun.</span>
                </span>
              </label>
            </div>

            <div id="eski-kimlik-alanlari" class="contents {{ old('eski_tip_kimlik_var') ? '' : 'hidden' }}">
              <div class="sm:col-span-2">
                <p class="mb-4 font-jakarta text-[12px] font-bold uppercase tracking-[0.16em] text-primary/60">Nüfusa Kayıtlı Olduğu</p>
              </div>

              <div class="form-group">
                <label class="form-label">İl <span>*</span></label>
                <select name="kimlik_kayitli_il" id="kimlik_kayitli_il" lang="tr" class="form-select @error('kimlik_kayitli_il') border-red-400 @enderror" data-il-select="kimlik" data-kimlik-alani="true" {{ old('eski_tip_kimlik_var') ? 'required' : '' }}>
                  <option value="">Seçiniz</option>
                  @foreach($iller as $il => $etiket)
                    <option value="{{ $il }}" @selected(old('kimlik_kayitli_il') === $il)>{{ $etiket }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">İlçe <span>*</span></label>
                <select name="kimlik_kayitli_ilce" id="kimlik_kayitli_ilce" lang="tr" class="form-select @error('kimlik_kayitli_ilce') border-red-400 @enderror" data-ilce-select="kimlik" data-kimlik-alani="true" data-selected="{{ old('kimlik_kayitli_ilce') }}" @disabled(!old('kimlik_kayitli_il')) {{ old('eski_tip_kimlik_var') ? 'required' : '' }}>
                  <option value="">{{ old('kimlik_kayitli_il') ? 'İlçe Seçiniz' : 'Önce il seçiniz' }}</option>
                  @foreach($kimlikIlceleri as $ilce => $etiket)
                    <option value="{{ $ilce }}" @selected(old('kimlik_kayitli_ilce') === $ilce)>{{ $etiket }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group sm:col-span-2">
                <label class="form-label">Mahalle / Köy <span>*</span></label>
                <input type="text" name="kimlik_kayitli_mahalle_koy" lang="tr" class="form-input uppercase-input @error('kimlik_kayitli_mahalle_koy') border-red-400 @enderror" data-kimlik-alani="true" placeholder="MAHALLE VEYA KÖY ADI" {{ old('eski_tip_kimlik_var') ? 'required' : '' }} value="{{ old('kimlik_kayitli_mahalle_koy') }}">
              </div>

              <div class="form-group">
                <label class="form-label">Cilt No <span>*</span></label>
                <input type="text" name="kimlik_cilt_no" lang="tr" class="form-input @error('kimlik_cilt_no') border-red-400 @enderror" data-kimlik-alani="true" inputmode="numeric" pattern="[0-9]+" placeholder="CİLT NO" {{ old('eski_tip_kimlik_var') ? 'required' : '' }} value="{{ old('kimlik_cilt_no') }}">
              </div>

              <div class="form-group">
                <label class="form-label">Aile Sıra No <span>*</span></label>
                <input type="text" name="kimlik_aile_sira_no" lang="tr" class="form-input @error('kimlik_aile_sira_no') border-red-400 @enderror" data-kimlik-alani="true" inputmode="numeric" pattern="[0-9]+" placeholder="AİLE SIRA NO" {{ old('eski_tip_kimlik_var') ? 'required' : '' }} value="{{ old('kimlik_aile_sira_no') }}">
              </div>

              <div class="form-group">
                <label class="form-label">Sıra No <span>*</span></label>
                <input type="text" name="kimlik_sira_no" lang="tr" class="form-input @error('kimlik_sira_no') border-red-400 @enderror" data-kimlik-alani="true" inputmode="numeric" pattern="[0-9]+" placeholder="SIRA NO" {{ old('eski_tip_kimlik_var') ? 'required' : '' }} value="{{ old('kimlik_sira_no') }}">
              </div>
            </div>
          </div>
        </div>

        <button type="button" onclick="sonrakiAdim(1)" class="w-full rounded-xl bg-primary py-4 font-jakarta text-sm font-bold text-cream transition-colors hover:bg-primary-dark flex items-center justify-center gap-2">
          Devam Et — Veli Bilgileri
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
        </button>
      </div>

      <div id="adim-panel-2" class="adim-panel hidden">
        <div class="mb-5 rounded-2xl border border-primary/10 bg-white p-7">
          <h2 class="mb-1 font-baskerville text-xl font-bold text-primary">Veli Bilgileri</h2>
          <p class="mb-6 font-jakarta text-sm text-teal-muted">Başvuru sürecinde kullanılacak veli iletişim ve adres bilgileri.</p>

          <h3 class="mb-4 border-b border-primary/10 pb-2 font-jakarta text-sm font-bold uppercase tracking-[0.16em] text-primary/60">Veli (Anne / Vasi)</h3>
          <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="form-group">
              <label class="form-label">Ad Soyad <span>*</span></label>
              <input type="text" name="veli_ad_soyad" lang="tr" class="form-input uppercase-input @error('veli_ad_soyad') border-red-400 @enderror" autocomplete="name" pattern="[A-ZÇĞİÖŞÜa-zçğıöşü\s]+" placeholder="VELİ AD SOYAD" required value="{{ old('veli_ad_soyad') }}">
            </div>

            <div class="form-group">
              <label class="form-label">Telefon <span>*</span></label>
              <input type="tel" name="veli_telefon" lang="tr" class="form-input @error('veli_telefon') border-red-400 @enderror" autocomplete="tel" inputmode="numeric" pattern="[0-9\s]+" placeholder="05XX XXX XX XX" required value="{{ old('veli_telefon') }}">
            </div>

            <div class="form-group sm:col-span-2">
              <label class="form-label">E-posta <span>*</span></label>
              <input type="email" name="veli_eposta" lang="tr" class="form-input @error('veli_eposta') border-red-400 @enderror" autocomplete="email" placeholder="ornek@mail.com" required value="{{ old('veli_eposta') }}">
            </div>

            <div class="form-group">
              <label class="form-label">İl</label>
              <select name="veli_il" id="veli_il" lang="tr" class="form-select @error('veli_il') border-red-400 @enderror" data-il-select="veli">
                <option value="">Seçiniz</option>
                @foreach($iller as $il => $etiket)
                  <option value="{{ $il }}" @selected(old('veli_il') === $il)>{{ $etiket }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">İlçe</label>
              <select name="veli_ilce" id="veli_ilce" lang="tr" class="form-select @error('veli_ilce') border-red-400 @enderror" data-ilce-select="veli" data-selected="{{ old('veli_ilce') }}" @disabled(!old('veli_il'))>
                <option value="">{{ old('veli_il') ? 'İlçe Seçiniz' : 'Önce il seçiniz' }}</option>
                @foreach($veliIlceleri as $ilce => $etiket)
                  <option value="{{ $ilce }}" @selected(old('veli_ilce') === $ilce)>{{ $etiket }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group sm:col-span-2">
              <label class="form-label">Açık Adres</label>
              <input type="text" name="veli_adres" lang="tr" class="form-input uppercase-input" autocomplete="street-address" placeholder="MAHALLE / SOKAK / NO" value="{{ old('veli_adres') }}">
            </div>
          </div>

        </div>

        <div class="flex gap-3">
          <button type="button" onclick="oncekiAdim(2)" class="flex items-center gap-2 rounded-xl border border-primary/15 bg-white px-6 py-4 font-jakarta text-sm font-semibold text-primary transition-colors hover:bg-bg-soft">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
            Geri
          </button>
          <button type="button" onclick="sonrakiAdim(2)" class="flex-1 rounded-xl bg-primary py-4 font-jakarta text-sm font-bold text-cream transition-colors hover:bg-primary-dark flex items-center justify-center gap-2">
            Devam Et — Okul Bilgileri
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>

      <div id="adim-panel-3" class="adim-panel hidden">
        <div class="mb-5 rounded-2xl border border-primary/10 bg-white p-7">
          <h2 class="mb-1 font-baskerville text-xl font-bold text-primary">Okul Bilgileri</h2>
          <p class="mb-6 font-jakarta text-sm text-teal-muted">Öğrencinin devam ettiği okul bilgilerini girin.</p>

          <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="form-group sm:col-span-2">
              <label class="form-label">Okul Adı <span>*</span></label>
              <input type="text" name="okul_adi" lang="tr" class="form-input uppercase-input @error('okul_adi') border-red-400 @enderror" pattern="[A-ZÇĞİÖŞÜa-zçğıöşü0-9\s\.\-]+" placeholder="ÖĞRENCİNİN GİTTİĞİ OKULUN ADI" required value="{{ old('okul_adi') }}">
            </div>

            <div class="form-group">
              <label class="form-label">Okul İl <span>*</span></label>
              <select name="okul_il" id="okul_il" lang="tr" class="form-select @error('okul_il') border-red-400 @enderror" data-il-select="okul" required>
                <option value="">Seçiniz</option>
                @foreach($iller as $il => $etiket)
                  <option value="{{ $il }}" @selected(old('okul_il') === $il)>{{ $etiket }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Okul İlçe <span>*</span></label>
              <select name="okul_ilce" id="okul_ilce" lang="tr" class="form-select @error('okul_ilce') border-red-400 @enderror" data-ilce-select="okul" data-selected="{{ old('okul_ilce') }}" @disabled(!old('okul_il')) required>
                <option value="">{{ old('okul_il') ? 'İlçe Seçiniz' : 'Önce il seçiniz' }}</option>
                @foreach($okulIlceleri as $ilce => $etiket)
                  <option value="{{ $ilce }}" @selected(old('okul_ilce') === $ilce)>{{ $etiket }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Okul Türü</label>
              <select name="okul_turu" lang="tr" class="form-select">
                <option value="">Seçiniz</option>
                <option value="devlet" @selected(old('okul_turu') === 'devlet')>Devlet</option>
                <option value="ozel" @selected(old('okul_turu') === 'ozel')>Özel</option>
                <option value="imam-hatip" @selected(old('okul_turu') === 'imam-hatip')>İmam Hatip</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Not Ortalaması</label>
              <input type="number" name="not_ortalamasi" lang="tr" class="form-input" min="0" max="100" step="0.01" placeholder="85.50" value="{{ old('not_ortalamasi') }}">
            </div>
          </div>
        </div>

        <div class="flex gap-3">
          <button type="button" onclick="oncekiAdim(3)" class="flex items-center gap-2 rounded-xl border border-primary/15 bg-white px-6 py-4 font-jakarta text-sm font-semibold text-primary transition-colors hover:bg-bg-soft">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
            Geri
          </button>
          <button type="button" onclick="sonrakiAdim(3)" class="flex-1 rounded-xl bg-primary py-4 font-jakarta text-sm font-bold text-cream transition-colors hover:bg-primary-dark flex items-center justify-center gap-2">
            Devam Et — Özet ve Onay
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>

      <div id="adim-panel-4" class="adim-panel hidden">
        <div class="mb-5 rounded-2xl border border-primary/10 bg-white p-7">
          <h2 class="mb-1 font-baskerville text-xl font-bold text-primary">Özet ve Onay</h2>
          <p class="mb-6 font-jakarta text-sm text-teal-muted">Bilgilerinizi son kez kontrol edin ve başvurunuzu tamamlayın.</p>

          <div id="ozet-tablo" class="mb-8 space-y-3 rounded-xl bg-bg-soft p-4 font-jakarta text-sm text-primary"></div>

          <div class="form-group mb-6">
            <label class="form-label">Doğrulama Kodu <span class="text-primary/40">(gerekirse)</span></label>
            <input type="text" name="otp_kodu" lang="tr" class="form-input" autocomplete="one-time-code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="6 HANELİ KOD" value="{{ old('otp_kodu') }}">
          </div>

          <div class="space-y-3 mb-8" id="onay-kutulari">
            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox" name="onay_bilgi" id="onay_bilgi" class="onay-cb mt-0.5 h-4 w-4 cursor-pointer" required {{ old('onay_bilgi') ? 'checked' : '' }}>
              <span class="font-jakarta text-[13px] leading-relaxed text-primary/80">Verilen bilgilerin doğru ve eksiksiz olduğunu beyan ederim. Yanlış bilgi verilmesi durumunda başvurumun iptal edileceğini kabul ediyorum.</span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox" name="onay_kvkk" id="onay_kvkk" class="onay-cb mt-0.5 h-4 w-4 cursor-pointer" required {{ old('onay_kvkk') ? 'checked' : '' }}>
              <span class="font-jakarta text-[13px] leading-relaxed text-primary/80"><a href="{{ route('kurumsal.show', 'kvkk') }}" target="_blank" class="text-accent underline hover:text-orange-cta">KVKK Aydınlatma Metni</a>'ni okudum ve kişisel verilerimin işlenmesine onay veriyorum.</span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox" name="onay_iletisim" id="onay_iletisim" class="onay-cb mt-0.5 h-4 w-4 cursor-pointer" required {{ old('onay_iletisim') ? 'checked' : '' }}>
              <span class="font-jakarta text-[13px] leading-relaxed text-primary/80">Başvuru sürecine ilişkin SMS ve e-posta bildirimleri almayı kabul ediyorum.</span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox" name="onay_tuzuk" id="onay_tuzuk" class="onay-cb mt-0.5 h-4 w-4 cursor-pointer" required {{ old('onay_tuzuk') ? 'checked' : '' }}>
              <span class="font-jakarta text-[13px] leading-relaxed text-primary/80"><a href="{{ route('kurumsal.show', 'tuzuk') }}" target="_blank" class="text-accent underline hover:text-orange-cta">Dernek Tüzüğü</a>'nü ve kayıt koşullarını okudum, kabul ediyorum.</span>
            </label>
          </div>

          <button type="submit" id="basvur-btn" disabled class="w-full rounded-xl bg-orange-cta py-4 font-jakarta text-[15px] font-bold text-white opacity-40 cursor-not-allowed transition-all duration-300 flex items-center justify-center gap-2" title="Lütfen tüm onay kutularını işaretleyin">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Başvuruyu Gönder
          </button>

          <p class="mt-3 text-center font-jakarta text-[12px] text-teal-muted">Tüm onay kutularını işaretlemeniz gerekmektedir.</p>
        </div>

        <button type="button" onclick="oncekiAdim(4)" class="w-full rounded-xl border border-primary/15 bg-white px-6 py-4 font-jakarta text-sm font-semibold text-primary transition-colors hover:bg-bg-soft flex items-center justify-center gap-2">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
          Geri — Okul Bilgileri
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script id="ekayit-ilceler-data" type="application/json">@json($ilceler_haritasi)</script>
@endpush
