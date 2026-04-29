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
                $renkHarita = [
                    'blue'   => ['rgb' => '59,130,246',  'text' => 'text-blue-700', 'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300'],
                    'green'  => ['rgb' => '34,197,94',   'text' => 'text-green-700', 'badge' => 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300'],
                    'red'    => ['rgb' => '239,68,68',   'text' => 'text-red-700', 'badge' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'],
                    'orange' => ['rgb' => '249,115,22',  'text' => 'text-orange-700', 'badge' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300'],
                    'purple' => ['rgb' => '168,85,247',  'text' => 'text-purple-700', 'badge' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300'],
                    'amber'  => ['rgb' => '245,158,11',  'text' => 'text-amber-700', 'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
                    'teal'   => ['rgb' => '20,184,166',  'text' => 'text-teal-700', 'badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300'],
                    'lime'   => ['rgb' => '132,204,22',  'text' => 'text-lime-700', 'badge' => 'bg-lime-100 text-lime-700 dark:bg-lime-500/15 dark:text-lime-300'],
                    'pink'   => ['rgb' => '236,72,153',  'text' => 'text-pink-700', 'badge' => 'bg-pink-100 text-pink-700 dark:bg-pink-500/15 dark:text-pink-300'],
                    'yellow' => ['rgb' => '234,179,8',   'text' => 'text-yellow-700', 'badge' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-300'],
                ];
            @endphp

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 2xl:grid-cols-3">
                @foreach ($sinifler as $item)
                    @php
                        $sinif   = $item['sinif'];
                        $renk    = $sinif->renk ?? 'blue';
                        $cls     = $renkHarita[$renk] ?? $renkHarita['blue'];
                        $rgb     = $cls['rgb'];
                        $listUrl = \App\Filament\Resources\EkayitKayitResource::getUrl('index');

                        $cardStyle = "background: linear-gradient(180deg, rgba({$rgb}, 0.12) 0%, rgba({$rgb}, 0.06) 100%); border-left: 4px solid rgba({$rgb}, 0.9);";
                        $rowStyle = "background-color: rgba(255,255,255,0.72); border: 1px solid rgba({$rgb}, 0.12);";
                    @endphp

                    <x-filament::section compact class="overflow-hidden" style="{{ $cardStyle }}">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-semibold text-gray-950 dark:text-white">{{ $sinif->ad }}</h3>
                                    <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">{{ $sinif->kurum?->ad ?? '—' }}</p>
                                </div>

                                <a
                                    href="{{ $listUrl }}?tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                                    class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $cls['badge'] }}"
                                >
                                    {{ $item['toplam'] }} kayıt
                                </a>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <a
                                    href="{{ $listUrl }}?tableFilters[durum][values][0]=beklemede&tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                                    class="rounded-xl p-3 transition hover:-translate-y-0.5 hover:shadow-sm"
                                    style="{{ $rowStyle }}"
                                >
                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Bekleyen</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $item['bekleyen'] }}</div>
                                </a>

                                <a
                                    href="{{ $listUrl }}?tableFilters[durum][values][0]=onaylandi&tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                                    class="rounded-xl p-3 transition hover:-translate-y-0.5 hover:shadow-sm"
                                    style="{{ $rowStyle }}"
                                >
                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Onay</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $item['onaylanan'] }}</div>
                                </a>

                                <a
                                    href="{{ $listUrl }}?tableFilters[durum][values][0]=yedek&tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                                    class="rounded-xl p-3 transition hover:-translate-y-0.5 hover:shadow-sm"
                                    style="{{ $rowStyle }}"
                                >
                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Yedek</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $item['yedek'] }}</div>
                                </a>
                            </div>

                            <div class="flex items-center justify-between rounded-xl px-4 py-3" style="{{ $rowStyle }}">
                                <div>
                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Toplam Başvuru</div>
                                    <div class="mt-1 text-lg font-semibold {{ $cls['text'] }}">{{ $item['toplam'] }}</div>
                                </div>

                                <a
                                    href="{{ $listUrl }}?tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                                    class="text-sm font-medium {{ $cls['text'] }}"
                                >
                                    Tümünü Gör
                                </a>
                            </div>
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @endif

    </div>
</x-filament-panels::page>
