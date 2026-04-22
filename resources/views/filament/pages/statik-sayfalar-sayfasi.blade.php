<x-filament-panels::page>
    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600">
            Bu sayfalar panelden içerik olarak yönetilmez. URL yapısı sabittir: <strong>/kurumsal/{slug}</strong>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Sayfa</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">URL</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Durum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($this->sayfalar as $sayfa)
                        @php
                            $url = route('kurumsal.show', ['slug' => $sayfa['slug']]);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $sayfa['ad'] }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ $url }}" target="_blank" class="text-primary-600 hover:underline">{{ $url }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $sayfa['aktif'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $sayfa['aktif'] ? 'Aktif' : 'Hazırlanıyor' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
