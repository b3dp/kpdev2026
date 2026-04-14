@extends('layouts.app')

@section('title', 'Profilim')
@section('meta_description', 'Kestanepazarı mezun portalında profil bilgilerinizi, bağış geçmişinizi ve güvenlik ayarlarınızı yönetin.')

@section('schema')
@php
    $schemaData = [
        '@context' => 'https://schema.org',
        '@type' => 'ProfilePage',
        'name' => 'Profilim',
        'url' => route('uye.profil.index'),
        'description' => 'Üyenin profil, bağış ve güvenlik ayarlarını yönettiği mezun portalı sayfası.',
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endsection

@section('content')
@php
    $adParcalari = collect(explode(' ', trim((string) ($uye->ad_soyad ?? 'Üye'))))->filter()->values();
    $ilkHarfler = $adParcalari->take(2)->map(fn ($parca) => mb_strtoupper(mb_substr($parca, 0, 1, 'UTF-8'), 'UTF-8'))->implode('');
    $ilkHarfler = $ilkHarfler !== '' ? $ilkHarfler : 'KP';

    $sonEkayit = $ekayitKayitlar->first();
    $profilTipi = $mezunProfil
        ? 'Mezun'
        : (($ekayitOzeti['adet'] ?? 0) > 0
            ? 'Veli'
            : (($bagisOzeti['adet'] ?? 0) > 0 ? 'Bağışçı' : 'Portal Üyesi'));

    $profilOzeti = collect([
        $profilTipi,
        $mezunProfil?->mezuniyet_yili ?: (($ekayitOzeti['adet'] ?? 0) > 0 ? (($ekayitOzeti['adet'] ?? 0).' başvuru') : null),
        $mezunProfil?->meslek ?: (($ekayitOzeti['adet'] ?? 0) > 0 ? 'E-Kayıt Takibi' : null),
        $mezunProfil?->ikamet_il,
    ])->filter()->implode(' · ');

    if ($mezunProfil) {
        $durumBilgisi = match ($mezunProfil?->durum) {
            'aktif' => ['etiket' => 'Aktif Mezun', 'sinif' => 'uye-profil__pill uye-profil__pill--green'],
            'reddedildi' => ['etiket' => 'Tekrar Düzenleme Gerekli', 'sinif' => 'uye-profil__pill uye-profil__pill--red'],
            default => ['etiket' => 'Onay Bekliyor', 'sinif' => 'uye-profil__pill uye-profil__pill--gold'],
        };
    } elseif ($sonEkayit) {
        $durumBilgisi = match ($sonEkayit->durum?->value) {
            'onaylandi' => ['etiket' => 'Veli Başvurusu Onaylandı', 'sinif' => 'uye-profil__pill uye-profil__pill--green'],
            'reddedildi' => ['etiket' => 'Veli Başvurusu Reddedildi', 'sinif' => 'uye-profil__pill uye-profil__pill--red'],
            'yedek' => ['etiket' => 'Yedek Liste', 'sinif' => 'uye-profil__pill uye-profil__pill--gold'],
            default => ['etiket' => 'Başvuru İncelemede', 'sinif' => 'uye-profil__pill uye-profil__pill--gold'],
        };
    } elseif (($bagisOzeti['adet'] ?? 0) > 0) {
        $durumBilgisi = ['etiket' => 'Aktif Destekçi', 'sinif' => 'uye-profil__pill uye-profil__pill--green'];
    } else {
        $durumBilgisi = ['etiket' => 'Profilinizi Tamamlayın', 'sinif' => 'uye-profil__pill uye-profil__pill--gold'];
    }
@endphp

<section class="uye-profil py-10 md:py-14" data-uye-profil>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="uye-profil__hero mb-8 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative z-[1] flex items-center gap-4">
                <div class="uye-profil__avatar h-20 w-20 text-2xl">{{ $ilkHarfler }}</div>
                <div>
                    <p class="text-xs font-medium text-[#ebdfb5]/60">Hoş geldiniz</p>
                    <h1 class="mt-1 font-['Libre_Baskerville'] text-2xl font-bold text-[#EBDFB5]">{{ $uye->ad_soyad ?: 'Kıymetli Üyemiz' }}</h1>
                    <p class="mt-2 text-sm text-[#ebdfb5]/75">{{ $profilOzeti !== '' ? $profilOzeti : 'Bilgilerinizi güncelleyerek başvurularınızı ve destek geçmişinizi tek ekrandan yönetin.' }}</p>
                </div>
            </div>

            <div class="relative z-[1] flex flex-wrap items-center gap-3 lg:justify-end">
                <div class="text-center">
                    <p class="font-['Libre_Baskerville'] text-xl font-bold text-[#EBDFB5]">{{ $bagisOzeti['adet'] }}</p>
                    <p class="mt-1 text-xs text-[#ebdfb5]/60">Bağış</p>
                </div>
                <div class="text-center">
                    <p class="font-['Libre_Baskerville'] text-xl font-bold text-[#EBDFB5]">₺{{ number_format((float) $bagisOzeti['toplam'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-[#ebdfb5]/60">Toplam</p>
                </div>
                <div class="text-center">
                    <p class="font-['Libre_Baskerville'] text-xl font-bold text-[#EBDFB5]">{{ $yaklasanEtkinlikler->count() + $gecmisEtkinlikler->count() }}</p>
                    <p class="mt-1 text-xs text-[#ebdfb5]/60">Etkinlik</p>
                </div>
                <a href="{{ route('bagis.index') }}" class="rounded-xl bg-[#E95925] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#c94620]">Bağış Yap</a>
                <form action="{{ route('uye.cikis') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-xl border border-white/20 px-4 py-2 text-sm font-semibold text-[#EBDFB5] transition hover:border-[#EBDFB5]/60 hover:bg-white/5">Çıkış Yap</button>
                </form>
            </div>
        </div>

        <div class="grid gap-8 lg:grid-cols-3">
            <aside>
                <div class="uye-profil__sidebar rounded-[1.25rem]">
                    <div class="uye-profil__sidebar-cover"></div>
                    <div class="px-5 pb-5">
                        <div class="-mt-10 inline-flex rounded-full border-4 border-white bg-white">
                            <div class="uye-profil__avatar h-20 w-20 text-2xl">{{ $ilkHarfler }}</div>
                        </div>

                        <div class="mt-4">
                            <h2 class="font-['Libre_Baskerville'] text-xl font-bold text-[#162E4B]">{{ $uye->ad_soyad ?: 'Portal Üyesi' }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $mezunProfil?->meslek ?: (($ekayitOzeti['adet'] ?? 0) > 0 ? 'Veli başvuru takibi aktif' : (($bagisOzeti['adet'] ?? 0) > 0 ? 'Kestanepazarı destekçisi' : 'Kestanepazarı topluluk üyesi')) }}</p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="{{ $durumBilgisi['sinif'] }}">{{ $durumBilgisi['etiket'] }}</span>
                            @if ($uye->durum)
                                <span class="uye-profil__pill uye-profil__pill--gold">Üyelik: {{ $uye->durum->label() }}</span>
                            @endif
                        </div>

                        <div class="mt-5 space-y-3 border-t border-slate-200 pt-4 text-sm text-slate-600">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-[#B27829]">✦</span>
                                <span>
                                    @if ($mezunProfil?->mezuniyet_yili)
                                        {{ 'Mezuniyet yılı: '.$mezunProfil->mezuniyet_yili }}
                                    @elseif (($ekayitOzeti['adet'] ?? 0) > 0)
                                        {{ 'E-Kayıt başvurusu: '.($ekayitOzeti['adet'] ?? 0).' adet' }}
                                    @elseif (($bagisOzeti['adet'] ?? 0) > 0)
                                        {{ 'Toplam bağış: '.($bagisOzeti['adet'] ?? 0).' adet' }}
                                    @else
                                        Profil bilgilerinizi tamamlayın
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-[#B27829]">✉</span>
                                <span>{{ $uye->eposta ?: 'E-posta eklenmemiş' }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-[#B27829]">☎</span>
                                <span>{{ $uye->telefon ?: 'Telefon eklenmemiş' }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-[#B27829]">⌂</span>
                                <span>{{ collect([$mezunProfil?->ikamet_ilce, $mezunProfil?->ikamet_il])->filter()->implode(' / ') ?: ($sonEkayit?->sinif?->ad ? 'Başvuru sınıfı: '.$sonEkayit->sinif->ad : 'İkamet bilgisi girilmemiş') }}</span>
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl bg-[#B27829]/10 px-4 py-3 text-sm text-[#162E4B]">
                            <p class="font-semibold text-[#B27829]">Rozetlerim</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse ($uye->rozetler as $rozet)
                                    <span class="uye-profil__pill uye-profil__pill--gold">{{ $rozet->tip?->label() ?? ucfirst((string) $rozet->tip) }}</span>
                                @empty
                                    <span class="text-xs text-slate-500">Henüz rozet görünmüyor.</span>
                                @endforelse
                            </div>
                        </div>

                        @if (filled($mezunProfil?->red_notu))
                            <div class="uye-profil__notice mt-4 text-sm">
                                <p class="font-semibold text-[#B27829]">Not</p>
                                <p class="mt-1 text-slate-600">{{ $mezunProfil->red_notu }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </aside>

            <div class="lg:col-span-2">
                <div class="uye-profil__panel overflow-hidden rounded-[1.25rem]">
                    <div class="flex flex-wrap gap-1 overflow-x-auto border-b border-slate-200 px-2 pt-2">
                        <button type="button" class="uye-profil__tab is-active rounded-t-xl px-4 py-3 text-sm font-semibold" data-profil-tab="bilgiler" aria-selected="true">Bilgilerimi Düzenle</button>
                        <button type="button" class="uye-profil__tab rounded-t-xl px-4 py-3 text-sm font-semibold" data-profil-tab="bagis" aria-selected="false">Bağış Geçmişim</button>
                        <button type="button" class="uye-profil__tab rounded-t-xl px-4 py-3 text-sm font-semibold" data-profil-tab="ekayit" aria-selected="false">E-Kayıt Takibi</button>
                        <button type="button" class="uye-profil__tab rounded-t-xl px-4 py-3 text-sm font-semibold" data-profil-tab="etkinlik" aria-selected="false">Etkinlikler</button>
                    </div>

                    <div class="p-5 md:p-7">
                        <div data-profil-panel="bilgiler">
                            <form action="{{ route('uye.profil.guncelle') }}" method="POST" data-ajax-form class="space-y-6">
                                @csrf

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="ad_soyad" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">Ad Soyad</label>
                                        <input type="text" id="ad_soyad" name="ad_soyad" value="{{ $uye->ad_soyad }}" class="uye-profil__input" autocomplete="name">
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="ad_soyad"></p>
                                    </div>
                                    <div>
                                        <label for="eposta" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">E-posta</label>
                                        <input type="email" id="eposta" name="eposta" value="{{ $uye->eposta }}" class="uye-profil__input" autocomplete="email">
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="eposta"></p>
                                    </div>
                                    <div>
                                        <label for="telefon" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">Telefon</label>
                                        <input type="text" id="telefon" value="{{ $uye->telefon }}" class="uye-profil__input" disabled>
                                        <p class="mt-1 text-xs text-slate-500">Telefon alanı güvenlik nedeniyle değiştirilemez.</p>
                                    </div>
                                    <div>
                                        <label for="mezuniyet_yili" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">Mezuniyet Yılı</label>
                                        <select id="mezuniyet_yili" name="mezuniyet_yili" class="uye-profil__select">
                                            <option value="">Seçiniz</option>
                                            @foreach ($mezuniyetYillari as $yil)
                                                <option value="{{ $yil }}" @selected((int) $mezunProfil?->mezuniyet_yili === (int) $yil)>{{ $yil }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="mezuniyet_yili"></p>
                                    </div>
                                    <div>
                                        <label for="ikamet_il" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">İkamet İli</label>
                                        <select id="ikamet_il" name="ikamet_il" class="uye-profil__select">
                                            <option value="">Seçiniz</option>
                                            @foreach ($iller as $il => $etiket)
                                                <option value="{{ $il }}" @selected($mezunProfil?->ikamet_il === $il)>{{ $etiket }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="ikamet_il"></p>
                                    </div>
                                    <div>
                                        <label for="ikamet_ilce" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">İkamet İlçesi</label>
                                        <input type="text" id="ikamet_ilce" name="ikamet_ilce" value="{{ $mezunProfil?->ikamet_ilce }}" class="uye-profil__input" placeholder="Örn. Konak">
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="ikamet_ilce"></p>
                                    </div>
                                    <div>
                                        <label for="gorev_il" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">Görev İli</label>
                                        <select id="gorev_il" name="gorev_il" class="uye-profil__select">
                                            <option value="">Seçiniz</option>
                                            @foreach ($iller as $il => $etiket)
                                                <option value="{{ $il }}" @selected($mezunProfil?->gorev_il === $il)>{{ $etiket }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="gorev_il"></p>
                                    </div>
                                    <div>
                                        <label for="gorev_ilce" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">Görev İlçesi</label>
                                        <input type="text" id="gorev_ilce" name="gorev_ilce" value="{{ $mezunProfil?->gorev_ilce }}" class="uye-profil__input" placeholder="Örn. Karşıyaka">
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="gorev_ilce"></p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="meslek" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">Meslek / Unvan</label>
                                        <input type="text" id="meslek" name="meslek" value="{{ $mezunProfil?->meslek }}" class="uye-profil__input" placeholder="Örn. Yazılım Mühendisi">
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="meslek"></p>
                                    </div>
                                    <div>
                                        <label for="linkedin" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">LinkedIn</label>
                                        <input type="text" id="linkedin" name="linkedin" value="{{ $mezunProfil?->linkedin }}" class="uye-profil__input" placeholder="linkedin.com/in/...">
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="linkedin"></p>
                                    </div>
                                    <div>
                                        <label for="instagram" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">Instagram</label>
                                        <input type="text" id="instagram" name="instagram" value="{{ $mezunProfil?->instagram }}" class="uye-profil__input" placeholder="@kullaniciadi">
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="instagram"></p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="twitter" class="mb-1.5 block text-sm font-semibold text-[#162E4B]">Twitter / X</label>
                                        <input type="text" id="twitter" name="twitter" value="{{ $mezunProfil?->twitter }}" class="uye-profil__input" placeholder="x.com/... veya @kullaniciadi">
                                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="twitter"></p>
                                    </div>
                                </div>

                                <div class="space-y-4 rounded-2xl border border-slate-200 bg-[#F7F5F0] p-4">
                                    <div class="rounded-2xl border border-[#B27829]/20 bg-[#B27829]/10 px-4 py-3 text-sm text-[#162E4B]">
                                        <p class="font-semibold text-[#B27829]">OTP ile giriş</p>
                                        <p class="mt-1 text-slate-600">Bu hesapta girişler e-posta veya telefonunuza gönderilen <strong>OTP ile yapılır</strong>. Ayrı bir şifre kullanmıyoruz.</p>
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold text-[#162E4B]">Bildirim Tercihleri</p>
                                        <div class="mt-3 space-y-3">
                                            <label class="flex items-center gap-3 text-sm text-slate-600">
                                                <input type="checkbox" name="eposta_abonelik" value="1" @checked($uye->eposta_abonelik) class="h-4 w-4 rounded border-slate-300 text-[#162E4B] focus:ring-[#B27829]">
                                                Etkinlik ve mezun ağı duyurularını e-posta ile almak istiyorum.
                                            </label>
                                            <label class="flex items-center gap-3 text-sm text-slate-600">
                                                <input type="checkbox" name="sms_abonelik" value="1" @checked($uye->sms_abonelik) class="h-4 w-4 rounded border-slate-300 text-[#162E4B] focus:ring-[#B27829]">
                                                Kısa hatırlatmaları SMS ile almak istiyorum.
                                            </label>
                                            <label class="flex items-center gap-3 text-sm text-slate-600">
                                                <input type="checkbox" name="hafiz" value="1" @checked($mezunProfil?->hafiz) class="h-4 w-4 rounded border-slate-300 text-[#162E4B] focus:ring-[#B27829]">
                                                Hafız mezun olarak görünmek istiyorum.
                                            </label>
                                        </div>
                                    </div>

                                    <div class="border-t border-slate-200 pt-4">
                                        <p class="text-sm font-semibold text-[#162E4B]">İletişim Doğrulama Durumu</p>
                                        <div class="mt-3 space-y-3">
                                            <div class="flex flex-col gap-2 rounded-2xl bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">E-posta</p>
                                                    <p class="text-sm font-medium text-[#162E4B]">{{ $uye->eposta ?: 'Tanımlı değil' }}</p>
                                                </div>
                                                <span class="{{ $uye->eposta_dogrulandi ? 'uye-profil__pill uye-profil__pill--green' : 'uye-profil__pill uye-profil__pill--gold' }}">{{ $uye->eposta_dogrulandi ? 'Doğrulandı' : 'Doğrulama Bekliyor' }}</span>
                                            </div>
                                            <div class="flex flex-col gap-2 rounded-2xl bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Telefon</p>
                                                    <p class="text-sm font-medium text-[#162E4B]">{{ $uye->telefon ?: 'Tanımlı değil' }}</p>
                                                </div>
                                                <span class="{{ $uye->telefon_dogrulandi ? 'uye-profil__pill uye-profil__pill--green' : 'uye-profil__pill uye-profil__pill--gold' }}">{{ $uye->telefon_dogrulandi ? 'Doğrulandı' : 'Doğrulama Bekliyor' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" data-label="Değişiklikleri Kaydet" class="rounded-xl bg-[#162E4B] px-5 py-3 text-sm font-bold text-[#EBDFB5] transition hover:bg-[#091420]">Değişiklikleri Kaydet</button>
                                </div>

                                <div data-success-box class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"></div>
                            </form>
                        </div>

                        <div data-profil-panel="bagis" class="hidden">
                            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-['Libre_Baskerville'] text-xl font-bold text-[#162E4B]">Bağış Geçmişim</h3>
                                    <p class="mt-1 text-sm text-slate-500">Gerçekleşen bağışlarınızı ve makbuz bağlantılarınızı buradan takip edebilirsiniz.</p>
                                </div>
                                <a href="{{ route('bagis.index') }}" class="rounded-xl bg-[#E95925] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#c94620]">Yeni Bağış</a>
                            </div>

                            <div class="mb-6 grid gap-3 md:grid-cols-3">
                                <div class="uye-profil__stat-card bg-[#F7F5F0]">
                                    <p class="font-['Libre_Baskerville'] text-2xl font-bold text-[#162E4B]">{{ $bagisOzeti['adet'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Toplam Bağış</p>
                                </div>
                                <div class="uye-profil__stat-card bg-[#F7F5F0]">
                                    <p class="font-['Libre_Baskerville'] text-2xl font-bold text-[#162E4B]">₺{{ number_format((float) $bagisOzeti['toplam'], 0, ',', '.') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Toplam Tutar</p>
                                </div>
                                <div class="uye-profil__stat-card border border-[#B27829]/20 bg-[#B27829]/10">
                                    <p class="font-['Libre_Baskerville'] text-2xl font-bold text-[#B27829]">{{ $bagisOzeti['son_bagis'] ? '₺'.number_format((float) $bagisOzeti['son_bagis'], 0, ',', '.') : '—' }}</p>
                                    <p class="mt-1 text-xs text-[#B27829]">Son Bağış</p>
                                </div>
                            </div>

                            @forelse ($bagislar as $bagis)
                                <div class="uye-profil__history-row">
                                    <div>
                                        <p class="text-sm font-semibold text-[#162E4B]">Bağış #{{ $bagis->bagis_no }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $bagis->odeme_tarihi?->format('d.m.Y H:i') ?: 'Tarih bekleniyor' }}
                                            · {{ $bagis->durum?->label() ?? 'İşleniyor' }}
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="font-['Libre_Baskerville'] text-lg font-bold text-[#162E4B]">₺{{ number_format((float) $bagis->toplam_tutar, 2, ',', '.') }}</span>
                                        @if ($bagis->makbuzUrl())
                                            <a href="{{ $bagis->makbuzUrl() }}" target="_blank" rel="noopener" class="text-sm font-semibold text-[#B27829] hover:text-[#E95925]">Makbuz</a>
                                        @else
                                            <span class="text-xs text-slate-500">Makbuz hazırlanıyor</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="uye-profil__notice">
                                    Henüz tamamlanmış bağış kaydınız bulunmuyor. Dilerseniz bağış sayfasından yeni bir destek oluşturabilirsiniz.
                                </div>
                            @endforelse
                        </div>

                        <div data-profil-panel="ekayit" class="hidden">
                            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-['Libre_Baskerville'] text-xl font-bold text-[#162E4B]">E-Kayıt Takibi</h3>
                                    <p class="mt-1 text-sm text-slate-500">Velisi olduğunuz öğrencilerin başvuru durumunu buradan izleyebilirsiniz.</p>
                                </div>
                                @if (($ekayitOzeti['adet'] ?? 0) > 0)
                                    <span class="uye-profil__pill uye-profil__pill--gold">{{ $ekayitOzeti['adet'] }} Başvuru</span>
                                @endif
                            </div>

                            @if (($ekayitOzeti['adet'] ?? 0) > 0)
                                <div class="mb-6 grid gap-3 md:grid-cols-3">
                                    <div class="uye-profil__stat-card bg-[#F7F5F0]">
                                        <p class="font-['Libre_Baskerville'] text-2xl font-bold text-[#162E4B]">{{ $ekayitOzeti['adet'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Toplam Başvuru</p>
                                    </div>
                                    <div class="uye-profil__stat-card border border-[#B27829]/20 bg-[#B27829]/10">
                                        <p class="font-['Libre_Baskerville'] text-2xl font-bold text-[#B27829]">{{ $ekayitOzeti['bekleyen'] }}</p>
                                        <p class="mt-1 text-xs text-[#B27829]">Bekleyen / Yedek</p>
                                    </div>
                                    <div class="uye-profil__stat-card bg-[#ecfdf5]">
                                        <p class="font-['Libre_Baskerville'] text-2xl font-bold text-[#15803d]">{{ $ekayitOzeti['onaylanan'] }}</p>
                                        <p class="mt-1 text-xs text-emerald-700">Onaylanan</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    @foreach ($ekayitKayitlar as $kayit)
                                        @php
                                            $durumSinifi = match ($kayit->durum?->value) {
                                                'onaylandi' => 'uye-profil__pill uye-profil__pill--green',
                                                'reddedildi' => 'uye-profil__pill uye-profil__pill--red',
                                                'yedek' => 'uye-profil__pill uye-profil__pill--gold',
                                                default => 'uye-profil__pill uye-profil__pill--gold',
                                            };
                                        @endphp
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p class="text-sm font-semibold text-[#162E4B]">{{ $kayit->ogrenciBilgisi?->ad_soyad ?: 'Öğrenci bilgisi bekleniyor' }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        {{ $kayit->sinif?->ad ?: 'Sınıf bilgisi yok' }}
                                                        @if (filled($kayit->sinif?->donem?->ogretim_yili))
                                                            · {{ $kayit->sinif->donem->ogretim_yili }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <span class="{{ $durumSinifi }}">{{ $kayit->durum?->label() ?? 'Beklemede' }}</span>
                                            </div>

                                            <div class="mt-3 grid gap-3 text-sm text-slate-600 md:grid-cols-2">
                                                <div>
                                                    <span class="font-semibold text-[#162E4B]">Veli:</span>
                                                    {{ $kayit->veliBilgisi?->ad_soyad ?: ($uye->ad_soyad ?: 'Belirtilmedi') }}
                                                </div>
                                                <div>
                                                    <span class="font-semibold text-[#162E4B]">Son Güncelleme:</span>
                                                    {{ $kayit->durum_tarihi?->format('d.m.Y H:i') ?: $kayit->updated_at?->format('d.m.Y H:i') ?: 'Bekleniyor' }}
                                                </div>
                                            </div>

                                            @if (filled($kayit->durum_notu_formatli))
                                                <div class="mt-3 rounded-xl bg-[#F7F5F0] px-3.5 py-3 text-sm text-slate-600">
                                                    <span class="font-semibold text-[#162E4B]">Not:</span>
                                                    {{ $kayit->durum_notu_formatli }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="uye-profil__notice">
                                    Henüz aktif bir veli başvurunuz görünmüyor. E-kayıt başvurunuz oluştuğunda öğrenci adı, sınıfı ve güncel durum bilgisi burada listelenecek.
                                </div>
                            @endif
                        </div>

                        <div data-profil-panel="etkinlik" class="hidden space-y-6">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-['Libre_Baskerville'] text-xl font-bold text-[#162E4B]">Etkinlikler</h3>
                                    <p class="mt-1 text-sm text-slate-500">Mezun ağı için öne çıkan buluşmaları buradan inceleyebilirsiniz.</p>
                                </div>
                                <a href="{{ route('etkinlikler.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-[#162E4B] transition hover:bg-[#F7F5F0]">Tüm Etkinlikler</a>
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Yaklaşan</p>
                                @forelse ($yaklasanEtkinlikler as $etkinlik)
                                    <div class="uye-profil__event-row">
                                        <div class="uye-profil__event-date">
                                            <p class="font-['Libre_Baskerville'] text-lg font-bold leading-none">{{ $etkinlik->baslangic_tarihi?->format('d') ?: '—' }}</p>
                                            <p class="mt-1 text-[10px] font-semibold uppercase text-[#EBDFB5]/70">{{ $etkinlik->baslangic_tarihi?->translatedFormat('M') }}</p>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-[#162E4B]">{{ $etkinlik->baslik }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $etkinlik->baslangic_tarihi?->format('H:i') ?: '--:--' }} · {{ $etkinlik->konum_ad ?: ($etkinlik->konum_il ?: 'Detay sayfasında') }}</p>
                                            <a href="{{ route('etkinlikler.show', $etkinlik->slug) }}" class="mt-2 inline-flex text-sm font-semibold text-[#B27829] hover:text-[#E95925]">Detayları Gör</a>
                                        </div>
                                    </div>
                                @empty
                                    <div class="uye-profil__notice">Şu anda yayında yaklaşan etkinlik bulunmuyor.</div>
                                @endforelse
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Geçmişten</p>
                                @forelse ($gecmisEtkinlikler as $etkinlik)
                                    <div class="uye-profil__event-row opacity-80">
                                        <div class="uye-profil__event-date !bg-slate-500">
                                            <p class="font-['Libre_Baskerville'] text-lg font-bold leading-none">{{ $etkinlik->baslangic_tarihi?->format('d') ?: '—' }}</p>
                                            <p class="mt-1 text-[10px] font-semibold uppercase text-white/70">{{ $etkinlik->baslangic_tarihi?->translatedFormat('M') }}</p>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-[#162E4B]">{{ $etkinlik->baslik }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $etkinlik->konum_ad ?: ($etkinlik->konum_il ?: 'Konum bilgisi yok') }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="uye-profil__notice">Geçmiş etkinlik listesi şu an boş görünüyor.</div>
                                @endforelse
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
