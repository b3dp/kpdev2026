@extends('layouts.app')

@section('content')
<section class="mx-auto min-h-[calc(100vh-102px)] max-w-7xl px-4 pb-14 pt-[118px] lg:px-6">
    <div class="mx-auto max-w-5xl">
        <div class="mb-9 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-[14px] bg-[linear-gradient(135deg,#162E4B,#28484C)]">
                <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="#EBDFB5" stroke-width="2"><path stroke-linecap="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            </div>
            <h1 class="font-baskerville text-[clamp(24px,3vw,34px)] font-bold text-primary">Üye Girişi</h1>
            <p class="mx-auto mt-3 max-w-xl text-[14.5px] leading-7 text-teal-muted">
                Kestanepazarı üye alanına hoş geldiniz. E-posta veya telefon bilginizle OTP doğrulaması yaparak hesabınıza giriş yapabilirsiniz.
            </p>
        </div>

        <div class="grid items-start gap-8 md:grid-cols-2">
            <div class="auth-card">
                <div class="flex border-b border-primary/8">
                    <a href="{{ route('uye.giris.form') }}" class="panel-tab active text-center">Giriş Yap</a>
                    <a href="{{ route('uye.kayit.form') }}" class="panel-tab text-center">Kayıt Ol</a>
                </div>

                <div class="flex flex-col gap-[18px] p-7">
                    <form id="giriş-formu" class="flex flex-col gap-[18px]">
            @csrf

            <x-honeypot />

            <div class="flex gap-1.5 rounded-[10px] bg-bg-soft p-1">
                <button type="button" class="giris-tip active" id="tip-eposta" onclick="switchGirisTip('eposta')">E-posta ile</button>
                <button type="button" class="giris-tip" id="tip-telefon" onclick="switchGirisTip('telefon')">Telefonla</button>
            </div>

            <div id="giris-eposta" class="flex flex-col gap-3.5">
                <div class="form-group">
                <label for="eposta" class="form-label">
                    E-posta <span>*</span>
                </label>
                <input
                    type="email"
                    name="eposta"
                    id="eposta"
                    class="form-input"
                    placeholder="ornek@eposta.com"
                />
                @error('eposta')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                </div>
            </div>

            <div id="giris-telefon" class="hidden flex-col gap-3.5">
                <div class="form-group">
                <label for="telefon" class="form-label">
                    Telefon <span>*</span>
                </label>
                <input
                    type="text"
                    name="telefon"
                    id="telefon"
                    class="form-input"
                    placeholder="5xx xxx xx xx"
                />
                @error('telefon')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                </div>
            </div>

            <input
                type="hidden"
                name="g-recaptcha-response"
                id="recaptcha-response"
            />

            <div class="rounded-[10px] border-l-[3px] border-accent bg-bg-soft px-3.5 py-3 text-[13px] leading-6 text-primary">
                Şifre yerine telefonunuza veya e-posta adresinize <strong>tek kullanımlık doğrulama kodu</strong> gönderilir.
            </div>

            <div id="hata-alani" class="hidden rounded-[10px] border border-rose-200 bg-rose-50 px-3.5 py-3 text-[13px] text-rose-700">
                <p id="hata-mesaji"></p>
            </div>

            <button
                type="submit"
                id="giris-submit"
                class="mezun-primary-btn"
            >
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                OTP ile Giriş Yap
            </button>

            <p class="text-center text-[13px] text-teal-muted">
                Hesabınız yok mu?
                <a href="{{ route('uye.kayit.form') }}" class="font-bold text-accent transition hover:text-orange-cta">Kayıt olun</a>
            </p>
        </form>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-[16px] border border-primary/8 bg-white p-6">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-[9px] bg-[linear-gradient(135deg,#162E4B,#28484C)]">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#EBDFB5" stroke-width="2"><path stroke-linecap="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    </div>
                    <h2 class="font-baskerville text-[18px] font-bold text-primary">Üye Alanında Neler Var?</h2>
                    <div class="mt-4 space-y-3">
                        @foreach([
                            'Profil bilgilerinizi yönetin',
                            'Bağış geçmişinizi görüntüleyin',
                            'Bildirim ve tercihlerinizi güncelleyin',
                            'Başvurularınızı tek ekrandan takip edin',
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
                    <p class="relative z-[1] mb-4 text-[12px] font-semibold uppercase tracking-[0.08em] text-cream/50">Güvenli Erişim</p>
                    <div class="relative z-[1] grid grid-cols-2 gap-4">
                        <div>
                            <p class="font-baskerville text-[28px] font-bold leading-none text-cream">OTP</p>
                            <p class="mt-1 text-[12px] text-cream/55">Tek kod doğrulama</p>
                        </div>
                        <div>
                            <p class="font-baskerville text-[28px] font-bold leading-none text-cream">Hızlı</p>
                            <p class="mt-1 text-[12px] text-cream/55">Şifresiz giriş akışı</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="otp-modal" class="hidden fixed inset-0 z-[80] bg-primary/50 px-4">
            <div class="mx-auto mt-24 max-w-md rounded-[16px] bg-white p-6 shadow-2xl">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-baskerville text-[20px] font-bold text-primary">Doğrulama Kodu</h3>
                        <p class="mt-1 text-[13px] text-teal-muted">Telefonunuza veya e-posta adresinize gönderilen 6 haneli kodu girin.</p>
                    </div>
                </div>

                <form id="otp-formu" class="space-y-4">
                    @csrf
                    <input
                        type="text"
                        name="kod"
                        placeholder="000000"
                        maxlength="6"
                        class="form-input text-center text-[24px] tracking-[0.4em]"
                        required
                    />
                    <button
                        type="submit"
                        id="giris-otp-submit"
                        class="mezun-secondary-btn w-full justify-center"
                    >
                        Kodu Doğrula
                    </button>
                </form>

                <div id="otp-hata" class="hidden mt-4 rounded-[10px] border border-rose-200 bg-rose-50 px-3.5 py-3 text-[13px] text-rose-700">
                    <p id="otp-hata-mesaji"></p>
                </div>
            </div>
        </div>
</div>
</section>

<script>
function switchGirisTip(tip) {
    const tipEposta = document.getElementById('tip-eposta');
    const tipTelefon = document.getElementById('tip-telefon');
    const girisEposta = document.getElementById('giris-eposta');
    const girisTelefon = document.getElementById('giris-telefon');

    tipEposta?.classList.toggle('active', tip === 'eposta');
    tipTelefon?.classList.toggle('active', tip === 'telefon');
    girisEposta?.classList.toggle('hidden', tip !== 'eposta');
    girisTelefon?.classList.toggle('hidden', tip !== 'telefon');
}
</script>

<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<script>
let girisGonderiliyor = false;
let girisOtpGonderiliyor = false;

function butonYukleniyorYap(buton, yukleniyor, normalYazi, yukleniyorYazisi) {
    buton.disabled = yukleniyor;
    buton.textContent = yukleniyor ? yukleniyorYazisi : normalYazi;
    buton.classList.toggle('opacity-70', yukleniyor);
    buton.classList.toggle('cursor-not-allowed', yukleniyor);
}

document.getElementById('giriş-formu').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (girisGonderiliyor) {
        return;
    }

    girisGonderiliyor = true;

    const girisButonu = document.getElementById('giris-submit');
    const hataBolumu = document.getElementById('hata-alani');
    const hataMesaji = document.getElementById('hata-mesaji');
    butonYukleniyorYap(girisButonu, true, 'OTP Gönder', 'Gönderiliyor...');
    hataBolumu.classList.add('hidden');
    hataMesaji.textContent = '';

    try {
        if (typeof grecaptcha === 'undefined') {
            hataMesaji.textContent = 'reCAPTCHA yüklenemedi. Sayfayı yenileyip tekrar deneyiniz.';
            hataBolumu.classList.remove('hidden');
            return;
        }

        const token = await grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {
            action: 'giris'
        });

        if (!token) {
            hataMesaji.textContent = 'reCAPTCHA doğrulaması yapılamadı. Lütfen tekrar deneyiniz.';
            hataBolumu.classList.remove('hidden');
            return;
        }

        document.getElementById('recaptcha-response').value = token;

        const formData = new FormData(this);

        const response = await fetch('{{ route('uye.giris.giris') }}', {
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
        girisGonderiliyor = false;
        butonYukleniyorYap(girisButonu, false, 'OTP Gönder', 'Gönderiliyor...');
    }
});

document.getElementById('otp-formu').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (girisOtpGonderiliyor) {
        return;
    }

    girisOtpGonderiliyor = true;

    const formData = new FormData(this);
    const otpHataBolumu = document.getElementById('otp-hata');
    const otpHataMesaji = document.getElementById('otp-hata-mesaji');
    const otpButonu = document.getElementById('giris-otp-submit');
    butonYukleniyorYap(otpButonu, true, 'Doğrula', 'Doğrulanıyor...');
    otpHataBolumu.classList.add('hidden');
    otpHataMesaji.textContent = '';

    try {
        const response = await fetch('{{ route('uye.giris.otp') }}', {
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
        girisOtpGonderiliyor = false;
        butonYukleniyorYap(otpButonu, false, 'Doğrula', 'Doğrulanıyor...');
    }
});
</script>
@endsection
