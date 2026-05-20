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
    $aramaUrl = \App\Filament\Pages\GlobalArama::getUrl();
@endphp

<div class="flex shrink-0 items-center gap-2 mr-2">

    {{-- Arama butonu --}}
    <a
        href="{{ $aramaUrl }}"
        title="Global Arama"
        style="display:flex;align-items:center;gap:7px;border-radius:999px;border:1px solid #d1d5db;background:#f9fafb;padding:7px 14px;font-size:13px;font-weight:600;color:#374151;transition:all .2s ease;"
        onmouseover="this.style.borderColor='#93c5fd';this.style.background='#eff6ff';this.style.color='#1d4ed8';"
        onmouseout="this.style.borderColor='#d1d5db';this.style.background='#f9fafb';this.style.color='#374151';"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <span>Ara</span>
    </a>

    {{-- Yeni içerik dropdown --}}
    <x-filament::dropdown placement="bottom-end" width="xs" teleport>
        <x-slot name="trigger">
            <button
                type="button"
                class="shrink-0"
                style="display:flex;align-items:center;gap:8px;border-radius:999px;border:1px solid #8fb4ea;background:#e9f2ff;padding:8px 14px;font-size:14px;font-weight:800;color:#1450a3;transition:all .2s ease;"
            >
                <span style="font-size:30px;font-weight:900;line-height:1;color:#1e5ec2;">+</span>
                <span style="letter-spacing:.14em;">YENI</span>
            </button>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach($menuOgeleri as $oge)
                <x-filament::dropdown.list.item tag="a" :href="$oge['url']">
                    {{ $oge['etiket'] }}
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>

</div>
