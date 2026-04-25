@extends('layouts.app')

@php
    $metaDescription = 'Kestanepazari kurumlari: kurumlara ait kurumsal sayfalari gorselli olarak inceleyin.';
    $varsayilanOg = 'https://cdn.kestanepazari.org.tr/logo.png';
    $ilkGorsel = $kurumSayfalari->first()?->kart_gorseli;
    $ogImage = filled($ilkGorsel) ? $ilkGorsel : $varsayilanOg;
    $heroGorsel = $ilkGorsel ?: $varsayilanOg;

    $yatayMenu = collect($standartMenuSayfalari ?? [])->map(fn ($menuSayfa) => [
        'href' => route('kurumsal.show', ['slug' => $menuSayfa->slug]),
        'label' => $menuSayfa->ad,
        'aktif' => false,
    ])->values();

    $yatayMenu->push([
        'href' => route('kurumsal.show', ['slug' => 'kurumlar']),
        'label' => 'Kurumlar',
        'aktif' => true,
    ]);

    $yatayMenu->push([
        'href' => route('kurumsal.show', ['slug' => 'atolyeler']),
        'label' => 'Atölyeler',
        'aktif' => false,
    ]);
@endphp

@section('title', 'Kurumlar')
@section('meta_description', $metaDescription)
@section('canonical', route('kurumsal.show', ['slug' => 'kurumlar']))
@section('og_image', $ogImage)

@section('content')
    <section class="kurumsal-hero">
        @if($heroGorsel)
            <div class="kurumsal-hero-image" style="background-image: url('{{ $heroGorsel }}');"></div>
        @endif

        <div class="kurumsal-hero-overlay"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 lg:px-6 lg:py-20">
            <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-[#ebdfb5]/70">
                <a href="{{ route('home') }}" class="transition hover:text-[#ebdfb5]">Ana Sayfa</a>
                <span>/</span>
                <a href="{{ route('kurumsal.show') }}" class="transition hover:text-[#ebdfb5]">Kurumsal</a>
                <span>/</span>
                <span class="text-[#ebdfb5]">Kurumlar</span>
            </div>

            <div>
                <h1 class="font-baskerville text-3xl font-bold leading-tight text-[#ebdfb5] md:text-5xl">Kurumlar</h1>
            </div>
        </div>
    </section>

    <div class="kurumsal-top-nav">
        <div class="mx-auto max-w-7xl px-4 lg:px-6">
            <div class="kurumsal-top-nav-inner">
                @foreach($yatayMenu as $item)
                    <a href="{{ $item['href'] }}" class="page-nav-link {{ $item['aktif'] ? 'active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <section class="mx-auto max-w-7xl px-4 py-10 lg:px-6 lg:py-12">
        @if($kurumSayfalari->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
                Kurum sayfasi bulunamadi.
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach($kurumSayfalari as $sayfa)
                    @php
                        $kartGorseli = $sayfa->kart_gorseli ?: $varsayilanOg;
                        $kartOzet = $sayfa->ozet ?: \Illuminate\Support\Str::limit(strip_tags((string) $sayfa->icerik), 160, '...');
                    @endphp

                    <a href="{{ route('kurumsal.show', ['slug' => $sayfa->slug]) }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div style="position:relative;width:100%;padding-top:56.25%;background:#f1f5f9;">
                            <img
                                src="{{ $kartGorseli }}"
                                alt="{{ $sayfa->ad }}"
                                loading="lazy"
                                style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"
                            >
                        </div>

                        <div class="p-4">
                            <h2 class="mb-2 line-clamp-2 font-jakarta text-base font-bold text-primary">{{ $sayfa->ad }}</h2>
                            <p class="line-clamp-3 font-jakarta text-sm leading-6 text-[#62868d]">{{ $kartOzet }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
