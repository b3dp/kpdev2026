@extends('layouts.app')

@php
    $metaDescription = 'Kestanepazari atolyeleri: atolyelere ait kurumsal sayfalari gorselli olarak inceleyin.';
    $varsayilanOg = 'https://cdn.kestanepazari.org.tr/logo.png';
    $ilkGorsel = $atolyeSayfalari->first()?->kart_gorseli;
    $ogImage = filled($ilkGorsel) ? $ilkGorsel : $varsayilanOg;
@endphp

@section('title', 'Atölyeler')
@section('meta_description', $metaDescription)
@section('canonical', route('kurumsal.show', ['slug' => 'atolyeler']))
@section('og_image', $ogImage)

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 lg:px-6 lg:py-12">
        <div class="mb-8">
            <h1 class="font-baskerville text-3xl font-bold text-primary md:text-5xl">Atölyeler</h1>
        </div>

        @if($atolyeSayfalari->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
                Atölye sayfasi bulunamadi.
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach($atolyeSayfalari as $sayfa)
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
