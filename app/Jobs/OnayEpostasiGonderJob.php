<?php

namespace App\Jobs;

use App\Models\Haber;
use App\Models\Yonetici;
use App\Services\ZeptomailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class OnayEpostasiGonderJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 3;

    public function __construct(public int $haberId)
    {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [60, 120, 300];
    }

    public function handle(ZeptomailService $zeptomail): void
    {
        $haber = Haber::query()
            ->with(['yonetici', 'kategori', 'kisiler', 'kurumlar'])
            ->find($this->haberId);

        if (! $haber) {
            return;
        }

        // Token üret
        $token = bin2hex(random_bytes(32));

        // Eski tokenı geçersiz kıl, yeni token kaydet
        $haber->update([
            'onay_token' => $token,
            'onay_token_expires_at' => now()->addHours(3),
            'onay_epostasi_gonderildi_at' => now(),
        ]);

        // Editörü bul
        $editor = Yonetici::find(config('services.haber_onay.editor_id'));
        if (! $editor || ! $editor->eposta) {
            Log::error('[OnayEpostasiGonderJob] Editör bulunamadı veya e-postası yok', [
                'editor_id' => config('services.haber_onay.editor_id'),
                'haber_id' => $haber->id,
            ]);

            return;
        }

        // URL'leri oluştur
        $yayinlaUrl = route('haber.onay.yayinla', [
            'haber' => $haber->id,
            'token' => $token,
        ]);
        $duzenleUrl = config('app.url') . '/yonetim/haberler/' . $haber->id . '/edit';

        // Görsel URL
        $gorselUrl = $haber->gorsel_lg ?: null;

        // Kişi ve kurumlar
        $kisiler = $haber->kisiler->pluck('ad_soyad')->join(', ');
        $kurumlar = $haber->kurumlar->pluck('ad')->join(', ');

        // E-posta gönder
        $zeptomail->haberOnayGonder(
            eposta: $editor->eposta,
            ad: $editor->ad_soyad,
            haberBaslik: $haber->baslik,
            haberIcerik: strip_tags($haber->icerik ?? ''),
            haberKategori: $haber->kategori?->ad ?? '—',
            kisiler: $kisiler,
            kurumlar: $kurumlar,
            gorselUrl: $gorselUrl,
            yayinlaUrl: $yayinlaUrl,
            duzenleUrl: $duzenleUrl,
        );

        Log::info('[OnayEpostasiGonderJob] Onay e-postası gönderildi', [
            'haber_id' => $haber->id,
            'editor_id' => $editor->id,
            'token_expires' => now()->addHours(3),
        ]);
    }
}
