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
        x-transition:enter="transition ease-out duration-180"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-120"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[1000] flex items-start justify-center px-4 pt-14 sm:px-6 sm:pt-20"
        style="display: none;"
    >
        {{-- Arka plan --}}
        <div
            class="fixed inset-0 bg-slate-700/30 backdrop-blur-sm"
            x-on:click="acik = false; $wire.set('arama', '')"
        ></div>

        {{-- Modal kutu --}}
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.985]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-130"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.99]"
            class="relative z-10 mx-auto w-full max-w-5xl overflow-hidden rounded-[30px] border border-white/90 bg-white/95 shadow-[0_30px_90px_-24px_rgba(15,23,42,0.55)] ring-1 ring-sky-100/80"
            x-on:click.stop
        >
            <div class="pointer-events-none absolute -left-20 -top-20 h-52 w-52 rounded-full bg-sky-100/60 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-16 top-10 h-44 w-44 rounded-full bg-cyan-100/60 blur-3xl"></div>

            <div class="relative border-b border-sky-100/80 px-5 pt-4 sm:px-8">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700/80">Akıllı Arama</p>
            </div>

            {{-- Arama inputu --}}
            <div class="relative z-10 flex items-center border-b border-sky-100/80 bg-gradient-to-r from-white via-sky-50/90 to-cyan-50/80 px-5 sm:px-8">
                <x-heroicon-o-magnifying-glass class="h-5 w-5 flex-shrink-0 text-sky-500" />
                <input
                    x-ref="aramaInput"
                    type="text"
                    wire:model.live.debounce.300ms="arama"
                    placeholder="Haber, etkinlik, kişi, kurum, rehber, üye, mezun, kayıt ara..."
                    class="w-full border-0 bg-transparent px-3 py-4 text-base font-medium text-slate-900 placeholder:text-slate-500 focus:outline-none focus:ring-0"
                    autocomplete="off"
                />
                @if($arama)
                    <button
                        type="button"
                        wire:click="$set('arama', '')"
                        class="text-slate-400 transition hover:text-slate-700"
                    >
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                @endif
                <kbd class="ml-2 hidden rounded-md border border-sky-200 bg-white px-2 py-0.5 text-xs font-medium text-slate-500 shadow-sm sm:inline">ESC</kbd>
            </div>

            {{-- Filtreler --}}
            <div class="relative z-10 flex flex-wrap gap-2 border-b border-sky-100/80 bg-white/80 px-5 py-3 sm:px-8">
                @php
                    $filtreler = [
                        'tum' => 'Tümü',
                        'haberler' => 'Haber',
                        'etkinlikler' => 'Etkinlik',
                        'kisiler' => 'Kişi',
                        'kurumlar' => 'Kurum',
                        'rehber' => 'Rehber',
                        'uyeler' => 'Üye',
                        'mezunlar' => 'Mezun',
                        'kayitlar' => 'E-Kayıt',
                    ];
                @endphp

                @foreach($filtreler as $anahtar => $etiket)
                    <button
                        type="button"
                        wire:click="setFiltre('{{ $anahtar }}')"
                        class="rounded-full border px-3.5 py-1.5 text-xs font-semibold tracking-[0.01em] transition {{ $aktifFiltre === $anahtar ? 'border-sky-600 bg-gradient-to-r from-sky-600 to-cyan-600 text-white shadow-[0_8px_20px_-10px_rgba(14,116,144,0.8)]' : 'border-slate-200 bg-white text-slate-700 hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700' }}"
                    >
                        {{ $etiket }}
                    </button>
                @endforeach
            </div>

            {{-- Sonuçlar --}}
            <div class="relative z-10 max-h-[64vh] overflow-y-auto bg-gradient-to-b from-white via-slate-50/60 to-sky-50/70 p-3 sm:p-4 sm:px-6">
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
                            'rehber'           => ['baslik' => 'Rehber',           'ikon' => 'heroicon-o-user-group'],
                            'uyeler'           => ['baslik' => 'Üyeler',           'ikon' => 'heroicon-o-identification'],
                            'mezunlar'         => ['baslik' => 'Mezunlar',         'ikon' => 'heroicon-o-academic-cap'],
                            'kayitlar'         => ['baslik' => 'E-Kayıt',          'ikon' => 'heroicon-o-clipboard-document-list'],
                        ];

                        $gorunenToplam = 0;
                        foreach ($gruplar as $anahtar => $meta) {
                            if ($aktifFiltre !== 'tum' && $aktifFiltre !== $anahtar) {
                                continue;
                            }
                            $gorunenToplam += count($sonuclar[$anahtar] ?? []);
                        }
                    @endphp

                    @foreach($gruplar as $anahtar => $meta)
                        @if(($aktifFiltre === 'tum' || $aktifFiltre === $anahtar) && !empty($sonuclar[$anahtar]))
                            <div class="mb-4">
                                <p class="mb-1.5 px-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-400">
                                    {{ $meta['baslik'] }} ({{ count($sonuclar[$anahtar]) }})
                                </p>
                                @foreach($sonuclar[$anahtar] as $sonuc)
                                    <a
                                        href="{{ $sonuc['link'] }}"
                                        class="group flex items-start gap-3 rounded-2xl border border-slate-200/70 bg-white/90 px-3.5 py-3 shadow-[0_8px_24px_-18px_rgba(15,23,42,0.45)] transition hover:-translate-y-[1px] hover:border-sky-200 hover:bg-sky-50/80"
                                        x-on:click="acik = false"
                                    >
                                        <div class="mt-0.5 flex-shrink-0 text-slate-400 transition group-hover:text-sky-600">
                                            <x-dynamic-component :component="$meta['ikon']" class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900 group-hover:text-sky-700">
                                                {{ $sonuc['baslik'] }}
                                            </p>
                                            @if(!empty($sonuc['ozet']))
                                                <p class="mt-0.5 truncate text-xs text-slate-500">
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
                        Gösterilen {{ $gorunenToplam }} / Toplam {{ $toplamSonuc }} sonuç
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
