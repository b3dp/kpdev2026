<?php

use App\Console\Commands\BagisTerkSepetKontrol;
use App\Console\Commands\BagisTuruOtomatikKontrol;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bagis:hicri-kontrol')->hourly();
Schedule::command('bagis:terk-sepet')->everyTwoHours();
