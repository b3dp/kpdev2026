<x-filament-panels::page>
@php
    $grupTanim = [
        'kisiler'           => ['etiket' => 'Kişiler',          'ikon' => 'heroicon-o-user',              'renk' => 'blue'],
        'uyeler'            => ['etiket' => 'Üyeler',           'ikon' => 'heroicon-o-users',             'renk' => 'indigo'],
        'haberler'          => ['etiket' => 'Haberler',         'ikon' => 'heroicon-o-newspaper',         'renk' => 'sky'],
        'ekayit_kayitlar'   => ['etiket' => 'E-Kayıt',          'ikon' => 'heroicon-o-academic-cap',      'renk' => 'emerald'],
        'bagislar'          => ['etiket' => 'Bağışlar',         'ikon' => 'heroicon-o-heart',             'renk' => 'rose'],
        'mezunlar'          => ['etiket' => 'Mezunlar',         'ikon' => 'heroicon-o-building-library',  'renk' => 'amber'],
        'etkinlikler'       => ['etiket' => 'Etkinlikler',      'ikon' => 'heroicon-o-calendar-days',     'renk' => 'violet'],
        'kurumlar'          => ['etiket' => 'Kurumlar',         'ikon' => 'heroicon-o-building-office-2', 'renk' => 'teal'],
        'kurumsal_sayfalar' => ['etiket' => 'Kurumsal Sayfalar','ikon' => 'heroicon-o-document-text',     'renk' => 'gray'],
    ];

    $renkSinif = [
        'blue'   => ['chip_aktif' => 'bg-blue-600 text-white',   'chip_pasif' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',   'baslik' => 'text-blue-700',   'bg' => 'bg-blue-50',   'icon_bg' => 'bg-blue-100 text-blue-600',   'hover' => 'hover:border-blue-300 hover:bg-blue-50/40'],
        'indigo' => ['chip_aktif' => 'bg-indigo-600 text-white', 'chip_pasif' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200', 'baslik' => 'text-indigo-700', 'bg' => 'bg-indigo-50', 'icon_bg' => 'bg-indigo-100 text-indigo-600', 'hover' => 'hover:border-indigo-300 hover:bg-indigo-50/40'],
        'sky'    => ['chip_aktif' => 'bg-sky-600 text-white',    'chip_pasif' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',       'baslik' => 'text-sky-700',    'bg' => 'bg-sky-50',    'icon_bg' => 'bg-sky-100 text-sky-600',       'hover' => 'hover:border-sky-300 hover:bg-sky-50/40'],
        'emerald'=> ['chip_aktif' => 'bg-emerald-600 text-white','chip_pasif' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200','baslik' => 'text-emerald-700','bg' => 'bg-emerald-50','icon_bg' => 'bg-emerald-100 text-emerald-600','hover' => 'hover:border-emerald-300 hover:bg-emerald-50/40'],
        'rose'   => ['chip_aktif' => 'bg-rose-600 text-white',   'chip_pasif' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',     'baslik' => 'text-rose-700',   'bg' => 'bg-rose-50',   'icon_bg' => 'bg-rose-100 text-rose-600',     'hover' => 'hover:border-rose-300 hover:bg-rose-50/40'],
        'amber'  => ['chip_aktif' => 'bg-amber-600 text-white',  'chip_pasif' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',   'baslik' => 'text-amber-700',  'bg' => 'bg-amber-50',  'icon_bg' => 'bg-amber-100 text-amber-600',   'hover' => 'hover:border-amber-300 hover:bg-amber-50/40'],
        'violet' => ['chip_aktif' => 'bg-violet-600 text-white', 'chip_pasif' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200','baslik' => 'text-violet-700', 'bg' => 'bg-violet-50', 'icon_bg' => 'bg-violet-100 text-violet-600', 'hover' => 'hover:border-violet-300 hover:bg-violet-50/40'],
        'teal'   => ['chip_aktif' => 'bg-teal-600 text-white',   'chip_pasif' => 'bg-teal-50 text-teal-700 ring-1 ring-teal-200',     'baslik' => 'text-teal-700',   'bg' => 'bg-teal-50',   'icon_bg' => 'bg-teal-100 text-teal-600',     'hover' => 'hover:border-teal-300 hover:bg-teal-50/40'],
        'gray'   => ['chip_aktif' => 'bg-gray-600 text-white',   'chip_pasif' => 'bg-gray-50 text-gray-700 ring-1 ring-gray-200',     'baslik' => 'text-gray-700',   'bg' => 'bg-gray-50',   'icon_bg' => 'bg-gray-100 text-gray-600',     'hover' => 'hover:border-gray-300 hover:bg-gray-50/40'],
    ];

    $aramaVar = strlen(trim($arama)) >= 2;
    $herhangiSonuc = $aramaVar && $toplamSonuc > 0;

    // Sadece sonucu olan gruplar
    $aktifGruplar = $aramaVar
        ? array_filter($grupTanim, fn($k) => !empty($sonuclar[$k] ?? []), ARRAY_FILTER_USE_KEY)
        : [];
@endphp

<div class="space-y-6">

    {{-- ─── ARAMA KUTUSU ─── --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="p-5">
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                    <div wire:loading.remove wire:target="arama">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                    </div>
                    <div wire:loading wire:target="arama">
                        <svg class="h-5 w-5 animate-spin text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.350ms="arama"
                    placeholder="Kişi, üye, haber, e-kayıt, bağış, mezun ara…"
                    autofocus
                    class="w-full rounded-xl border border-gray-300 bg-gray-50 py-3.5 pl-11 pr-10 text-base text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    x-data
                    x-on:keydown.escape="$wire.set('arama', '')"
                />
                @if(strlen(trim($arama)) > 0)
                <button
                    wire:click="$set('arama', '')"
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600"
                    type="button"
                    title="Temizle"
                >
                    <x-heroicon-o-x-circle class="h-5 w-5" />
                </button>
                @endif
            </div>

            {{-- Alt bilgi satırı --}}
            <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                <span>
                    @if($aramaVar && $toplamSonuc > 0)
                        <span class="font-semibold text-primary-600">{{ $toplamSonuc }}</span> sonuç bulundu
                    @elseif($aramaVar && $toplamSonuc === 0)
                        Hiç sonuç bulunamadı
                    @else
                        En az 2 karakter yazın
                    @endif
                </span>
                <span class="hidden items-center gap-1 sm:flex">
                    <kbd class="rounded border border-gray-200 bg-gray-100 px-1.5 py-0.5 font-mono text-xs">ESC</kbd>
                    <span>temizle</span>
                </span>
            </div>
        </div>

        {{-- Kategori chip'leri (sadece sonuç varsa göster) --}}
        @if($herhangiSonuc)
        <div class="border-t border-gray-100 px-5 py-3">
            <div class="flex flex-wrap gap-2">
                @foreach($aktifGruplar as $anahtar => $tanim)
                    @php
                        $sayi = count($sonuclar[$anahtar] ?? []);
                        $renk = $renkSinif[$tanim['renk']];
                    @endphp
                    @if($sayi > 0)
                    <a href="#grup-{{ $anahtar }}"
                       class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition {{ $renk['chip_pasif'] }} hover:opacity-80">
                        <x-dynamic-component :component="$tanim['ikon']" class="h-3.5 w-3.5" />
                        {{ $tanim['etiket'] }}
                        <span class="ml-0.5 rounded-full bg-white/70 px-1.5 py-0.5 text-xs font-bold leading-none">{{ $sayi }}</span>
                    </a>
                    @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ─── SONUÇLAR ─── --}}
    @if($aramaVar)

        @if($toplamSonuc === 0)
        {{-- Boş durum --}}
        <div class="rounded-2xl border border-dashed border-gray-200 bg-white py-16 text-center">
            <x-heroicon-o-magnifying-glass class="mx-auto h-10 w-10 text-gray-300" />
            <p class="mt-3 text-base font-medium text-gray-500">
                "<span class="text-gray-700">{{ $arama }}</span>" için sonuç bulunamadı
            </p>
            <p class="mt-1 text-sm text-gray-400">Farklı anahtar kelime deneyin</p>
        </div>
        @else

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
            @foreach($grupTanim as $anahtar => $tanim)
                @php
                    $liste = $sonuclar[$anahtar] ?? [];
                    $sayi  = count($liste);
                    $renk  = $renkSinif[$tanim['renk']];
                @endphp
                @if($sayi > 0)
                <div id="grup-{{ $anahtar }}" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">

                    {{-- Grup başlığı --}}
                    <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3 {{ $renk['bg'] }}">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $renk['icon_bg'] }}">
                            <x-dynamic-component :component="$tanim['ikon']" class="h-4 w-4" />
                        </div>
                        <span class="text-sm font-semibold {{ $renk['baslik'] }}">{{ $tanim['etiket'] }}</span>
                        <span class="ml-auto flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-white px-1.5 text-xs font-bold text-gray-700 shadow-sm ring-1 ring-gray-200">
                            {{ $sayi }}
                        </span>
                    </div>

                    {{-- Sonuç listesi --}}
                    <div class="divide-y divide-gray-50">
                        @foreach($liste as $sonuc)
                        <a href="{{ $sonuc['link'] }}"
                           class="flex items-start gap-3 px-4 py-3 transition {{ $renk['hover'] }}">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">
                                    {{ $sonuc['baslik'] }}
                                </p>
                                @if(! empty($sonuc['ozet']))
                                <p class="mt-0.5 truncate text-xs text-gray-500">
                                    {{ \Illuminate\Support\Str::limit(strip_tags((string) $sonuc['ozet']), 90) }}
                                </p>
                                @endif
                            </div>
                            <x-heroicon-m-arrow-top-right-on-square class="mt-0.5 h-4 w-4 shrink-0 text-gray-300" />
                        </a>
                        @endforeach
                    </div>

                </div>
                @endif
            @endforeach
        </div>

        @endif

    @else
    {{-- Başlangıç durumu --}}
    <div class="rounded-2xl border border-dashed border-gray-200 bg-white py-16 text-center">
        <x-heroicon-o-magnifying-glass class="mx-auto h-10 w-10 text-gray-300" />
        <p class="mt-3 text-base font-medium text-gray-500">Aramak istediğinizi yazın</p>
        <p class="mt-1 text-sm text-gray-400">Kişi, üye, haber, bağış, e-kayıt ve daha fazlası…</p>
        @php
            $ornekler = ['5326847101', 'Ahmet Yılmaz', 'mezun 2015', 'Anadolu Lisesi'];
        @endphp
        <div class="mt-5 flex flex-wrap justify-center gap-2">
            @foreach($ornekler as $ornek)
            <button
                wire:click="$set('arama', '{{ $ornek }}')"
                class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs text-gray-500 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700"
                type="button"
            >
                {{ $ornek }}
            </button>
            @endforeach
        </div>
    </div>
    @endif

</div>
</x-filament-panels::page>
