<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Üye route'ları
require __DIR__ . '/uye.php';
