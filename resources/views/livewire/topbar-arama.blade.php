<div
    class="fi-global-search-ctn"
    x-data="{ isOpen: false }"
    x-on:click.away="isOpen = false"
    x-on:keydown.escape.window="isOpen = false; $wire.set('arama', '')"
>
    <div x-on:focus-first-global-search-result.stop="$el.querySelector('.fi-global-search-result-link')?.focus()" class="fi-global-search">
        <div x-id="['input']" class="fi-global-search-field">
            <label x-bind:for="$id('input')" class="fi-sr-only">Global arama</label>

            <div class="fi-input-wrp">
                <div class="fi-input-wrp-prefix fi-input-wrp-prefix-has-content fi-inline">
                    <x-heroicon-s-magnifying-glass
                        wire:loading.remove.delay.default
                        wire:target="arama"
                        class="fi-icon fi-size-md"
                    />

                    <svg
                        class="fi-icon fi-loading-indicator fi-size-md"
                        wire:loading.delay.default
                        wire:target="arama"
                        fill="none"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            clip-rule="evenodd"
                            d="M12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19ZM12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                            fill="currentColor"
                            fill-rule="evenodd"
                            opacity="0.2"
                        ></path>
                        <path d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z" fill="currentColor"></path>
                    </svg>
                </div>

                <div class="fi-input-wrp-content-ctn">
                    <input
                        x-ref="aramaInput"
                        x-bind:id="$id('input')"
                        x-on:focus="isOpen = true"
                        x-on:input="isOpen = true"
                        x-on:keydown.down.prevent.stop="$dispatch('focus-first-global-search-result')"
                        x-on:keydown.ctrl.k.window.prevent="$refs.aramaInput.focus(); isOpen = true"
                        x-on:keydown.slash.window="if (document.activeElement.tagName === 'BODY') { $refs.aramaInput.focus(); isOpen = true }"
                        autocomplete="off"
                        class="fi-input fi-input-has-inline-prefix"
                        maxlength="1000"
                        placeholder="Haber, etkinlik, kisi, kurum, rehber, uye, mezun, kayit ara..."
                        type="search"
                        wire:model.live.debounce.300ms="arama"
                    />
                </div>

                @if($arama)
                    <div class="fi-input-wrp-suffix fi-input-wrp-suffix-has-content fi-inline">
                        <button
                            type="button"
                            wire:click="$set('arama', '')"
                            x-on:click="isOpen = false"
                            class="fi-icon-btn fi-color-gray fi-size-sm"
                            aria-label="Temizle"
                        >
                            <x-heroicon-s-x-circle class="fi-icon fi-size-md" />
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div
            x-show="isOpen"
            x-transition:enter-start="fi-transition-enter-start"
            x-transition:leave-end="fi-transition-leave-end"
            class="fi-global-search-results-ctn"
            style="display: none;"
        >
            @php
                $gruplar = [
                    'haberler' => 'haberler',
                    'etkinlikler' => 'etkinlikler',
                    'kurumsal_sayfalar' => 'kurumsal sayfalar',
                    'kisiler' => 'kisiler',
                    'kurumlar' => 'kurumlar',
                    'rehber' => 'rehber',
                    'uyeler' => 'uyeler',
                    'mezunlar' => 'mezunlar',
                    'kayitlar' => 'e-kayit',
                ];
            @endphp

            @if(mb_strlen(trim($arama), 'UTF-8') < 2)
                <div class="p-4 text-sm text-gray-500">En az 2 karakter girin.</div>
            @elseif($toplamSonuc === 0)
                <div class="p-4 text-sm text-gray-500">Sonuc bulunamadi.</div>
            @else
                <ul class="fi-global-search-results">
                    @foreach($gruplar as $anahtar => $etiket)
                        @if(!empty($sonuclar[$anahtar]))
                            <li class="fi-global-search-result-group">
                                <h3 class="fi-global-search-result-group-header">{{ $etiket }}</h3>

                                <ul class="fi-global-search-result-group-results">
                                    @foreach($sonuclar[$anahtar] as $sonuc)
                                        <li class="fi-global-search-result">
                                            <a
                                                href="{{ $sonuc['link'] }}"
                                                class="fi-global-search-result-link"
                                                x-on:click="isOpen = false"
                                            >
                                                <h4 class="fi-global-search-result-heading">{{ $sonuc['baslik'] }}</h4>

                                                @if(!empty($sonuc['ozet']))
                                                    <dl class="fi-global-search-result-details">
                                                        <div class="fi-global-search-result-detail">
                                                            <dd class="fi-global-search-result-detail-value">
                                                                {{ \Illuminate\Support\Str::limit(strip_tags((string) $sonuc['ozet']), 90) }}
                                                            </dd>
                                                        </div>
                                                    </dl>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
