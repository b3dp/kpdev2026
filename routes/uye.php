<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Uye\GirisController;
use App\Http\Controllers\Uye\KayitController;
use App\Http\Controllers\Uye\ProfilController;
use App\Http\Controllers\Uye\AbonelikController;

// Giriş yapılmamış üyeler
Route::middleware('guest:uye')->group(function () {
    Route::get('/giris', [GirisController::class, 'form'])->name('uye.giris.form');
    Route::post('/giris', [GirisController::class, 'giris'])->name('uye.giris.giris')->middleware('throttle:5,1');
    Route::post('/giris/otp', [GirisController::class, 'otpDogrula'])->name('uye.giris.otp');
    
    Route::get('/kayit', [KayitController::class, 'form'])->name('uye.kayit.form');
    Route::post('/kayit', [KayitController::class, 'kayit'])->name('uye.kayit.kayit')->middleware('throttle:3,1');
});

// Giriş yapan üyeler
Route::middleware('auth:uye')->group(function () {
    Route::post('/cikis', [GirisController::class, 'cikis'])->name('uye.cikis');
    Route::get('/profilim', [ProfilController::class, 'index'])->name('uye.profil.index');
    Route::post('/profilim', [ProfilController::class, 'guncelle'])->name('uye.profil.guncelle');
    Route::post('/profil/abonelik', [ProfilController::class, 'abonelikGuncelle'])->name('uye.abonelik.guncelle');
});

// Abonelik iptal (token bazlı, unauthenticated)
Route::get('/abonelik/iptal/{token}/{kanal}', [AbonelikController::class, 'iptal'])->name('uye.abonelik.iptal');
