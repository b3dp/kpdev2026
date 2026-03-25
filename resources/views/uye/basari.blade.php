@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8 text-center">

        {{-- Yeşil tik ikonu --}}
        <div class="flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-3">
            {{ $baslik ?? 'İşlem Başarılı' }}
        </h1>

        <p class="text-gray-600 mb-8">
            {{ $mesaj ?? 'İşlem başarıyla tamamlandı.' }}
        </p>

        @if(isset($link) && isset($linkMetin))
            <a href="{{ $link }}"
               class="inline-block bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition font-medium">
                {{ $linkMetin }}
            </a>
        @else
            <a href="{{ route('uye.profil.index') }}"
               class="inline-block bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition font-medium">
                Profilime Git
            </a>
        @endif

    </div>
</div>
@endsection
