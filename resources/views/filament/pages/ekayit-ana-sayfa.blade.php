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
            @php($listUrl = \App\Filament\Resources\EkayitKayitResource::getUrl('index'))

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                @foreach ($sinifler as $item)
                    @php($sinif = $item['sinif'])

                    <a href="{{ $listUrl }}?tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                       class="group block rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $sinif->ad }}</h3>
                        </div>

                        <div class="my-6">
                            <div class="flex items-center justify-between border-b border-gray-100 py-3 dark:border-gray-800">
                                <span class="text-sm text-orange-400">Bekleyen</span>
                                <span class="text-right text-sm text-orange-500">{{ $item['bekleyen'] }}</span>
                            </div>

                            <div class="flex items-center justify-between border-b border-gray-100 py-3 dark:border-gray-800">
                                <span class="text-sm text-green-600">Onay</span>
                                <span class="text-right text-sm text-green-600">{{ $item['onaylanan'] }}</span>
                            </div>

                            <div class="flex items-center justify-between border-b border-gray-100 py-3 dark:border-gray-800">
                                <span class="text-sm text-red-600">Red</span>
                                <span class="text-right text-sm text-red-600">{{ $item['reddedilen'] }}</span>
                            </div>

                            <div class="flex items-center justify-between border-b border-gray-100 py-3 dark:border-gray-800">
                                <span class="text-sm text-blue-500">Yedek</span>
                                <span class="text-right text-sm text-blue-500">{{ $item['yedek'] }}</span>
                            </div>
                        </div>

                        <div class="text-xs text-gray-400 dark:text-gray-500">Toplam: {{ $item['toplam'] }} kayıt</div>
                    </a>
                @endforeach
            </div>
        @endif

    </div>
</x-filament-panels::page>
