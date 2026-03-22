@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-red-600 mb-4">✗ Hata</h1>
            <p class="text-gray-700 mb-6">{{ $mesaj }}</p>
            <p class="text-gray-600 text-sm">Lütfen daha sonra tekrar deneyin.</p>
        </div>
    </div>
</div>
@endsection
