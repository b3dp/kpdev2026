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

            <div class="grid grid-cols-1 md:grid-cols-3" style="gap: 18px;">
                @foreach ($sinifler as $item)
                    @php($sinif = $item['sinif'])

                    <a href="{{ $listUrl }}?tableFilters[sinif_id][values][0]={{ $sinif->id }}"
                       class="group block transition"
                       style="border-radius: 16px; border: 1px solid #e5e7eb; background: #ffffff; padding: 22px; box-shadow: 0 1px 2px rgba(16, 24, 40, 0.08); text-decoration: none;">
                        <div class="flex items-start justify-between">
                            <h3 style="font-size: 22px; line-height: 1.2; font-weight: 700; color: #111827;">{{ $sinif->ad }}</h3>
                        </div>

                        <div style="margin-top: 18px; margin-bottom: 18px;">
                            <div class="flex items-center justify-between" style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                <span style="font-size: 15px; color: #f97316; font-weight: 600;">Bekleyen</span>
                                <span style="font-size: 15px; color: #f97316; font-weight: 600;">{{ $item['bekleyen'] }}</span>
                            </div>

                            <div class="flex items-center justify-between" style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                <span style="font-size: 15px; color: #16a34a; font-weight: 600;">Onay</span>
                                <span style="font-size: 15px; color: #16a34a; font-weight: 600;">{{ $item['onaylanan'] }}</span>
                            </div>

                            <div class="flex items-center justify-between" style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                <span style="font-size: 15px; color: #dc2626; font-weight: 600;">Red</span>
                                <span style="font-size: 15px; color: #dc2626; font-weight: 600;">{{ $item['reddedilen'] }}</span>
                            </div>

                            <div class="flex items-center justify-between" style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                <span style="font-size: 15px; color: #3b82f6; font-weight: 600;">Yedek</span>
                                <span style="font-size: 15px; color: #3b82f6; font-weight: 600;">{{ $item['yedek'] }}</span>
                            </div>
                        </div>

                        <div style="font-size: 14px; color: #9ca3af;">Toplam: {{ $item['toplam'] }} kayıt</div>
                    </a>
                @endforeach
            </div>
        @endif

    </div>
</x-filament-panels::page>
