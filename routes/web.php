<?php

use App\Http\Controllers\HaberOnayController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Üye route'ları
require __DIR__ . '/uye.php';

Route::get('/haber-onayla/{token}', [HaberOnayController::class, 'onayla'])->name('haber.onayla');
Route::get('/haber-reddet/{token}', [HaberOnayController::class, 'reddet'])->name('haber.reddet');
