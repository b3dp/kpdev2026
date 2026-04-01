<?php

use App\Console\Commands\BagisTerkSepetKontrol;
use App\Console\Commands\BagisRaporGonder;
use App\Console\Commands\BagisTuruOtomatikKontrol;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bagis:hicri-kontrol')->hourly();
Schedule::command('bagis:terk-sepet')->everyTwoHours();
Schedule::command('bagis:rapor-gonder gunluk')->dailyAt('08:00');
Schedule::command('bagis:rapor-gonder haftalik')->weeklyOn(1, '08:00');
Schedule::command('bagis:rapor-gonder aylik')->monthlyOn(1, '08:00');
Schedule::command('ekayit:donem-kontrol')->dailyAt('00:05');
Schedule::command('sms:durum-guncelle')->everyTenMinutes();
Schedule::call(function () {
    \App\Models\Haber::query()
        ->where('durum', \App\Enums\HaberDurumu::Planli->value)
        ->where('yayin_tarihi', '<=', now())
        ->update(['durum' => \App\Enums\HaberDurumu::Yayinda->value]);
})->everyMinute();

Schedule::call(function () {
    $haberler = \App\Models\Haber::query()
        ->where('durum', \App\Enums\HaberDurumu::Incelemede->value)
        ->whereNotNull('onay_epostasi_gonderildi_at')
        ->where('onay_epostasi_gonderildi_at', '<=', now()->subHour())
        ->get();

    foreach ($haberler as $haber) {
        $editor = \App\Models\Yonetici::find(config('services.haber_onay.editor_id'));
        if (! $editor || ! $editor->telefon) {
            continue;
        }

        $mesaj = 'Inceleme bekleyen haberiniz var: "'
            . mb_substr($haber->baslik, 0, 40)
            . '". Panel: ' . config('app.url') . '/yonetim/haberler';

        try {
            app(\App\Services\HermesService::class)->sendSMS(
                [$editor->telefon],
                $mesaj
            );

            \Illuminate\Support\Facades\Log::info('[HaberOnay] SMS hatirlatma gonderildi', [
                'haber_id' => $haber->id,
                'editor_telefon' => $editor->telefon,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[HaberOnay] SMS gonderilemedi', [
                'haber_id' => $haber->id,
                'hata' => $e->getMessage(),
            ]);
        }
    }
})->everyMinute();
