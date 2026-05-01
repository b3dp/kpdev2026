<div class="space-y-4">
    @if(count($hatali) > 0)
        <div>
            <h3 class="text-sm font-semibold text-red-600 mb-2">Hatalı Format ({{ count($hatali) }})</h3>
            <div class="bg-red-50 dark:bg-red-950 rounded-lg p-3 space-y-1 max-h-48 overflow-y-auto">
                @foreach($hatali as $item)
                    <div class="text-sm font-mono text-red-800 dark:text-red-200">{{ $item['numara'] }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if(count($mukerrerExcel) > 0)
        <div>
            <h3 class="text-sm font-semibold text-yellow-600 mb-2">Excel'de Mükerrer ({{ count($mukerrerExcel) }})</h3>
            <div class="bg-yellow-50 dark:bg-yellow-950 rounded-lg p-3 space-y-1 max-h-48 overflow-y-auto">
                @foreach($mukerrerExcel as $item)
                    <div class="text-sm font-mono text-yellow-800 dark:text-yellow-200">{{ $item['numara'] }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if(count($mukerrerDb) > 0)
        <div>
            <h3 class="text-sm font-semibold text-blue-600 mb-2">Veritabanında Mevcut ({{ count($mukerrerDb) }})</h3>
            <div class="bg-blue-50 dark:bg-blue-950 rounded-lg p-3 space-y-1 max-h-48 overflow-y-auto">
                @foreach($mukerrerDb as $item)
                    <div class="text-sm font-mono text-blue-800 dark:text-blue-200">{{ $item['numara'] }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if(count($hatali) === 0 && count($mukerrerExcel) === 0 && count($mukerrerDb) === 0)
        <p class="text-sm text-gray-500">Hatalı veya mükerrer numara bulunmuyor.</p>
    @endif
</div>
