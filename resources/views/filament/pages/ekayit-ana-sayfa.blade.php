<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Dönem Seçici --}}
        <div class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Dönem:</label>
            <select
                wire:model.live="donemId"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                @foreach ($this->getDonemler() as $donem)
                    <option value="{{ $donem->id }}">
                        {{ $donem->ad }}
                        @if ($donem->aktif) ✓ @endif
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Sınıf Kartları --}}
        @php $sinifler = $this->getSiniflerWithStats(); @endphp

        @if (count($sinifler) === 0)
            <div class="rounded-xl border border-dashed border-gray-300 p-12 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                Bu döneme ait aktif sınıf bulunamadı.
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($sinifler as $item)
                    @php
                        $sinif = $item['sinif'];
                        $renk  = $sinif->renk ?? 'blue';

                        $renkler = [
                            'blue'   => ['border' => 'border-blue-500',   'bg' => 'bg-blue-50   dark:bg-blue-950',   'text' => 'text-blue-700   dark:text-blue-300',   'badge' => 'bg-blue-100   text-blue-800'],
                            'green'  => ['border' => 'border-green-500',  'bg' => 'bg-green-50  dark:bg-green-950',  'text' => 'text-green-700  dark:text-green-300',  'badge' => 'bg-green-100  text-green-800'],
                            'orange' => ['border' => 'border-orange-500', 'bg' => 'bg-orange-50 dark:bg-orange-950', 'text' => 'text-orange-700 dark:text-orange-300', 'badge' => 'bg-orange-100 text-orange-800'],
                            'purple' => ['border' => 'border-purple-500', 'bg' => 'bg-purple-50 dark:bg-purple-950', 'text' => 'text-purple-700 dark:text-purple-300', 'badge' => 'bg-purple-100 text-purple-800'],
                            'red'    => ['border' => 'border-red-500',    'bg' => 'bg-red-50    dark:bg-red-950',    'text' => 'text-red-700    dark:text-red-300',    'badge' => 'bg-red-100    text-red-800'],
                            'amber'  => ['border' => 'border-amber-500',  'bg' => 'bg-amber-50  dark:bg-amber-950',  'text' => 'text-amber-700  dark:text-amber-300',  'badge' => 'bg-amber-100  text-amber-800'],
                            'teal'   => ['border' => 'border-teal-500',   'bg' => 'bg-teal-50   dark:bg-teal-950',   'text' => 'text-teal-700   dark:text-teal-300',   'badge' => 'bg-teal-100   text-teal-800'],
                            'lime'   => ['border' => 'border-lime-500',   'bg' => 'bg-lime-50   dark:bg-lime-950',   'text' => 'text-lime-700   dark:text-lime-300',   'badge' => 'bg-lime-100   text-lime-800'],
                            'pink'   => ['border' => 'border-pink-500',   'bg' => 'bg-pink-50   dark:bg-pink-950',   'text' => 'text-pink-700   dark:text-pink-300',   'badge' => 'bg-pink-100   text-pink-800'],
                            'yellow' => ['border' => 'border-yellow-500', 'bg' => 'bg-yellow-50 dark:bg-yellow-950', 'text' => 'text-yellow-700 dark:text-yellow-300', 'badge' => 'bg-yellow-100 text-yellow-800'],
                        ];

                        $cls = $renkler[$renk] ?? $renkler['blue'];
                        $listUrl = \App\Filament\Resources\EkayitKayitResource::getUrl('index');
                    @endphp

                    <div class="overflow-hidden rounded-xl border-l-4 bg-white shadow-sm dark:bg-gray-800 {{ $cls['border'] }}">
                        <div class="p-5">
                            {{-- Başlık --}}
                            <div class="mb-1 text-base font-bold text-gray-900 dark:text-white">
                                {{ $sinif->ad }}
                            </div>
                            <div class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                                {{ $sinif->kurum?->ad ?? '—' }}
                            </div>

                            <div class="border-t border-gray-100 dark:border-gray-700"></div>

                            {{-- Sayılar --}}
                            <div class="mt-4 space-y-1.5 text-sm">
                                <a href="{{ $listUrl }}?tableFilters[durum][values][0]=beklemede&tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                                   class="flex items-center justify-between rounded-md px-2 py-1 transition hover:bg-yellow-50 dark:hover:bg-yellow-900/30">
                                    <span class="text-gray-600 dark:text-gray-300">Bekleyen</span>
                                    <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-800">{{ $item['bekleyen'] }}</span>
                                </a>
                                <a href="{{ $listUrl }}?tableFilters[durum][values][0]=onaylandi&tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                                   class="flex items-center justify-between rounded-md px-2 py-1 transition hover:bg-green-50 dark:hover:bg-green-900/30">
                                    <span class="text-gray-600 dark:text-gray-300">Onaylanan</span>
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">{{ $item['onaylanan'] }}</span>
                                </a>
                                <a href="{{ $listUrl }}?tableFilters[durum][values][0]=yedek&tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                                   class="flex items-center justify-between rounded-md px-2 py-1 transition hover:bg-blue-50 dark:hover:bg-blue-900/30">
                                    <span class="text-gray-600 dark:text-gray-300">Yedek</span>
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">{{ $item['yedek'] }}</span>
                                </a>

                                <div class="border-t border-gray-100 dark:border-gray-700"></div>

                                <div class="flex items-center justify-between px-2 py-1">
                                    <span class="font-medium text-gray-700 dark:text-gray-200">Toplam</span>
                                    <span class="font-bold {{ $cls['text'] }}">{{ $item['toplam'] }} kayıt</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-filament-panels::page>
