@extends('layouts.app')

@section('title', $etkinlik->seo_baslik ?? $etkinlik->baslik)
@section('meta_description', $etkinlik->meta_description ?? $etkinlik->ozet)

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
            <article class="rounded-xl border border-primary/10 bg-white p-6">
                <h1 class="mb-3 font-baskerville text-3xl font-bold text-primary">{{ $etkinlik->baslik }}</h1>
                <p class="font-jakarta text-sm text-teal-muted">
                    {{ $etkinlik->baslangic_tarihi?->format('d.m.Y H:i') }}
                    @if($etkinlik->bitis_tarihi) - {{ $etkinlik->bitis_tarihi->format('d.m.Y H:i') }} @endif
                </p>

                @if($etkinlik->ozet)
                    <p class="mt-4 font-jakarta leading-7 text-primary/80">{{ $etkinlik->ozet }}</p>
                @endif
            </article>

            @include('components.sidebar')
        </div>
    </section>
@endsection