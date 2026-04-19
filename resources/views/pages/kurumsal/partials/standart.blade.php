@php
    $icerikHtml = trim((string) $sayfa->icerik);
@endphp

@if($sayfa->slug === 'hakkimizda')
    <section id="tarihce" class="kurumsal-section-card">
        <p class="kurumsal-eyebrow">Geçmişten Bugüne</p>
        <h2 class="kurumsal-section-title">Tarihçe</h2>

        <div class="kurumsal-prose">
            @if($icerikHtml)
                {!! $icerikHtml !!}
            @else
                <p>{{ $sayfa->ozet ?: 'Bu sayfaya ait kurumsal içerik yakında güncellenecektir.' }}</p>
            @endif
        </div>

        @if($sayfa->video_embed_url)
            <div class="mt-6 overflow-hidden rounded-[20px] border border-[#162e4b]/10 bg-white shadow-sm">
                <iframe
                    src="{{ $sayfa->video_embed_url }}"
                    title="{{ $sayfa->ad }} videosu"
                    class="aspect-video w-full"
                    loading="lazy"
                    allowfullscreen
                ></iframe>
            </div>
        @endif
    </section>
@else
    <section class="kurumsal-section-card">
        <p class="kurumsal-eyebrow">Kurumsal içerik</p>
        <h2 class="kurumsal-section-title">{{ $sayfa->ad }}</h2>

        <div class="kurumsal-prose">
            @if($icerikHtml)
                {!! $icerikHtml !!}
            @else
                <p>{{ $sayfa->ozet ?: 'Bu sayfaya ait kurumsal içerik yakında güncellenecektir.' }}</p>
            @endif
        </div>

        @if($sayfa->video_embed_url)
            <div class="mt-6 overflow-hidden rounded-[20px] border border-[#162e4b]/10 bg-white shadow-sm">
                <iframe
                    src="{{ $sayfa->video_embed_url }}"
                    title="{{ $sayfa->ad }} videosu"
                    class="aspect-video w-full"
                    loading="lazy"
                    allowfullscreen
                ></iframe>
            </div>
        @endif
    </section>
@endif
