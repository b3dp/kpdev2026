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

<div class="mr-2">
    <x-filament::dropdown placement="bottom-end" width="xs">
        <x-slot name="trigger">
            <button
                type="button"
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
