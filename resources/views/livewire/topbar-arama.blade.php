<div
    x-data="{ isOpen: false }"
    x-on:click.away="isOpen = false"
    x-on:keydown.escape.window="isOpen = false; $wire.set('arama', '')"
    class="relative hidden lg:block"
>
    <div class="relative w-[34rem] max-w-[58vw]">
        <label for="ust-arama" class="sr-only">Global arama</label>

        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400" wire:loading.remove.delay.default wire:target="arama">
                <x-heroicon-o-magnifying-glass class="h-5 w-5" />
            </div>

            <div class="pointer-events-none absolute inset-y-0 left-3 hidden items-center text-gray-400" wire:loading.delay.default wire:target="arama">
                <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path clip-rule="evenodd" d="M12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19ZM12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="currentColor" fill-rule="evenodd" opacity="0.2"></path>
                    <path d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z" fill="currentColor"></path>
                </svg>
            </div>

            <input
                id="ust-arama"
                x-ref="aramaInput"
                x-on:focus="isOpen = true"
                x-on:input="isOpen = true"
                x-on:keydown.ctrl.k.window.prevent="$refs.aramaInput.focus(); isOpen = true"
                x-on:keydown.slash.window="if (document.activeElement.tagName === 'BODY') { $refs.aramaInput.focus(); isOpen = true }"
                autocomplete="off"
                class="h-11 w-full rounded-2xl border border-gray-300 bg-white pl-10 pr-10 text-base text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200"
                maxlength="1000"
                placeholder="Haber, etkinlik, kisi, kurum, rehber, uye, mezun, kayit ara..."
                type="search"
                wire:model.live.debounce.300ms="arama"
            />

            @if($arama)
                <button
                    type="button"
                    wire:click="$set('arama', '')"
                    class="absolute inset-y-0 right-2 my-auto h-7 w-7 rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    aria-label="Temizle"
                >
                    <x-heroicon-s-x-circle class="h-5 w-5" />
                </button>
            @endif
        </div>

        <div
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="absolute left-0 right-0 z-[999] mt-2 max-h-[70vh] overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-xl"
            style="display: none;"
        >
            @php
                $gruplar = [
                    'haberler' => 'Haberler',
                    'etkinlikler' => 'Etkinlikler',
                    'kurumsal_sayfalar' => 'Kurumsal Sayfalar',
                    'kisiler' => 'Kisiler',
                    'kurumlar' => 'Kurumlar',
                    'rehber' => 'Rehber',
                    'uyeler' => 'Uyeler',
                    'mezunlar' => 'Mezunlar',
                    'kayitlar' => 'E-Kayit',
                ];
            @endphp

            @if(mb_strlen(trim($arama), 'UTF-8') < 2)
                <div class="px-4 py-5 text-sm text-gray-500">En az 2 karakter girin.</div>
            @elseif($toplamSonuc === 0)
                <div class="px-4 py-5 text-sm text-gray-500">Sonuc bulunamadi.</div>
            @else
                @foreach($gruplar as $anahtar => $etiket)
                    @if(!empty($sonuclar[$anahtar]))
                        <div class="border-b border-gray-100 last:border-b-0">
                            <h3 class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $etiket }}</h3>

                            @foreach($sonuclar[$anahtar] as $sonuc)
                                <a
                                    href="{{ $sonuc['link'] }}"
                                    class="block px-4 py-3 transition hover:bg-gray-50"
                                    x-on:click="isOpen = false"
                                >
                                    <h4 class="text-base font-medium text-gray-900">{{ $sonuc['baslik'] }}</h4>

                                    @if(!empty($sonuc['ozet']))
                                        <p class="mt-1 text-sm text-gray-500">{{ \Illuminate\Support\Str::limit(strip_tags((string) $sonuc['ozet']), 90) }}</p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</div>
