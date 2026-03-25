@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8 text-center">

        {{-- Kırmızı X ikonu --}}
        <div class="flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mx-auto mb-6">
            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-3">
            Bir Hata Oluştu
        </h1>

        <p class="text-red-600 font-medium mb-8">
            {{ $hata ?? 'Beklenmedik bir hata oluştu.' }}
        </p>

        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('uye.giris.form') }}"
           class="inline-block bg-gray-600 text-white px-6 py-3 rounded-md hover:bg-gray-700 transition font-medium">
            Geri Dön
        </a>

    </div>
</div>
@endsection
