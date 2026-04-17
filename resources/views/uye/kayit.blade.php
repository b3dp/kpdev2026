@extends('layouts.app')

@section('content')
<section class="relative overflow-hidden bg-[radial-gradient(circle_at_top_right,_rgba(22,46,75,0.16),_transparent_35%),linear-gradient(180deg,#f7f5f0_0%,#fff_72%)] px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
    <div class="mx-auto grid min-h-[calc(100vh-210px)] max-w-6xl items-stretch gap-6 lg:grid-cols-[.98fr_1.02fr]">
        <div class="flex items-center justify-center order-2 lg:order-1">
            <div class="w-full max-w-xl rounded-[30px] border border-primary/10 bg-white p-6 shadow-[0_18px_45px_rgba(22,46,75,0.12)] sm:p-8 lg:p-10">
                <div class="mb-8 flex items-center justify-between gap-4">
                    <div>
                        <p class="font-jakarta text-[11px] font-semibold uppercase tracking-[0.18em] text-accent">Yeni Uye</p>
                        <h1 class="mt-2 font-baskerville text-[34px] font-bold leading-none text-primary">Kayıt Ol</h1>
                        <p class="mt-3 font-jakarta text-sm leading-6 text-teal-muted">Yeni hesap oluşturun ve OTP doğrulamasıyla profilinizi hızla tamamlayın.</p>
                    </div>
                    <img src="{{ asset('images/logo.svg') }}" alt="Kestanepazarı" class="hidden h-16 w-auto sm:block">
                </div>

                <div class="mb-8 grid grid-cols-2 rounded-[18px] bg-bg-soft p-1.5">
                    <a href="{{ route('uye.giris.form') }}" class="rounded-[14px] px-4 py-3 text-center font-jakarta text-sm font-semibold text-teal-muted transition-colors hover:text-primary">Giriş</a>
                    <a href="{{ route('uye.kayit.form') }}" class="rounded-[14px] bg-white px-4 py-3 text-center font-jakarta text-sm font-semibold text-primary shadow-sm">Kayıt Ol</a>
                </div>

        <form id="kayit-formu" class="space-y-6">
            @csrf

            {{-- Honeypot --}}
            <x-honeypot />

            {{-- Ad Soyad --}}
            <div>
                <label for="ad_soyad" class="block font-jakarta text-[12px] font-semibold uppercase tracking-[0.14em] text-primary/70">
                    Ad Soyad
                </label>
                <input
                    type="text"
                    name="ad_soyad"
                    id="ad_soyad"
                    class="mt-2 block w-full rounded-[16px] border border-primary/12 bg-bg-soft px-4 py-3.5 font-jakarta text-[15px] text-primary shadow-none transition focus:border-accent focus:bg-white focus:ring-0"
                    placeholder="Ad Soyad"
                    required
                />
                @error('ad_soyad')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- E-posta --}}
            <div>
                <label for="eposta" class="block font-jakarta text-[12px] font-semibold uppercase tracking-[0.14em] text-primary/70">
                    E-Posta
                </label>
                <input
                    type="email"
                    name="eposta"
                    id="eposta"
                    class="mt-2 block w-full rounded-[16px] border border-primary/12 bg-bg-soft px-4 py-3.5 font-jakarta text-[15px] text-primary shadow-none transition focus:border-accent focus:bg-white focus:ring-0"
                    placeholder="ornek@eposta.com"
                />
                @error('eposta')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Telefon --}}
            <div>
                <label for="telefon" class="block font-jakarta text-[12px] font-semibold uppercase tracking-[0.14em] text-primary/70">
                    Cep Telefonu
                </label>
                <input
                    type="text"
                    name="telefon"
                    id="telefon"
                    class="mt-2 block w-full rounded-[16px] border border-primary/12 bg-bg-soft px-4 py-3.5 font-jakarta text-[15px] text-primary shadow-none transition focus:border-accent focus:bg-white focus:ring-0"
                    placeholder="5xx xxx xx xx"
                />
                <p class="mt-2 font-jakarta text-xs text-teal-muted">E-posta veya telefon alanlarından en az birini doldurun.</p>
                @error('telefon')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- reCAPTCHA --}}
            <input
                type="hidden"
                name="g-recaptcha-response"
                id="recaptcha-response"
            />

            {{-- Hata Mesajı --}}
            <div id="hata-alani" class="hidden rounded-[16px] border border-red-200 bg-red-50 px-4 py-3 font-jakarta text-sm text-red-700">
                <p id="hata-mesaji"></p>
            </div>

            {{-- Kayıt Butonu --}}
            <button
                type="submit"
                id="kayit-submit"
                class="w-full rounded-[16px] bg-orange-cta px-4 py-3.5 font-jakarta text-[15px] font-bold text-white shadow-[0_14px_30px_rgba(233,89,37,0.24)] transition hover:bg-[#c94620]"
            >
                Kayıt Ol
            </button>

            <div class="rounded-[18px] border border-primary/8 bg-bg-soft px-4 py-4 text-center font-jakarta text-sm text-teal-muted">
                Zaten hesabınız var mı?
                <a href="{{ route('uye.giris.form') }}" class="font-semibold text-primary transition hover:text-accent">
                    Giriş Yap
                </a>
            </div>
        </form>
            </div>
        </div>

        <div class="order-1 overflow-hidden rounded-[30px] bg-[#102742] text-white shadow-[0_24px_60px_rgba(22,46,75,0.22)] lg:order-2 lg:flex lg:flex-col lg:justify-between">
            <div class="p-10">
                <div class="inline-flex items-center gap-3 rounded-full border border-white/14 bg-white/8 px-4 py-2 font-jakarta text-xs font-semibold uppercase tracking-[0.16em] text-white/78">
                    Topluluga Katil
                </div>
                <h1 class="mt-8 max-w-[440px] font-baskerville text-[clamp(36px,4vw,56px)] font-bold leading-[1.04] text-cream">
                    Tek form, tek kod, temiz bir üyelik başlangıcı.
                </h1>
                <p class="mt-6 max-w-[440px] font-jakarta text-[15px] leading-7 text-white/78">
                    Mezun, bağış ve profil süreçlerine erişmek için temel bilgilerinizi bırakın; doğrulama kodu ile hesabınızı hızlıca aktive edin.
                </p>

                <div class="mt-10 space-y-3">
                    <div class="rounded-[22px] border border-white/10 bg-white/8 p-5 backdrop-blur-sm">
                        <p class="font-jakarta text-[11px] font-semibold uppercase tracking-[0.15em] text-white/60">Adim 1</p>
                        <p class="mt-2 font-jakarta text-base font-semibold text-cream">Temel bilgilerinizi girin</p>
                    </div>
                    <div class="rounded-[22px] border border-white/10 bg-white/8 p-5 backdrop-blur-sm">
                        <p class="font-jakarta text-[11px] font-semibold uppercase tracking-[0.15em] text-white/60">Adim 2</p>
                        <p class="mt-2 font-jakarta text-base font-semibold text-cream">OTP kodunu doğrulayın</p>
                    </div>
                    <div class="rounded-[22px] border border-white/10 bg-white/8 p-5 backdrop-blur-sm">
                        <p class="font-jakarta text-[11px] font-semibold uppercase tracking-[0.15em] text-white/60">Adim 3</p>
                        <p class="mt-2 font-jakarta text-base font-semibold text-cream">Profil ve başvuru alanlarını kullanmaya başlayın</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-white/10 bg-white/6 px-10 py-6">
                <p class="font-jakarta text-sm font-medium text-white/70">Zaten üyeyseniz</p>
                <p class="mt-2 font-jakarta text-[15px] leading-7 text-white/84">Doğrudan giriş ekranına dönüp OTP ile oturumunuzu saniyeler içinde açabilirsiniz.</p>
            </div>
        </div>

        {{-- OTP Modal --}}
        <div id="otp-modal" class="hidden fixed inset-0 z-[90] flex items-center justify-center bg-primary/45 px-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-[28px] border border-white/30 bg-white p-6 shadow-[0_22px_60px_rgba(22,46,75,0.22)] sm:p-7">
                <p class="font-jakarta text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">OTP Doğrulamasi</p>
                <h2 class="mt-3 font-baskerville text-[30px] font-bold text-primary">Kodu Girin</h2>
                <p class="mt-3 font-jakarta text-sm leading-6 text-teal-muted">Telefonunuza veya e-postanıza gönderilen 6 haneli kodu giriniz.</p>

                <form id="otp-formu" class="space-y-4">
                    @csrf
                    <input
                        type="text"
                        name="kod"
                        placeholder="000000"
                        maxlength="6"
                        class="w-full rounded-[16px] border border-primary/12 bg-bg-soft px-4 py-3.5 text-center font-jakarta text-2xl tracking-[0.35em] text-primary focus:border-accent focus:bg-white focus:ring-0"
                        required
                    />
                    <button
                        type="submit"
                        id="otp-submit"
                        class="w-full rounded-[16px] bg-primary px-4 py-3.5 font-jakarta text-[15px] font-bold text-cream transition hover:bg-[#10243e]"
                    >
                        Doğrula
                    </button>
                </form>

                <div id="otp-hata" class="hidden mt-4 rounded-[16px] border border-red-200 bg-red-50 px-4 py-3 font-jakarta text-sm text-red-700">
                    <p id="otp-hata-mesaji"></p>
                </div>
            </div>
        </div>
</section>

<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<script>
let kayitGonderiliyor = false;
let otpGonderiliyor = false;

function butonYukleniyorYap(buton, yukleniyor, normalYazi, yukleniyorYazisi) {
    buton.disabled = yukleniyor;
    buton.textContent = yukleniyor ? yukleniyorYazisi : normalYazi;
    buton.classList.toggle('opacity-70', yukleniyor);
    buton.classList.toggle('cursor-not-allowed', yukleniyor);
}

document.getElementById('kayit-formu').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (kayitGonderiliyor) {
        return;
    }

    kayitGonderiliyor = true;

    const kayitButonu = document.getElementById('kayit-submit');
    const hataBolumu = document.getElementById('hata-alani');
    const hataMesaji = document.getElementById('hata-mesaji');
    butonYukleniyorYap(kayitButonu, true, 'Kayıt Ol', 'Gönderiliyor...');
    hataBolumu.classList.add('hidden');
    hataMesaji.textContent = '';

    try {
        if (typeof grecaptcha === 'undefined') {
            hataMesaji.textContent = 'reCAPTCHA yüklenemedi. Sayfayı yenileyip tekrar deneyiniz.';
            hataBolumu.classList.remove('hidden');
            return;
        }

        const token = await grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {
            action: 'kayit'
        });

        if (!token) {
            hataMesaji.textContent = 'reCAPTCHA doğrulaması yapılamadı. Lütfen tekrar deneyiniz.';
            hataBolumu.classList.remove('hidden');
            return;
        }

        document.getElementById('recaptcha-response').value = token;

        const formData = new FormData(this);

        const response = await fetch('{{ route('uye.kayit.kayit') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: formData
        });

        if (!response.ok) {
            let detay = `HTTP ${response.status}`;

            try {
                const payload = await response.json();
                const errorMessages = Object.values(payload.errors || {}).flat();
                detay = errorMessages[0] || payload.message || detay;
            } catch (_) {
                const text = await response.text();
                detay = text || detay;
            }

            hataMesaji.textContent = detay;
            hataBolumu.classList.remove('hidden');
            return;
        }

        const data = await response.json();
        if (data.step === 'otp') {
            document.getElementById('otp-modal').classList.remove('hidden');
        }
    } catch (error) {
        hataMesaji.textContent = `Network hatası: ${error.message}`;
        hataBolumu.classList.remove('hidden');
    } finally {
        kayitGonderiliyor = false;
        butonYukleniyorYap(kayitButonu, false, 'Kayıt Ol', 'Gönderiliyor...');
    }
});

document.getElementById('otp-formu').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (otpGonderiliyor) {
        return;
    }

    otpGonderiliyor = true;

    const formData = new FormData(this);
    const otpHataBolumu = document.getElementById('otp-hata');
    const otpHataMesaji = document.getElementById('otp-hata-mesaji');
    const otpButonu = document.getElementById('otp-submit');
    butonYukleniyorYap(otpButonu, true, 'Doğrula', 'Doğrulanıyor...');
    otpHataBolumu.classList.add('hidden');
    otpHataMesaji.textContent = '';

    try {
        const response = await fetch('{{ route('uye.kayit.otp') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: formData
        });

        if (!response.ok) {
            let detay = `HTTP ${response.status}`;

            try {
                const payload = await response.json();
                const errorMessages = Object.values(payload.errors || {}).flat();
                detay = errorMessages[0] || payload.message || detay;
            } catch (_) {
                const text = await response.text();
                detay = text || detay;
            }

            otpHataMesaji.textContent = detay;
            otpHataBolumu.classList.remove('hidden');
            return;
        }

        const data = await response.json();
        if (data.redirect) {
            window.location.href = data.redirect;
        }
    } catch (error) {
        otpHataMesaji.textContent = `Network hatası: ${error.message}`;
        otpHataBolumu.classList.remove('hidden');
    } finally {
        otpGonderiliyor = false;
        butonYukleniyorYap(otpButonu, false, 'Doğrula', 'Doğrulanıyor...');
    }
});
</script>
@endsection
