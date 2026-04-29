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
            @php
                // Tailwind'in purge etmemesi için sabit sınıf listesi her renk için bir kez tanımlanıyor:
                // text-blue-800 text-green-800 text-red-800 text-orange-800 text-purple-800 text-amber-800 text-teal-800 text-lime-800 text-pink-800 text-yellow-800
                $renkHarita = [
                    'blue'   => ['rgb' => '59,130,246',  'text' => 'text-blue-800'],
                    'green'  => ['rgb' => '34,197,94',   'text' => 'text-green-800'],
                    'red'    => ['rgb' => '239,68,68',   'text' => 'text-red-800'],
                    'orange' => ['rgb' => '249,115,22',  'text' => 'text-orange-800'],
                    'purple' => ['rgb' => '168,85,247',  'text' => 'text-purple-800'],
                    'amber'  => ['rgb' => '245,158,11',  'text' => 'text-amber-800'],
                    'teal'   => ['rgb' => '20,184,166',  'text' => 'text-teal-800'],
                    'lime'   => ['rgb' => '132,204,22',  'text' => 'text-lime-800'],
                    'pink'   => ['rgb' => '236,72,153',  'text' => 'text-pink-800'],
                    'yellow' => ['rgb' => '234,179,8',   'text' => 'text-yellow-800'],
                ];
            @endphp

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($sinifler as $item)
                    @php
                        $sinif   = $item['sinif'];
                        $renk    = $sinif->renk ?? 'blue';
                        $cls     = $renkHarita[$renk] ?? $renkHarita['blue'];
                        $rgb     = $cls['rgb'];
                        $listUrl = \App\Filament\Resources\EkayitKayitResource::getUrl('index');

                        $bgStyle     = "background-color: rgba({$rgb}, 0.10);";
                        $borderStyle = "border-color: rgba({$rgb}, 0.35);";
                        $headerStyle = "background-color: rgba({$rgb}, 0.18); border-bottom: 1px solid rgba({$rgb}, 0.25);";
                        $totalStyle  = "background-color: rgba({$rgb}, 0.18); color: rgba({$rgb}, 1); border-top: 1px solid rgba({$rgb}, 0.2);";
                    @endphp

                    <div class="overflow-hidden rounded-2xl border shadow-sm" style="{{ $bgStyle }} {{ $borderStyle }}">

                        {{-- Kart Başlığı --}}
                        <div class="px-4 py-3" style="{{ $headerStyle }}">
                            <div class="text-[13px] font-bold leading-snug {{ $cls['text'] }}">{{ $sinif->ad }}</div>
                            <div class="mt-0.5 truncate text-[11px] text-gray-500">{{ $sinif->kurum?->ad ?? '—' }}</div>
                        </div>

                        {{-- İstatistikler --}}
                        <div class="divide-y divide-black/5 px-3 py-2">
                            <a href="{{ $listUrl }}?tableFilters[durum][values][0]=beklemede&tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                               class="flex items-center justify-between py-1.5 text-xs transition hover:opacity-70">
                                <span class="text-gray-600">Bekleyen</span>
                                <span class="min-w-[22px] rounded-full bg-yellow-200 px-1.5 py-0.5 text-center text-[11px] font-bold text-yellow-800">{{ $item['bekleyen'] }}</span>
                            </a>
                            <a href="{{ $listUrl }}?tableFilters[durum][values][0]=onaylandi&tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                               class="flex items-center justify-between py-1.5 text-xs transition hover:opacity-70">
                                <span class="text-gray-600">Onaylanan</span>
                                <span class="min-w-[22px] rounded-full bg-green-200 px-1.5 py-0.5 text-center text-[11px] font-bold text-green-800">{{ $item['onaylanan'] }}</span>
                            </a>
                            <a href="{{ $listUrl }}?tableFilters[durum][values][0]=yedek&tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                               class="flex items-center justify-between py-1.5 text-xs transition hover:opacity-70">
                                <span class="text-gray-600">Yedek</span>
                                <span class="min-w-[22px] rounded-full bg-sky-200 px-1.5 py-0.5 text-center text-[11px] font-bold text-sky-800">{{ $item['yedek'] }}</span>
                            </a>
                        </div>

                        {{-- Toplam --}}
                        <a href="{{ $listUrl }}?tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                           class="flex items-center justify-between px-4 py-2 text-xs font-bold transition hover:opacity-80"
                           style="{{ $totalStyle }}">
                            <span>Toplam</span>
                            <span class="text-sm">{{ $item['toplam'] }} kayıt</span>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-filament-panels::page>
