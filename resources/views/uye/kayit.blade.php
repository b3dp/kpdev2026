@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Kayıt Ol</h1>
            <p class="text-gray-600 mt-2">Yeni hesap oluşturun</p>
        </div>

        <form id="kayit-formu" class="space-y-6">
            @csrf

            {{-- Honeypot --}}
            <x-honeypot />

            {{-- Ad Soyad --}}
            <div>
                <label for="ad_soyad" class="block text-sm font-medium text-gray-700">
                    Ad Soyad
                </label>
                <input
                    type="text"
                    name="ad_soyad"
                    id="ad_soyad"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Ad Soyad"
                    required
                />
                @error('ad_soyad')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- E-posta --}}
            <div>
                <label for="eposta" class="block text-sm font-medium text-gray-700">
                    E-Posta
                </label>
                <input
                    type="email"
                    name="eposta"
                    id="eposta"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="ornek@eposta.com"
                />
                @error('eposta')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Telefon --}}
            <div>
                <label for="telefon" class="block text-sm font-medium text-gray-700">
                    Cep Telefonu
                </label>
                <input
                    type="text"
                    name="telefon"
                    id="telefon"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="5xx xxx xx xx"
                />
                <p class="mt-1 text-xs text-gray-500">E-posta veya telefon alanlarından en az birini doldurun.</p>
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
            <div id="hata-alani" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                <p id="hata-mesaji"></p>
            </div>

            {{-- Kayıt Butonu --}}
            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition font-medium"
            >
                Kayıt Ol
            </button>

            {{-- Giriş Linki --}}
            <p class="text-center text-gray-600 text-sm">
                Zaten hesabınız var mı?
                <a href="{{ route('uye.giris.form') }}" class="text-blue-600 hover:text-blue-700 font-medium">
                    Giriş Yap
                </a>
            </p>
        </form>

        {{-- OTP Modal --}}
        <div id="otp-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
                <h2 class="text-xl font-bold text-gray-900 mb-4">OTP Doğrulaması</h2>
                <p class="text-gray-600 mb-4">Telefonunuza/e-postanıza gönderilen 6 haneli kodu giriniz.</p>

                <form id="otp-formu" class="space-y-4">
                    @csrf
                    <input
                        type="text"
                        name="kod"
                        placeholder="000000"
                        maxlength="6"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-center text-2xl tracking-widest focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required
                    />
                    <button
                        type="submit"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition font-medium"
                    >
                        Doğrula
                    </button>
                </form>

                <div id="otp-hata" class="hidden mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <p id="otp-hata-mesaji"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<script>
document.getElementById('kayit-formu').addEventListener('submit', async function(e) {
    e.preventDefault();

    const hataBolumu = document.getElementById('hata-alani');

    if (typeof grecaptcha === 'undefined') {
        document.getElementById('hata-mesaji').textContent = 'reCAPTCHA yüklenemedi. Sayfayı yenileyip tekrar deneyiniz.';
        hataBolumu.classList.remove('hidden');
        return;
    }

    // reCAPTCHA token al
    let token = '';

    try {
        token = await grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {
            action: 'kayit'
        });
    } catch (error) {
        document.getElementById('hata-mesaji').textContent = 'reCAPTCHA doğrulaması yapılamadı. Lütfen tekrar deneyiniz.';
        hataBolumu.classList.remove('hidden');
        return;
    }

    if (!token) {
        document.getElementById('hata-mesaji').textContent = 'reCAPTCHA doğrulaması yapılamadı. Lütfen tekrar deneyiniz.';
        hataBolumu.classList.remove('hidden');
        return;
    }

    document.getElementById('recaptcha-response').value = token;

    const formData = new FormData(this);

    try {
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

            document.getElementById('hata-mesaji').textContent = detay;
            hataBolumu.classList.remove('hidden');
            return;
        }

        const data = await response.json();
        if (data.step === 'otp') {
            document.getElementById('otp-modal').classList.remove('hidden');
        }
    } catch (error) {
        document.getElementById('hata-mesaji').textContent = `Network hatası: ${error.message}`;
        hataBolumu.classList.remove('hidden');
    }
});

document.getElementById('otp-formu').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const otpHataBolumu = document.getElementById('otp-hata');

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

            document.getElementById('otp-hata-mesaji').textContent = detay;
            otpHataBolumu.classList.remove('hidden');
            return;
        }

        const data = await response.json();
        if (data.redirect) {
            window.location.href = data.redirect;
        }
    } catch (error) {
        document.getElementById('otp-hata-mesaji').textContent = `Network hatası: ${error.message}`;
        otpHataBolumu.classList.remove('hidden');
    }
});
</script>
@endsection
