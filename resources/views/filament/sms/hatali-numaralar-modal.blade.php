<div class="space-y-3">
    @if(empty($numaralar))
        <p class="text-sm text-gray-500">Hatali numara bulunamadi.</p>
    @else
        <p class="text-sm text-gray-600">Toplam {{ count($numaralar) }} hatali numara:</p>

        <div class="max-h-72 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-3">
            <ul class="space-y-1">
                @foreach($numaralar as $numara)
                    <li class="font-mono text-sm text-gray-800">{{ $numara }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
