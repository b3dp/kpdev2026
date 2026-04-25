@php
    $menuOgeleri = [
        [
            'etiket' => 'Yeni haber',
            'url' => \App\Filament\Resources\HaberResource::getUrl('create'),
        ],
        [
            'etiket' => 'Yeni Kurumsal Sayfa',
            'url' => \App\Filament\Resources\KurumsalSayfaResource::getUrl('create'),
        ],
        [
            'etiket' => 'Yeni Kayıt',
            'url' => \App\Filament\Resources\EkayitKayitResource::getUrl('create'),
        ],
        [
            'etiket' => 'Yeni Kişi',
            'url' => \App\Filament\Resources\KisiResource::getUrl('create'),
        ],
    ];
@endphp

<div
    x-data="{ acik: false }"
    class="relative mr-2"
    x-on:mouseenter="acik = true"
    x-on:mouseleave="acik = false"
    x-on:keydown.escape.window="acik = false"
>
    <button
        type="button"
        x-on:focus="acik = true"
        x-on:click="acik = ! acik"
        style="display:flex;align-items:center;gap:8px;border-radius:999px;border:1px solid #8fb4ea;background:#e9f2ff;padding:8px 14px;font-size:14px;font-weight:800;color:#1450a3;transition:all .2s ease;"
    >
        <span style="font-size:30px;font-weight:900;line-height:1;color:#1e5ec2;">+</span>
        <span style="letter-spacing:.14em;">YENI</span>
    </button>

    <div
        x-cloak
        x-show="acik"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        x-on:click.outside="acik = false"
        x-on:mouseenter="acik = true"
        x-on:mouseleave="acik = false"
        class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
        style="display: none;"
    >
        <div class="border-b border-slate-100 px-4 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
            Hızlı İşlem
        </div>

        <div class="p-2">
            @foreach($menuOgeleri as $oge)
                <a
                    href="{{ $oge['url'] }}"
                    class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700"
                >
                    {{ $oge['etiket'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>
