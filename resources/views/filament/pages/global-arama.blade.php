<x-filament-panels::page>
    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <label class="mb-2 block text-sm font-medium text-gray-700">Arama</label>
            <input
                type="text"
                wire:model.live.debounce.300ms="arama"
                placeholder="Haber, etkinlik, kurumsal sayfa, kişi veya kurum ara..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
            <p class="mt-2 text-xs text-gray-500">Toplam sonuç: {{ $toplamSonuc }}</p>
        </div>

        @php
            $gruplar = [
                'haberler' => 'Haberler',
                'etkinlikler' => 'Etkinlikler',
                'kurumsal_sayfalar' => 'Kurumsal Sayfalar',
                'kisiler' => 'Kişiler',
                'kurumlar' => 'Kurumlar',
            ];
        @endphp

        @foreach($gruplar as $anahtar => $baslik)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900">{{ $baslik }} ({{ count($sonuclar[$anahtar] ?? []) }})</h3>

                @if(empty($sonuclar[$anahtar] ?? []))
                    <p class="mt-2 text-sm text-gray-500">Sonuç yok.</p>
                @else
                    <div class="mt-3 space-y-2">
                        @foreach($sonuclar[$anahtar] as $sonuc)
                            <a href="{{ $sonuc['link'] }}" class="block rounded-lg border border-gray-100 px-3 py-2 hover:border-primary-300 hover:bg-primary-50/30">
                                <p class="text-sm font-medium text-gray-900">{{ $sonuc['baslik'] }}</p>
                                @if(! empty($sonuc['ozet']))
                                    <p class="mt-1 text-xs text-gray-600">{{ \Illuminate\Support\Str::limit(strip_tags((string) $sonuc['ozet']), 120) }}</p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
