@php
    /** @var \App\Models\SmsExcelGonderim $record */
    // ViewColumn'dan ($getRecord) veya modal action'dan (compact) gelebilir
    if (!isset($record) && isset($getRecord)) {
        $record = $getRecord();
    }
    $hataliNumaralar = $record->hatali_numaralar ?? [];
    $gonderilenNumaralar = $record->gonderilen_numaralar ?? [];
@endphp

<div class="space-y-4">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Toplam Satir</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $record->toplam_satir }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Gecerli</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $record->gecerli_satir }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Mukerrer</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $record->mukerrer }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Hatali Format</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $record->hatali_format }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Bos</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $record->bos }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Bekleyen</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $record->bekleyen }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:col-span-2">
            <p class="text-xs text-gray-500">Yonetici</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $record->yonetici?->ad_soyad ?? '-' }}</p>
        </div>
    </div>

    @if(filled($record->hata_mesaji))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3">
            <p class="text-xs font-semibold text-red-700">Hata Mesaji</p>
            <p class="mt-1 text-sm text-red-700">{{ $record->hata_mesaji }}</p>
        </div>
    @endif

    <div class="grid gap-3 lg:grid-cols-2">
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
            <p class="text-xs font-semibold text-amber-700">Hatali Numaralar ({{ count($hataliNumaralar) }})</p>
            @if($hataliNumaralar === [])
                <p class="mt-2 text-sm text-amber-700">Kayit yok.</p>
            @else
                <div class="mt-2 max-h-40 overflow-y-auto rounded border border-amber-200 bg-white p-2">
                    <ul class="space-y-1">
                        @foreach($hataliNumaralar as $numara)
                            <li class="font-mono text-xs text-gray-800">{{ $numara }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
            <p class="text-xs font-semibold text-emerald-700">Gonderilen Numaralar ({{ count($gonderilenNumaralar) }})</p>
            @if($gonderilenNumaralar === [])
                <p class="mt-2 text-sm text-emerald-700">Kayit yok.</p>
            @else
                <div class="mt-2 max-h-40 overflow-y-auto rounded border border-emerald-200 bg-white p-2">
                    <ul class="space-y-1">
                        @foreach($gonderilenNumaralar as $numara)
                            <li class="font-mono text-xs text-gray-800">{{ $numara }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
