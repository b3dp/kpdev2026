<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-semibold text-gray-900">Log Arşivi</div>
                <div class="mt-2 text-sm text-gray-600">Klasör: backups/logs</div>
                <div class="mt-1 text-sm text-gray-600">Retention: son 3 ay</div>
                <div class="mt-3 text-2xl font-bold text-gray-900">{{ count($this->log_yedekleri) }}</div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-semibold text-gray-900">Günlük DB</div>
                <div class="mt-2 text-sm text-gray-600">Klasör: backups/db/daily</div>
                <div class="mt-1 text-sm text-gray-600">Retention: son 15 gün</div>
                <div class="mt-3 text-2xl font-bold text-gray-900">{{ count($this->gunluk_db_yedekleri) }}</div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-semibold text-gray-900">Aylık DB</div>
                <div class="mt-2 text-sm text-gray-600">Klasör: backups/db/monthly</div>
                <div class="mt-1 text-sm text-gray-600">Retention: son 6 ay</div>
                <div class="mt-3 text-2xl font-bold text-gray-900">{{ count($this->aylik_db_yedekleri) }}</div>
            </div>
        </div>

        @php
            $bolumler = [
                'Log Arşivleri' => $this->log_yedekleri,
                'Günlük DB Yedekleri' => $this->gunluk_db_yedekleri,
                'Aylık DB Yedekleri' => $this->aylik_db_yedekleri,
            ];
        @endphp

        @foreach ($bolumler as $baslik => $yedekler)
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">{{ $baslik }}</h3>
                </div>

                @if (count($yedekler) === 0)
                    <div class="px-5 py-8 text-sm text-gray-500">
                        Bu bölümde henüz yedek görünmüyor.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left font-medium text-gray-600">Dosya</th>
                                    <th class="px-5 py-3 text-left font-medium text-gray-600">Yol</th>
                                    <th class="px-5 py-3 text-left font-medium text-gray-600">Boyut</th>
                                    <th class="px-5 py-3 text-left font-medium text-gray-600">Son Değişim</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($yedekler as $yedek)
                                    <tr>
                                        <td class="px-5 py-3 font-medium text-gray-900">{{ $yedek['dosya_adi'] }}</td>
                                        <td class="px-5 py-3 text-gray-600">{{ $yedek['yol'] }}</td>
                                        <td class="px-5 py-3 text-gray-600">{{ $yedek['boyut_formatli'] }}</td>
                                        <td class="px-5 py-3 text-gray-600">{{ $yedek['degisim_formatli'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</x-filament-panels::page>