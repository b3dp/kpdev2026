<div
    x-data="{ acik: false }"
    x-on:keydown.escape.window="acik = false; $wire.arama = ''; $wire.set('arama', '')"
>
    {{-- Tetikleyici buton --}}
    <button
        type="button"
        x-on:click="acik = true; $nextTick(() => $refs.aramaInput.focus())"
        class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition hover:bg-primary-50 hover:text-primary-700"
        title="Global Arama (Ctrl+K)"
    >
        <x-heroicon-o-magnifying-glass class="h-5 w-5" />
    </button>

    {{-- Klavye kısayolu: Ctrl+K veya / --}}
    <div
        x-on:keydown.ctrl.k.window.prevent="acik = true; $nextTick(() => $refs.aramaInput.focus())"
        x-on:keydown.slash.window="if (document.activeElement.tagName === 'BODY') { acik = true; $nextTick(() => $refs.aramaInput.focus()) }"
    ></div>

    {{-- Modal overlay --}}
    <div
        x-show="acik"
        x-cloak
        class="fixed inset-0 z-50 flex items-start justify-center pt-16 px-4"
        style="display: none;"
    >
        {{-- Arka plan --}}
        <div
            class="fixed inset-0 bg-black/40 backdrop-blur-sm"
            x-on:click="acik = false; $wire.set('arama', '')"
        ></div>

        {{-- Modal kutu --}}
        <div
            class="relative z-10 w-full max-w-2xl rounded-2xl bg-white shadow-2xl"
            x-on:click.stop
        >
            {{-- Arama inputu --}}
            <div class="flex items-center border-b border-gray-200 px-4">
                <x-heroicon-o-magnifying-glass class="h-5 w-5 flex-shrink-0 text-gray-400" />
                <input
                    x-ref="aramaInput"
                    type="text"
                    wire:model.live.debounce.300ms="arama"
                    placeholder="Haber, etkinlik, kişi, kurum ara..."
                    class="w-full border-0 bg-transparent px-3 py-4 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0"
                    autocomplete="off"
                />
                @if($arama)
                    <button
                        type="button"
                        wire:click="$set('arama', '')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                @endif
                <kbd class="ml-2 hidden rounded border border-gray-200 px-1.5 py-0.5 text-xs text-gray-400 sm:inline">ESC</kbd>
            </div>

            {{-- Sonuçlar --}}
            <div class="max-h-[60vh] overflow-y-auto p-3">
                @if(mb_strlen(trim($arama), 'UTF-8') < 2)
                    <p class="px-3 py-8 text-center text-sm text-gray-400">En az 2 karakter girin...</p>
                @elseif($toplamSonuc === 0)
                    <p class="px-3 py-8 text-center text-sm text-gray-400">Sonuç bulunamadı.</p>
                @else
                    @php
                        $gruplar = [
                            'haberler'        => ['baslik' => 'Haberler',         'ikon' => 'heroicon-o-newspaper'],
                            'etkinlikler'      => ['baslik' => 'Etkinlikler',      'ikon' => 'heroicon-o-calendar-days'],
                            'kurumsal_sayfalar'=> ['baslik' => 'Kurumsal Sayfalar','ikon' => 'heroicon-o-document-text'],
                            'kisiler'          => ['baslik' => 'Kişiler',          'ikon' => 'heroicon-o-user'],
                            'kurumlar'         => ['baslik' => 'Kurumlar',         'ikon' => 'heroicon-o-building-office'],
                        ];
                    @endphp

                    @foreach($gruplar as $anahtar => $meta)
                        @if(!empty($sonuclar[$anahtar]))
                            <div class="mb-3">
                                <p class="mb-1 px-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    {{ $meta['baslik'] }} ({{ count($sonuclar[$anahtar]) }})
                                </p>
                                @foreach($sonuclar[$anahtar] as $sonuc)
                                    <a
                                        href="{{ $sonuc['link'] }}"
                                        class="flex items-start gap-3 rounded-lg px-3 py-2.5 hover:bg-primary-50 group"
                                        x-on:click="acik = false"
                                    >
                                        <div class="mt-0.5 flex-shrink-0 text-gray-400 group-hover:text-primary-600">
                                            <x-dynamic-component :component="$meta['ikon']" class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-gray-900 group-hover:text-primary-700">
                                                {{ $sonuc['baslik'] }}
                                            </p>
                                            @if(!empty($sonuc['ozet']))
                                                <p class="mt-0.5 truncate text-xs text-gray-500">
                                                    {{ \Illuminate\Support\Str::limit(strip_tags((string) $sonuc['ozet']), 80) }}
                                                </p>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    <p class="mt-2 border-t border-gray-100 pt-2 text-center text-xs text-gray-400">
                        Toplam {{ $toplamSonuc }} sonuç
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
