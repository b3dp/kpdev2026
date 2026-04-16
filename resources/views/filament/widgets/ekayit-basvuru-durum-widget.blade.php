<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-950">
                        E-Kayıt Başvuru Durumları
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{ $this->getDonem()?->ad ?? 'Aktif dönem bulunamadı' }}
                    </p>
                </div>
            </div>

            @php
                $sinifler = $this->getSiniflerWithStats();
                $listeUrl = $this->getEkayitListeUrl();
                $renkler = [
                    'blue' => ['border' => 'border-blue-500', 'bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'badge' => 'bg-blue-100 text-blue-800'],
                    'green' => ['border' => 'border-green-500', 'bg' => 'bg-green-50', 'text' => 'text-green-700', 'badge' => 'bg-green-100 text-green-800'],
                    'orange' => ['border' => 'border-orange-500', 'bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'badge' => 'bg-orange-100 text-orange-800'],
                    'purple' => ['border' => 'border-purple-500', 'bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'badge' => 'bg-purple-100 text-purple-800'],
                    'red' => ['border' => 'border-red-500', 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'badge' => 'bg-red-100 text-red-800'],
                    'amber' => ['border' => 'border-amber-500', 'bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'badge' => 'bg-amber-100 text-amber-800'],
                    'teal' => ['border' => 'border-teal-500', 'bg' => 'bg-teal-50', 'text' => 'text-teal-700', 'badge' => 'bg-teal-100 text-teal-800'],
                    'lime' => ['border' => 'border-lime-500', 'bg' => 'bg-lime-50', 'text' => 'text-lime-700', 'badge' => 'bg-lime-100 text-lime-800'],
                    'pink' => ['border' => 'border-pink-500', 'bg' => 'bg-pink-50', 'text' => 'text-pink-700', 'badge' => 'bg-pink-100 text-pink-800'],
                    'yellow' => ['border' => 'border-yellow-500', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'badge' => 'bg-yellow-100 text-yellow-800'],
                ];
            @endphp

            @if ($sinifler === [])
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">
                    Bu dönem için aktif sınıf bulunamadı.
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($sinifler as $item)
                        @php
                            $sinif = $item['sinif'];
                            $cls = $renkler[$sinif->renk ?? 'blue'] ?? $renkler['blue'];
                        @endphp

                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-l-4 p-5 {{ $cls['border'] }}">
                                <div class="mb-4 flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-base font-semibold text-gray-900">{{ $sinif->ad }}</div>
                                        <div class="text-xs text-gray-500">{{ $sinif->kurum?->ad ?? '—' }}</div>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $cls['badge'] }}">
                                        {{ $item['basvuru'] }} Başvuru
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <a href="{{ $listeUrl }}?tableFilters[sinif_id][values][0]={{ $sinif->id }}" class="rounded-xl {{ $cls['bg'] }} px-3 py-3 transition hover:opacity-90">
                                        <div class="text-xs text-gray-500">Başvuru</div>
                                        <div class="mt-1 text-lg font-bold {{ $cls['text'] }}">{{ $item['basvuru'] }}</div>
                                    </a>

                                    <a href="{{ $listeUrl }}?tableFilters[durum][values][0]=beklemede&tableFilters[sinif_id][values][0]={{ $sinif->id }}" class="rounded-xl bg-yellow-50 px-3 py-3 transition hover:opacity-90">
                                        <div class="text-xs text-gray-500">Bekleyen</div>
                                        <div class="mt-1 text-lg font-bold text-yellow-700">{{ $item['bekleyen'] }}</div>
                                    </a>

                                    <a href="{{ $listeUrl }}?tableFilters[durum][values][0]=onaylandi&tableFilters[sinif_id][values][0]={{ $sinif->id }}" class="rounded-xl bg-green-50 px-3 py-3 transition hover:opacity-90">
                                        <div class="text-xs text-gray-500">Onaylanan</div>
                                        <div class="mt-1 text-lg font-bold text-green-700">{{ $item['onaylanan'] }}</div>
                                    </a>

                                    <a href="{{ $listeUrl }}?tableFilters[durum][values][0]=reddedildi&tableFilters[sinif_id][values][0]={{ $sinif->id }}" class="rounded-xl bg-red-50 px-3 py-3 transition hover:opacity-90">
                                        <div class="text-xs text-gray-500">Red</div>
                                        <div class="mt-1 text-lg font-bold text-red-700">{{ $item['reddedilen'] }}</div>
                                    </a>

                                    <a href="{{ $listeUrl }}?tableFilters[durum][values][0]=yedek&tableFilters[sinif_id][values][0]={{ $sinif->id }}" class="col-span-2 rounded-xl bg-blue-50 px-3 py-3 transition hover:opacity-90">
                                        <div class="text-xs text-gray-500">Yedek</div>
                                        <div class="mt-1 text-lg font-bold text-blue-700">{{ $item['yedek'] }}</div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>