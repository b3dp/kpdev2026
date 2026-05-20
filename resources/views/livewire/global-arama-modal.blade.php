@php
    $grupTanim = [
        'kisiler'           => ['etiket' => 'Kişiler',           'renk' => '#2563eb', 'bg' => '#eff6ff'],
        'uyeler'            => ['etiket' => 'Üyeler',            'renk' => '#4f46e5', 'bg' => '#eef2ff'],
        'haberler'          => ['etiket' => 'Haberler',          'renk' => '#0284c7', 'bg' => '#f0f9ff'],
        'ekayit_kayitlar'   => ['etiket' => 'E-Kayıt',           'renk' => '#059669', 'bg' => '#ecfdf5'],
        'bagislar'          => ['etiket' => 'Bağışlar',          'renk' => '#e11d48', 'bg' => '#fff1f2'],
        'mezunlar'          => ['etiket' => 'Mezunlar',          'renk' => '#d97706', 'bg' => '#fffbeb'],
        'etkinlikler'       => ['etiket' => 'Etkinlikler',       'renk' => '#7c3aed', 'bg' => '#f5f3ff'],
        'kurumlar'          => ['etiket' => 'Kurumlar',          'renk' => '#0f766e', 'bg' => '#f0fdfa'],
        'kurumsal_sayfalar' => ['etiket' => 'Kurumsal Sayfalar', 'renk' => '#475569', 'bg' => '#f8fafc'],
    ];

    $aramaVar = mb_strlen(trim($arama), 'UTF-8') >= 2;
@endphp

<div>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
[x-cloak] { display: none !important; }
</style>
<div
    x-data="{ acik: @entangle('acik') }"
    x-on:keydown.window="
        if (event.key === 'k' && (event.metaKey || event.ctrlKey)) {
            event.preventDefault();
            acik = true;
            $nextTick(() => $refs.aramaInput?.focus());
        }
        if (event.key === 'Escape' && acik) {
            acik = false;
        }
    "
>
    {{-- Overlay --}}
    <div
        x-show="acik"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="acik = false; $wire.kapat()"
        style="position:fixed;inset:0;z-index:9998;background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);"
        x-cloak
    ></div>

    {{-- Modal --}}
    <div
        x-show="acik"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-4"
        x-on:click.stop
        style="position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:9999;width:min(680px, calc(100vw - 2rem));max-height:calc(100vh - 120px);display:flex;flex-direction:column;border-radius:16px;background:#fff;box-shadow:0 25px 60px rgba(0,0,0,0.25),0 0 0 1px rgba(0,0,0,0.06);overflow:hidden;"
        x-cloak
    >
        {{-- Arama girişi --}}
        <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #f1f5f9;">
            <div wire:loading.remove wire:target="arama" style="flex-shrink:0;color:#94a3b8;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width:20px;height:20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </div>
            <div wire:loading wire:target="arama" style="flex-shrink:0;color:#3b82f6;">
                <svg style="width:20px;height:20px;animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="arama"
                placeholder="Kişi, üye, haber, e-kayıt, bağış, mezun ara…"
                x-ref="aramaInput"
                x-on:keydown.escape.stop="acik = false; $wire.kapat()"
                style="flex:1;border:none;outline:none;font-size:16px;color:#0f172a;background:transparent;font-weight:500;"
                autocomplete="off"
                spellcheck="false"
                x-init="$nextTick(() => { if (acik) $el.focus() })"
                x-effect="if (acik) $nextTick(() => $el.focus())"
            />
            @if(strlen(trim($arama)) > 0)
            <button
                wire:click="$set('arama', '')"
                type="button"
                title="Temizle"
                style="flex-shrink:0;padding:4px;border-radius:6px;border:none;background:#f1f5f9;cursor:pointer;color:#64748b;display:flex;align-items:center;"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
            @endif
            <button
                type="button"
                x-on:click="acik = false; $wire.kapat()"
                title="Kapat (ESC)"
                style="flex-shrink:0;padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;background:#f8fafc;cursor:pointer;font-size:11px;color:#94a3b8;font-family:monospace;line-height:1;"
            >ESC</button>
        </div>

        {{-- Sonuçlar / boş durum --}}
        <div style="overflow-y:auto;flex:1;">

            @if(!$aramaVar)
            {{-- Başlangıç --}}
            <div style="padding:40px 24px;text-align:center;">
                <p style="font-size:14px;color:#94a3b8;margin:0;">Aramak istediğinizi yazın</p>
                <p style="font-size:12px;color:#cbd5e1;margin:8px 0 16px;">Kişi, üye, bağış, e-kayıt ve daha fazlası</p>
                <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:8px;">
                    @foreach(['5326847101', 'Ahmet Yılmaz', 'mezun 2015', 'Anadolu'] as $ornek)
                    <button
                        wire:click="$set('arama', '{{ $ornek }}')"
                        type="button"
                        style="padding:4px 12px;border-radius:999px;border:1px solid #e2e8f0;background:#f8fafc;font-size:12px;color:#64748b;cursor:pointer;"
                    >{{ $ornek }}</button>
                    @endforeach
                </div>
                <p style="font-size:11px;color:#cbd5e1;margin-top:20px;">
                    <kbd style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:2px 5px;font-family:monospace;">Ctrl+K</kbd>
                    ile her yerden açabilirsiniz
                </p>
            </div>

            @elseif($toplamSonuc === 0)
            {{-- Boş sonuç --}}
            <div style="padding:40px 24px;text-align:center;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:40px;height:40px;color:#cbd5e1;margin:0 auto;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <p style="font-size:14px;color:#64748b;margin:12px 0 4px;">
                    "<strong>{{ $arama }}</strong>" için sonuç bulunamadı
                </p>
                <p style="font-size:12px;color:#94a3b8;margin:0;">Farklı anahtar kelime deneyin</p>
            </div>

            @else
            {{-- Sonuç grupları --}}
            @foreach($grupTanim as $anahtar => $tanim)
                @php $liste = $sonuclar[$anahtar] ?? []; @endphp
                @if(count($liste) > 0)
                <div>
                    {{-- Grup başlığı --}}
                    <div style="display:flex;align-items:center;gap:8px;padding:10px 18px 6px;background:{{ $tanim['bg'] }};border-bottom:1px solid rgba(0,0,0,0.05);">
                        <span style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:{{ $tanim['renk'] }};">{{ $tanim['etiket'] }}</span>
                        <span style="font-size:11px;font-weight:600;color:{{ $tanim['renk'] }};background:rgba(255,255,255,0.7);border-radius:999px;padding:0 7px;line-height:1.8;border:1px solid rgba(0,0,0,0.08);">{{ count($liste) }}</span>
                    </div>
                    {{-- Satırlar --}}
                    @foreach($liste as $sonuc)
                    <a
                        href="{{ $sonuc['link'] }}"
                        style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 18px;border-bottom:1px solid #f8fafc;text-decoration:none;transition:background .12s;"
                        onmouseover="this.style.background='#f8fafc'"
                        onmouseout="this.style.background='transparent'"
                    >
                        <div style="min-width:0;flex:1;">
                            <p style="margin:0;font-size:14px;font-weight:500;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $sonuc['baslik'] }}
                            </p>
                            @if(!empty($sonuc['ozet']))
                            <p style="margin:2px 0 0;font-size:12px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ \Illuminate\Support\Str::limit(strip_tags((string) $sonuc['ozet']), 80) }}
                            </p>
                            @endif
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:14px;height:14px;flex-shrink:0;color:#cbd5e1;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                    </a>
                    @endforeach
                </div>
                @endif
            @endforeach
            @endif

        </div>

        {{-- Footer --}}
        @if($aramaVar && $toplamSonuc > 0)
        <div style="padding:8px 18px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:#fafafa;">
            <span style="font-size:11px;color:#94a3b8;">
                <strong style="color:#64748b;">{{ $toplamSonuc }}</strong> sonuç
            </span>
            <span style="font-size:11px;color:#cbd5e1;">Her kategoride en fazla 5 sonuç gösteriliyor</span>
        </div>
        @endif

    </div>
</div>
