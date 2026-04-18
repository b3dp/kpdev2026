<?php

namespace App\Services;

use App\Models\Haber;
use App\Models\HaberAiRevizyonu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class HaberAiRevizyonService
{
    public function __construct(
        protected HaberAiDiffService $haberAiDiffService,
    ) {}

    public function revizyonOlustur(Haber $haber, array $duzeltilmisVeri, string $islemTipi = 'ai_imla_duzeltme', bool $uygulandiMi = false): ?HaberAiRevizyonu
    {
        try {
            return HaberAiRevizyonu::query()->create([
                'haber_id' => $haber->id,
                'olusturan_yonetici_id' => Auth::id(),
                'islem_tipi' => $islemTipi,
                'model' => config('services.gemini.model', config('services.gemini.default_model')),
                'orijinal_baslik' => $haber->baslik,
                'duzeltilmis_baslik' => $duzeltilmisVeri['baslik'] ?? $haber->baslik,
                'orijinal_icerik' => $haber->icerik,
                'duzeltilmis_icerik' => $duzeltilmisVeri['icerik'] ?? $haber->icerik,
                'orijinal_ozet' => $haber->ozet,
                'duzeltilmis_ozet' => $duzeltilmisVeri['ozet'] ?? $haber->ozet,
                'orijinal_meta_description' => $haber->meta_description,
                'duzeltilmis_meta_description' => $duzeltilmisVeri['meta_description'] ?? $haber->meta_description,
                'diff_ozeti_json' => $this->haberAiDiffService->diffOzetiHazirla([
                    'icerik' => $haber->icerik,
                    'ozet' => $haber->ozet,
                    'meta_description' => $haber->meta_description,
                ], [
                    'icerik' => $duzeltilmisVeri['icerik'] ?? $haber->icerik,
                    'ozet' => $duzeltilmisVeri['ozet'] ?? $haber->ozet,
                    'meta_description' => $duzeltilmisVeri['meta_description'] ?? $haber->meta_description,
                ]),
                'uygulandi_mi' => $uygulandiMi,
                'uygulandi_at' => $uygulandiMi ? now() : null,
            ]);
        } catch (Throwable $exception) {
            Log::error('HaberAiRevizyonService@revizyonOlustur hata', [
                'haber_id' => $haber->id,
                'mesaj' => $exception->getMessage(),
                'satir' => $exception->getLine(),
            ]);

            return null;
        }
    }

    public function revizyonuUygula(HaberAiRevizyonu $revizyon): bool
    {
        try {
            $haber = $revizyon->haber;

            if (! $haber) {
                return false;
            }

            $haber->update([
                'baslik' => $revizyon->duzeltilmis_baslik ?: $haber->baslik,
                'icerik' => $revizyon->duzeltilmis_icerik,
                'ozet' => $revizyon->duzeltilmis_ozet,
                'meta_description' => $revizyon->duzeltilmis_meta_description,
                'ai_onay' => true,
            ]);

            $revizyon->update([
                'uygulandi_mi' => true,
                'uygulandi_at' => now(),
            ]);

            return true;
        } catch (Throwable $exception) {
            Log::error('HaberAiRevizyonService@revizyonuUygula hata', [
                'revizyon_id' => $revizyon->id,
                'mesaj' => $exception->getMessage(),
                'satir' => $exception->getLine(),
            ]);

            return false;
        }
    }

    public function revizyonuGeriAl(HaberAiRevizyonu $revizyon): bool
    {
        try {
            $haber = $revizyon->haber;

            if (! $haber) {
                return false;
            }

            $haber->update([
                'baslik' => $revizyon->orijinal_baslik ?: $haber->baslik,
                'icerik' => $revizyon->orijinal_icerik,
                'ozet' => $revizyon->orijinal_ozet,
                'meta_description' => $revizyon->orijinal_meta_description,
                'ai_onay' => false,
            ]);

            $revizyon->update([
                'geri_alindi_mi' => true,
                'geri_alindi_at' => now(),
            ]);

            return true;
        } catch (Throwable $exception) {
            Log::error('HaberAiRevizyonService@revizyonuGeriAl hata', [
                'revizyon_id' => $revizyon->id,
                'mesaj' => $exception->getMessage(),
                'satir' => $exception->getLine(),
            ]);

            return false;
        }
    }
}