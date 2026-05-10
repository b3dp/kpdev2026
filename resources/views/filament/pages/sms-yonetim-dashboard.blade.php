<x-filament-panels::page>
    <div class="space-y-6">
        <!-- İstatistikler ve Kredi Yönetimi -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @php
                $bugunBasarili = \App\Models\SmsGonderim::whereDate('created_at', today())->sum('basarili') ?? 0;
                $bugunBasarisiz = \App\Models\SmsGonderim::whereDate('created_at', today())->sum('basarisiz') ?? 0;
                $rehberSayisi = \App\Models\SmsKisi::count();
                $kalanKredi = \App\Models\SmsKredi::getKalanKredi();
            @endphp

            <!-- Başarılı SMS -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Bugün Başarılı SMS</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($bugunBasarili, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-green-500 text-4xl">✓</div>
                </div>
            </div>

            <!-- Başarısız SMS -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Bugün Başarısız SMS</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($bugunBasarisiz, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-red-500 text-4xl">✗</div>
                </div>
            </div>

            <!-- Rehber Sayısı -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Rehber Sayısı</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($rehberSayisi, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-blue-500 text-4xl">👥</div>
                </div>
            </div>

            <!-- Kalan Kredi -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 {{ $kalanKredi < 1000 ? 'border-orange-500' : 'border-blue-500' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Kalan Kredi</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($kalanKredi, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-blue-500 text-4xl">₺</div>
                </div>
                @if($kalanKredi < 1000)
                    <p class="text-orange-600 text-xs mt-2 font-semibold">Kredi azalıyor</p>
                @endif
            </div>

            <!-- Kredi Ekleme -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                <div class="flex flex-col h-full">
                    <p class="text-gray-500 text-sm mb-4 font-semibold">Kredi Ekle</p>
                    <form action="{{ route('filament.admin.sms-kredi-ekle') }}" method="POST" class="flex flex-col gap-3 flex-1">
                        @csrf
                        <input
                            type="number"
                            name="miktar"
                            placeholder="Miktar"
                            required
                            min="1"
                            class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500"
                        />
                        <button
                            type="submit"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold transition text-sm"
                        >
                            Ekle
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Son SMS Gönderimler Tablosu -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Son 10 SMS Gönderimi</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Tip</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-700">Alıcı</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-700">Başarılı</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-700">Başarısız</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-700">Durum</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Gönderme Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(\App\Models\SmsGonderim::latest('created_at')->limit(10)->get() as $gonderim)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4">{{ ucfirst($gonderim->tip) }}</td>
                                <td class="px-6 py-4 text-center font-semibold">{{ number_format($gonderim->alici_sayisi, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">{{ number_format($gonderim->basarili, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">{{ number_format($gonderim->basarisiz, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($gonderim->durum === 'tamamlandi')
                                        <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold">Tamamlandı</span>
                                    @elseif($gonderim->durum === 'basarisiz')
                                        <span class="inline-block bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold">Başarısız</span>
                                    @elseif($gonderim->durum === 'gonderiliyor')
                                        <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold">Gönderiliyor</span>
                                    @else
                                        <span class="inline-block bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-semibold">{{ ucfirst($gonderim->durum) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ optional($gonderim->created_at)->format('d.m.Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    SMS gönderimi bulunmamaktadır.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
