use App\Console\Commands\HaberSmSmartCropSon6;
Artisan::command('haber:sm-smart-crop-son6', [HaberSmSmartCropSon6::class, 'handle']);
use App\Console\Commands\HaberOrijinalGorselKopyalaSon10;
Artisan::command('haber:orijinal-gorsel-kopyala-son10', [HaberOrijinalGorselKopyalaSon10::class, 'handle']);
use App\Console\Commands\HaberSmSmartCropSon5;
Artisan::command('haber:sm-smart-crop-son5', [HaberSmSmartCropSon5::class, 'handle']);
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
Schedule::command('queue:work --stop-when-empty --max-time=55')->everyMinute()->withoutOverlapping();
Schedule::command('bagis:terk-sepet')->everyTwoHours();
Schedule::command('bagis:rapor-gonder gunluk')->dailyAt('08:00');
Schedule::command('bagis:rapor-gonder haftalik')->weeklyOn(1, '08:00');
Schedule::command('bagis:rapor-gonder aylik')->monthlyOn(1, '08:00');
Schedule::command('ekayit:donem-kontrol')->dailyAt('00:05');
Schedule::command('yedek:log-aylik')->monthlyOn(1, '03:00')->withoutOverlapping();
Schedule::command('yedek:db-gunluk')->dailyAt('04:00')->withoutOverlapping();
Schedule::command('yedek:db-aylik')->monthlyOn(1, '05:00')->withoutOverlapping();
Schedule::command('site-haritasi:olustur')->dailyAt('03:30')->withoutOverlapping();
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
        ->where('onay_epostasi_gonderildi_at', '<=', now()->subMinutes(config('services.haber_onay.sms_dakika')))
        ->whereNull('onay_sms_gonderildi_at')
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

            $haber->update(['onay_sms_gonderildi_at' => now()]);

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
