<?php

use App\Http\Controllers\HaberAiController;
use App\Http\Controllers\HaberOnayController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Üye route'ları
require __DIR__ . '/uye.php';

Route::get('/haber-onayla/{token}', [HaberOnayController::class, 'onayla'])->name('haber.onayla');
Route::get('/haber-reddet/{token}', [HaberOnayController::class, 'reddet'])->name('haber.reddet');
Route::get('/haber-onay/{haber}/yayinla', [HaberOnayController::class, 'yayinla'])->name('haber.onay.yayinla');

Route::middleware('auth:admin')->group(function () {
    Route::post('/yonetim/haberler/{haber}/ai-baslat', [HaberAiController::class, 'baslat'])->name('haber.ai.baslat');
    Route::get('/yonetim/haberler/{haber}/ai-durum', [HaberAiController::class, 'durum'])->name('haber.ai.durum');
});
