<x-filament-panels::page>
    <div class="space-y-6">

        <x-filament::section compact>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Sınıf Genel Durumu</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Seçili dönemdeki aktif sınıfların başvuru dağılımı.</p>
                </div>

                <label class="flex items-center gap-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Dönem</span>
                    <select
                        wire:model.live="donemId"
                        class="rounded-xl border-gray-300 bg-white text-sm shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                        @foreach ($this->getDonemler() as $donem)
                            <option value="{{ $donem->id }}">
                                {{ $donem->ad }}
                                @if ($donem->aktif) ✓ @endif
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>
        </x-filament::section>

        {{-- Sınıf Kartları --}}
        @php $sinifler = $this->getSiniflerWithStats(); @endphp

        @if (count($sinifler) === 0)
            <x-filament::section>
                <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    Bu döneme ait aktif sınıf bulunamadı.
                </div>
            </x-filament::section>
        @else
            @php
                $listUrl = \App\Filament\Resources\EkayitKayitResource::getUrl('index');
                $renkHarita = [
                    'blue'   => 'border-t-blue-500',
                    'green'  => 'border-t-green-500',
                    'red'    => 'border-t-red-500',
                    'orange' => 'border-t-orange-500',
                    'purple' => 'border-t-purple-500',
                    'amber'  => 'border-t-amber-500',
                    'teal'   => 'border-t-teal-500',
                    'lime'   => 'border-t-lime-500',
                    'pink'   => 'border-t-pink-500',
                    'yellow' => 'border-t-yellow-500',
                ];
            @endphp

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($sinifler as $item)
                    @php
                        $sinif  = $item['sinif'];
                        $renk   = $sinif->renk ?? 'blue';
                        $topCls = $renkHarita[$renk] ?? $renkHarita['blue'];
                    @endphp

                    <a href="{{ $listUrl }}?tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                       class="group block rounded-2xl border border-gray-200 bg-white shadow-sm ring-0 transition hover:shadow-md dark:border-gray-700 dark:bg-gray-900 border-t-4 {{ $topCls }}">

                        {{-- Başlık --}}
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                            <p class="text-base font-semibold text-gray-950 dark:text-white leading-tight">{{ $sinif->ad }}</p>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500 truncate">{{ $sinif->kurum?->ad ?? '—' }}</p>
                        </div>

                        {{-- Stat Satırları --}}
                        <div class="divide-y divide-gray-100 dark:divide-gray-800 px-5">
                            <div class="flex items-center justify-between py-3">
                                <span class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="inline-block h-2 w-2 rounded-full bg-orange-400"></span>
                                    Bekleyen
                                </span>
                                <span class="rounded-full bg-orange-50 px-2.5 py-0.5 text-sm font-semibold text-orange-600 dark:bg-orange-500/10 dark:text-orange-400">
                                    {{ $item['bekleyen'] }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between py-3">
                                <span class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                                    Onaylanan
                                </span>
                                <span class="rounded-full bg-green-50 px-2.5 py-0.5 text-sm font-semibold text-green-600 dark:bg-green-500/10 dark:text-green-400">
                                    {{ $item['onaylanan'] }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between py-3">
                                <span class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="inline-block h-2 w-2 rounded-full bg-red-400"></span>
                                    Reddedilen
                                </span>
                                <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-sm font-semibold text-red-600 dark:bg-red-500/10 dark:text-red-400">
                                    {{ $item['reddedilen'] }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between py-3">
                                <span class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="inline-block h-2 w-2 rounded-full bg-blue-400"></span>
                                    Yedek
                                </span>
                                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-sm font-semibold text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                                    {{ $item['yedek'] }}
                                </span>
                            </div>
                        </div>

                        {{-- Toplam --}}
                        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-400 dark:text-gray-500">Toplam</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-white">{{ $item['toplam'] }} kayıt</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

    </div>
</x-filament-panels::page>
