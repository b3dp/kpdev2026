<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Kestane Pazarı')</title>
    
    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    {{-- Navigation --}}
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-blue-600">
                        Kestane Pazarı
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    @auth('uye')
                        <span class="text-gray-700">{{ auth('uye')->user()->ad_soyad }}</span>
                        <a href="{{ route('uye.profil.index') }}" class="text-blue-600 hover:text-blue-700">
                            Profil
                        </a>
                        <form action="{{ route('uye.cikis') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-700">
                                Çıkış
                            </button>
                        </form>
                    @endauth
                    @guest('uye')
                        <a href="{{ route('uye.giris.form') }}" class="text-blue-600 hover:text-blue-700">
                            Giriş
                        </a>
                        <a href="{{ route('uye.kayit.form') }}" class="text-blue-600 hover:text-blue-700">
                            Kayıt
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="py-8">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-800 text-white mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <p class="text-center text-gray-400">
                &copy; 2026 Kestane Pazarı Derneği. Tüm hakları saklıdır.
            </p>
        </div>
    </footer>
</body>
</html>
