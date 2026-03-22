@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Giriş Yap</h1>
            <p class="text-gray-600 mt-2">E-posta veya telefon numaranız ile giriş yapın</p>
        </div>

        <form id="giriş-formu" class="space-y-6">
            @csrf

            {{-- Honeypot --}}
            <x-honeypot />

            {{-- İletişim Bilgisi --}}
            <div>
                <label for="iletisim" class="block text-sm font-medium text-gray-700">
                    E-Posta veya Telefon
                </label>
                <input
                    type="text"
                    name="iletisim"
                    id="iletisim"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="E-posta veya telefon"
                    required
                />
                @error('iletisim')
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

            {{-- Giriş Butonu --}}
            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition font-medium"
            >
                Giriş Yap
            </button>

            {{-- Kayıt Linki --}}
            <p class="text-center text-gray-600 text-sm">
                Hesabınız yok mu?
                <a href="{{ route('uye.kayit.form') }}" class="text-blue-600 hover:text-blue-700 font-medium">
                    Kayıt Ol
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
document.getElementById('giriş-formu').addEventListener('submit', async function(e) {
    e.preventDefault();

    // reCAPTCHA token al
    const token = await grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {
        action: 'giris'
    });
    document.getElementById('recaptcha-response').value = token;

    const formData = new FormData(this);
    const hataBolumu = document.getElementById('hata-alani');

    try {
        const response = await fetch('{{ route('uye.giris.giris') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: formData
        });

        if (!response.ok) {
            const errors = await response.json();
            const errorMessages = Object.values(errors.errors || {}).flat();
            document.getElementById('hata-mesaji').textContent = errorMessages[0] || 'Bir hata oluştu.';
            hataBolumu.classList.remove('hidden');
            return;
        }

        const data = await response.json();
        if (data.step === 'otp') {
            document.getElementById('otp-modal').classList.remove('hidden');
        } else if (data.step === 'sifre') {
            // Şifre formu göster (opsiyonel)
            alert('Şifre kontrolü');
        }
    } catch (error) {
        document.getElementById('hata-mesaji').textContent = 'Network hatası oluştu.';
        hataBolumu.classList.remove('hidden');
    }
});

document.getElementById('otp-formu').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const otpHataBolumu = document.getElementById('otp-hata');

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
            const errors = await response.json();
            const errorMessages = Object.values(errors.errors || {}).flat();
            document.getElementById('otp-hata-mesaji').textContent = errorMessages[0] || 'Bir hata oluştu.';
            otpHataBolumu.classList.remove('hidden');
            return;
        }

        const data = await response.json();
        if (data.redirect) {
            window.location.href = data.redirect;
        }
    } catch (error) {
        document.getElementById('otp-hata-mesaji').textContent = 'Network hatası oluştu.';
        otpHataBolumu.classList.remove('hidden');
    }
});
</script>
@endsection
