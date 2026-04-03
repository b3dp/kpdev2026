@extends('layouts.app')

@section('title', $haber->seo_baslik ?? $haber->baslik)
@section('meta_description', $haber->meta_description ?? $haber->ozet)

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
            <article class="rounded-xl border border-primary/10 bg-white p-6">
                <h1 class="mb-3 font-baskerville text-3xl font-bold text-primary">{{ $haber->baslik }}</h1>
                <p class="font-jakarta text-sm text-teal-muted">
                    {{ $haber->yayin_tarihi?->format('d.m.Y H:i') }}
                </p>

                @if($haber->ozet)
                    <p class="mt-4 font-jakarta leading-7 text-primary/80">{{ $haber->ozet }}</p>
                @endif
            </article>

            @include('components.sidebar')
        </div>
    </section>
@endsection