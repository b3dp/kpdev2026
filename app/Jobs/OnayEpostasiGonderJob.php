<?php

namespace App\Jobs;

use App\Models\Haber;
use App\Services\HaberOnayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class OnayEpostasiGonderJob implements ShouldQueue
{
    use Queueable;

    public string $queue = 'default';

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public int $haberId)
    {
    }

    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function handle(HaberOnayService $onayService): void
    {
        $haber = Haber::query()->with('yonetici')->find($this->haberId);

        if (! $haber || ! $haber->yonetici?->eposta) {
            return;
        }

        $yayinToken = $onayService->tokenOlustur($haber, 'yayin');
        $redToken = $onayService->tokenOlustur($haber, 'red');

        $yayinLinki = url('/haber-onayla/' . $yayinToken);
        $redLinki = url('/haber-reddet/' . $redToken);

        Mail::raw(
            "Haber: {$haber->baslik}\n\nYayınla: {$yayinLinki}\nReddet: {$redLinki}\n\nBu linkler 1 saat geçerlidir.",
            function ($message) use ($haber): void {
                $message
                    ->to($haber->yonetici->eposta)
                    ->subject('Haber Onay Linkleri');
            }
        );
    }
}
