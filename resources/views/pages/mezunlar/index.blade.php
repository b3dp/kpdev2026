@extends('layouts.app')

@php
    $aktifMezunSayisi = $istatistikler['aktif_mezun'] ?? 0;
    $hafizMezunSayisi = $istatistikler['hafiz_mezun'] ?? 0;
    $yilAraligi = $istatistikler['yil_araligi'] ?? ((int) now()->year - 1966 + 1);

    $oneCikanMezunlar = $mezunlar->isNotEmpty()
        ? $mezunlar
        : collect([
            (object) ['uye' => (object) ['ad_soyad' => 'Ahmet Yıldırım'], 'mezuniyet_yili' => 2012, 'meslek' => 'Eğitim Danışmanı', 'ikamet_il' => 'İzmir', 'kurum' => (object) ['ad' => 'Kestanepazarı']],
            (object) ['uye' => (object) ['ad_soyad' => 'Mustafa Arslan'], 'mezuniyet_yili' => 2016, 'meslek' => 'Yazılım Uzmanı', 'ikamet_il' => 'İstanbul', 'kurum' => (object) ['ad' => 'Kestanepazarı']],
            (object) ['uye' => (object) ['ad_soyad' => 'Mehmet Kaya'], 'mezuniyet_yili' => 2010, 'meslek' => 'Avukat', 'ikamet_il' => 'Ankara', 'kurum' => (object) ['ad' => 'Kestanepazarı']],
        ]);

    $schemaData = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => 'Mezun Portalı',
        'description' => 'Kestanepazarı mezunlarına özel giriş ve kayıt ekranı.',
        'url' => route('mezunlar.index'),
    ];
@endphp

@section('title', 'Mezunlar')
@section('meta_description', 'Kestanepazarı mezun portalı üzerinden giriş yapabilir, mezun kaydı oluşturabilir ve mezun ağına katılabilirsiniz.')
@section('robots', 'index, follow')
@section('canonical', route('mezunlar.index'))
@section('og_image', 'https://cdn.kestanepazari.org.tr/logo.png')

@section('schema')
<script type="application/ld+json">
@json($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
</script>
@endsection

@section('content')
    <section class="mx-auto min-h-[calc(100vh-102px)] max-w-7xl px-4 pb-14 pt-[118px] lg:px-6">
        <div class="mx-auto max-w-5xl">
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
            <div class="mb-9 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-[14px] bg-[linear-gradient(135deg,#162E4B,#28484C)]">
                    <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="#EBDFB5" stroke-width="2"><path stroke-linecap="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
                <h1 class="font-baskerville text-[clamp(24px,3vw,34px)] font-bold text-primary">Mezun Portalı</h1>
                <p class="mx-auto mt-3 max-w-xl text-[14.5px] leading-7 text-teal-muted">
                    Kestanepazarı mezunlarına özel alana hoş geldiniz. Giriş yapabilir veya mezun kaydınızı oluşturarak ağa katılabilirsiniz.
                </p>
            </div>

            <div class="grid items-start gap-8 md:grid-cols-2">
                <div class="auth-card" data-baslangic-panel="{{ $errors->any() || session('success') ? 'kayit' : 'giris' }}">
                    <div class="flex border-b border-primary/8">
                        <button type="button" class="panel-tab active" id="tab-giris" onclick="switchPanel('giris')">Giriş Yap</button>
                        <button type="button" class="panel-tab" id="tab-kayit" onclick="switchPanel('kayit')">Mezun Kaydı</button>
                    </div>

                    <div id="panel-giris" class="flex flex-col gap-[18px] p-7">
                        @auth('uye')
                            <div class="rounded-[12px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13px] text-emerald-700">
                                Zaten giriş yapmış görünüyorsunuz. Profil sayfanıza devam edebilirsiniz.
                            </div>

                            <a href="{{ route('uye.profil.index') }}" class="mezun-primary-btn">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                Profilime Git
                            </a>
                        @else
                            <form id="mezun-giris-formu" action="{{ route('uye.giris.giris') }}" method="POST" class="flex flex-col gap-[18px]" data-mezun-giris-form data-otp-url="{{ route('uye.giris.otp') }}">
                                @csrf
                                <input type="hidden" name="g-recaptcha-response" id="mezun-recaptcha-response" data-sitekey="{{ config('services.recaptcha.site_key') }}">

                                <div class="flex gap-1.5 rounded-[10px] bg-bg-soft p-1">
                                    <button type="button" class="giris-tip active" id="tip-eposta" onclick="switchGirisTip('eposta')">E-posta ile</button>
                                    <button type="button" class="giris-tip" id="tip-telefon" onclick="switchGirisTip('telefon')">Telefonla</button>
                                </div>

                                <div id="giris-eposta" class="flex flex-col gap-3.5">
                                    <div class="form-group">
                                        <label class="form-label">E-posta <span>*</span></label>
                                        <input type="email" name="eposta" class="form-input" placeholder="ornek@mail.com" autocomplete="email">
                                    </div>
                                </div>

                                <div id="giris-telefon" class="hidden flex-col gap-3.5">
                                    <div class="form-group">
                                        <label class="form-label">Telefon <span>*</span></label>
                                        <input type="text" name="telefon" class="form-input" placeholder="05XX XXX XX XX" autocomplete="tel">
                                    </div>
                                </div>

                                <div class="rounded-[10px] border-l-[3px] border-accent bg-bg-soft px-3.5 py-3 text-[13px] leading-6 text-primary">
                                    Şifre yerine telefonunuza veya e-postanıza <strong>tek kullanımlık doğrulama kodu</strong> gönderilir.
                                </div>

                                <div id="mezun-giris-hata-alani" class="hidden rounded-[10px] border border-rose-200 bg-rose-50 px-3.5 py-3 text-[13px] text-rose-700">
                                    <p id="mezun-giris-hata-mesaji"></p>
                                </div>

                                <button type="submit" id="mezun-giris-submit" class="mezun-primary-btn">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                    Doğrulama Kodu ile Giriş Yap
                                </button>
                            </form>

                            <div id="mezun-otp-modal" class="hidden fixed inset-0 z-[80] bg-primary/50 px-4">
                                <div class="mx-auto mt-24 max-w-md rounded-[16px] bg-white p-6 shadow-2xl">
                                    <div class="mb-4 flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="font-baskerville text-[20px] font-bold text-primary">Doğrulama Kodu</h3>
                                            <p class="mt-1 text-[13px] text-teal-muted">Telefonunuza veya e-posta adresinize gönderilen 6 haneli kodu girin.</p>
                                        </div>
                                        <button type="button" id="mezun-otp-kapat" class="rounded-full p-1 text-teal-muted transition hover:bg-bg-soft hover:text-primary">✕</button>
                                    </div>

                                    <form id="mezun-otp-formu" class="space-y-4">
                                        @csrf
                                        <input type="text" name="kod" maxlength="6" class="form-input text-center text-[24px] tracking-[0.4em]" placeholder="000000" required>

                                        <div id="mezun-otp-hata-alani" class="hidden rounded-[10px] border border-rose-200 bg-rose-50 px-3.5 py-3 text-[13px] text-rose-700">
                                            <p id="mezun-otp-hata-mesaji"></p>
                                        </div>

                                        <button type="submit" id="mezun-otp-submit" class="mezun-secondary-btn w-full justify-center">
                                            Kodu Doğrula
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <p class="text-center text-[13px] text-teal-muted">
                                Sorun yaşarsanız
                                <a href="{{ route('uye.giris.form') }}" class="font-bold text-accent transition hover:text-orange-cta">ayrı giriş ekranını açın</a>.
                            </p>
                        @endauth

                        <p class="text-center text-[13px] text-teal-muted">
                            Hesabınız yok mu?
                            <button type="button" onclick="switchPanel('kayit')" class="font-bold text-accent transition hover:text-orange-cta">Mezun kaydı oluşturun</button>
                        </p>
                    </div>

                    <form id="panel-kayit" method="POST" action="{{ route('mezunlar.store') }}" class="hidden flex-col gap-4 p-7">
                        @csrf

                        <div class="rounded-[10px] border-l-[3px] border-accent bg-bg-soft px-3.5 py-3">
                            <p class="text-[13px] leading-6 text-primary">Kaydınız dernek yönetimi tarafından incelendikten sonra aktif olur. Onay süreci genellikle 1–2 iş günü sürer.</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Ad Soyad <span>*</span></label>
                            <input type="text" name="ad_soyad" value="{{ old('ad_soyad') }}" class="form-input" placeholder="Adınız ve soyadınız">
                            @error('ad_soyad') <p class="text-[12px] text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="eposta" value="{{ old('eposta') }}" class="form-input" placeholder="ornek@mail.com">
                            @error('eposta') <p class="text-[12px] text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-group">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="telefon" value="{{ old('telefon') }}" class="form-input" placeholder="05XX XXX XX XX">
                                @error('telefon') <p class="text-[12px] text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mezuniyet Yılı <span>*</span></label>
                                <select name="mezuniyet_yili" class="form-select">
                                    <option value="">Yıl seçin</option>
                                    @foreach(array_slice($mezuniyetYillari, 0, 15) as $yil)
                                        <option value="{{ $yil }}" @selected((string) old('mezuniyet_yili') === (string) $yil)>{{ $yil }}</option>
                                    @endforeach
                                </select>
                                @error('mezuniyet_yili') <p class="text-[12px] text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Meslek / Unvan</label>
                            <input type="text" name="meslek" value="{{ old('meslek') }}" class="form-input" placeholder="Mühendis, Öğretmen...">
                            @error('meslek') <p class="text-[12px] text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Açık Adres</label>
                            <textarea name="acik_adres" class="form-input min-h-[92px] resize-y" placeholder="İlçe, mahalle, kurum veya görev yeri detayı yazabilirsiniz">{{ old('acik_adres') }}</textarea>
                            @error('acik_adres') <p class="text-[12px] text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-input min-h-[92px] resize-y" placeholder="Eklemek istediğiniz not, görev bilgisi veya açıklama">{{ old('aciklama') }}</textarea>
                            @error('aciklama') <p class="text-[12px] text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <label class="flex items-start gap-2.5 rounded-[9px] bg-bg-soft px-3 py-2.5 text-[12.5px] leading-6 text-teal-muted">
                            <input type="checkbox" name="kvkk" value="1" @checked(old('kvkk')) class="mt-1 h-[15px] w-[15px] accent-primary">
                            <span><a href="{{ route('kurumsal.show', 'kvkk') }}" class="font-semibold text-primary">KVKK Aydınlatma Metni</a>'ni okudum, kişisel verilerimin işlenmesine onay veriyorum. <strong class="text-orange-cta">*</strong></span>
                        </label>
                        @error('kvkk') <p class="text-[12px] text-rose-600">{{ $message }}</p> @enderror

                        <button type="submit" class="mezun-secondary-btn">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            Kayıt Başvurusu Gönder
                        </button>

                        <p class="text-center text-[13px] text-teal-muted">
                            Zaten hesabınız var mı?
                            <button type="button" onclick="switchPanel('giris')" class="font-bold text-accent transition hover:text-orange-cta">Giriş yapın</button>
                        </p>
                    </form>
                </div>

                <div class="space-y-4">
                    <div class="rounded-[16px] border border-primary/8 bg-white p-6">
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-[9px] bg-[linear-gradient(135deg,#162E4B,#28484C)]">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#EBDFB5" stroke-width="2"><path stroke-linecap="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                        </div>
                        <h2 class="font-baskerville text-[18px] font-bold text-primary">Mezun Portalında Neler Var?</h2>
                        <div class="mt-4 space-y-3">
                            @foreach([
                                'Profil bilgilerinizi güncelleyin',
                                'Bağış ve katılım geçmişinizi takip edin',
                                'Etkinliklerden öncelikli haberdar olun',
                                'Dernek duyurularına doğrudan erişin',
                            ] as $madde)
                                <div class="flex items-start gap-2.5">
                                    <span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary/8 text-accent">
                                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <p class="text-[13.5px] leading-6 text-teal-muted">{{ $madde }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-[16px] bg-[linear-gradient(135deg,#162E4B,#091420)] p-6">
                        <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-accent/10"></div>
                        <p class="relative z-[1] mb-4 text-[12px] font-semibold uppercase tracking-[0.08em] text-cream/50">Mezun Ailemiz</p>
                        <div class="relative z-[1] grid grid-cols-2 gap-4">
                            <div>
                                <p class="font-baskerville text-[28px] font-bold leading-none text-cream">
                                    {{ $aktifMezunSayisi > 0 ? number_format($aktifMezunSayisi, 0, ',', '.') : '4.500+' }}
                                </p>
                                <p class="mt-1 text-[12px] text-cream/55">Kayıtlı mezun</p>
                            </div>
                            <div>
                                <p class="font-baskerville text-[28px] font-bold leading-none text-cream">
                                    {{ $yilAraligi }}
                                </p>
                                <p class="mt-1 text-[12px] text-cream/55">Yıllık gelenek</p>
                            </div>
                        </div>
                        <div class="relative z-[1] mt-4 rounded-[12px] border border-cream/10 bg-white/5 px-3.5 py-3 text-[13px] text-cream/75">
                            Hafız mezun sayısı: <strong class="text-cream">{{ $hafizMezunSayisi > 0 ? number_format($hafizMezunSayisi, 0, ',', '.') : '—' }}</strong>
                        </div>
                    </div>

                    <div class="rounded-[14px] border border-primary/8 bg-white p-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-[38px] w-[38px] items-center justify-center rounded-[9px] bg-bg-soft text-accent">
                                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <div>
                                <p class="text-[13.5px] font-semibold text-primary">Sorun mu yaşıyorsunuz?</p>
                                <a href="{{ route('iletisim.index') }}" class="text-[13px] font-semibold text-accent transition hover:text-orange-cta">İletişim sayfası üzerinden bize ulaşın</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="mt-10 rounded-[20px] border border-primary/8 bg-white p-5 md:p-6">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-accent">Mezun Ağı</p>
                        <h2 class="font-baskerville text-[22px] font-bold text-primary">Öne çıkan mezunlar</h2>
                    </div>
                    <span class="rounded-full bg-bg-soft px-3 py-1 text-[12px] font-semibold text-teal-muted">Topluluk ruhu</span>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    @foreach($oneCikanMezunlar as $mezun)
                        @php
                            $adSoyad = $mezun->uye->ad_soyad ?? 'Kestanepazarı Mezunu';
                            $harfler = collect(explode(' ', $adSoyad))->filter()->take(2)->map(fn ($parca) => \Illuminate\Support\Str::substr($parca, 0, 1))->join('');
                        @endphp
                        <article class="mezun-preview-card">
                            <div class="mezun-preview-avatar">{{ $harfler ?: 'KM' }}</div>
                            <h3>{{ $adSoyad }}</h3>
                            <p>{{ $mezun->meslek ?: 'Mezun üye' }}</p>
                            <div class="mt-3 space-y-1 text-[12px] text-teal-muted">
                                <div>Mezuniyet: {{ $mezun->mezuniyet_yili ?: '—' }}</div>
                                <div>{{ $mezun->ikamet_il ?: ($mezun->kurum->ad ?? 'Kestanepazarı') }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </section>
@endsection

@push('scripts')
    @if (filled(config('services.recaptcha.site_key')))
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
    @endif
@endpush
