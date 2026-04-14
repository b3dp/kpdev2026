@php
    $guvenliIcerik = new \Illuminate\Support\HtmlString(str_replace('"', '&quot;', (string) $htmlIcerik));
@endphp

@if($htmlIcerik)
    <iframe
        srcdoc="{{ $guvenliIcerik }}"
        style="width:100%;height:600px;border:none;border-radius:8px;"
        sandbox="allow-same-origin">
    </iframe>
@else
    <p class="text-gray-400 text-center py-8">Bu gönderim için içerik kaydedilmemiş.</p>
@endif
