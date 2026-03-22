@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Profilim</h1>
                <form action="{{ route('uye.cikis') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-red-600 hover:text-red-700 font-medium">
                        Çıkış Yap
                    </button>
                </form>
            </div>

            {{-- Rozetler --}}
            @if ($uye->rozetler && $uye->rozetler->count() > 0)
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-3">Rozetlerim</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($uye->rozetler as $rozet)
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                                {{ ucfirst($rozet->rozet) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Profil Bilgileri --}}
            <div class="space-y-6">
                <h2 class="text-lg font-semibold text-gray-900">Profil Bilgileri</h2>

                <form id="profil-formu" class="space-y-4">
                    @csrf

                    <div>
                        <label for="ad_soyad" class="block text-sm font-medium text-gray-700">
                            Ad Soyad
                        </label>
                        <input
                            type="text"
                            name="ad_soyad"
                            id="ad_soyad"
                            value="{{ $uye->ad_soyad }}"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p id="ad_soyad_error" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>

                    <div>
                        <label for="telefon" class="block text-sm font-medium text-gray-700">
                            Telefon Numarası
                        </label>
                        <input
                            type="text"
                            id="telefon"
                            value="{{ $uye->telefon }}"
                            disabled
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600"
                        />
                        <p class="mt-1 text-xs text-gray-500">Telefon numarası değiştirilemez.</p>
                    </div>

                    <div>
                        <label for="eposta" class="block text-sm font-medium text-gray-700">
                            E-Posta Adresi
                        </label>
                        <input
                            type="email"
                            name="eposta"
                            id="eposta"
                            value="{{ $uye->eposta }}"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p id="eposta_error" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition font-medium"
                    >
                        Profili Güncelle
                    </button>
                </form>
            </div>

            {{-- Abonelikler --}}
            <div class="mt-8 pt-8 border-t border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Abonelik Tercihleri</h2>

                <form id="abonelik-formu" class="space-y-4">
                    @csrf

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            name="sms_abonelik"
                            id="sms_abonelik"
                            {{ $uye->sms_abonelik ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500 border-gray-300"
                        />
                        <label for="sms_abonelik" class="ml-2 block text-sm text-gray-900">
                            SMS abonelikleri kabul ediyorum
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            name="eposta_abonelik"
                            id="eposta_abonelik"
                            {{ $uye->eposta_abonelik ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500 border-gray-300"
                        />
                        <label for="eposta_abonelik" class="ml-2 block text-sm text-gray-900">
                            E-posta abonelikleri kabul ediyorum
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition font-medium"
                    >
                        Abonelik Tercihlerini Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('profil-formu').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch('{{ route('uye.profil.guncelle') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: formData
        });

        if (!response.ok) {
            const errors = await response.json();
            if (errors.errors) {
                Object.keys(errors.errors).forEach(field => {
                    const errorElement = document.getElementById(`${field}_error`);
                    if (errorElement) {
                        errorElement.textContent = errors.errors[field][0];
                        errorElement.classList.remove('hidden');
                    }
                });
            }
            return;
        }

        const data = await response.json();
        alert(data.message);
    } catch (error) {
        alert('Network hatası oluştu.');
    }
});

document.getElementById('abonelik-formu').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch('{{ route('uye.abonelik.guncelle') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: formData
        });

        if (!response.ok) {
            alert('Bir hata oluştu.');
            return;
        }

        const data = await response.json();
        alert(data.message);
    } catch (error) {
        alert('Network hatası oluştu.');
    }
});
</script>
@endsection
