@if($htmlIcerik)
    <iframe
        srcdoc="{{ e($htmlIcerik) }}"
        style="width:100%;height:600px;border:none;border-radius:8px;"
        sandbox="allow-same-origin">
    </iframe>
@else
    <p class="text-gray-400 text-center py-8">Bu gonderim icin icerik kaydedilmemis.</p>
@endif
