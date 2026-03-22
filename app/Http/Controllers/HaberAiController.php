<?php

namespace App\Http\Controllers;

use App\Enums\HaberDurumu;
use App\Jobs\OnayEpostasiGonderJob;
use App\Models\Haber;
use App\Models\Kisi;
use App\Models\Kurum;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HaberAiController extends Controller
{
    public function baslat(Haber $haber, GeminiService $geminiService): JsonResponse
    {
        try {
            if ($haber->ai_islem_yuzde > 0 && $haber->ai_islem_yuzde < 100) {
                return response()->json(['message' => 'AI işlemi zaten devam ediyor.'], 409);
            }

            $haber->update([
                'ai_islendi' => false,
                'ai_islem_yuzde' => 5,
                'ai_islem_adim' => 'AI işlemi başlatıldı',
            ]);

            $metin = (string) $haber->icerik;

            $haber->update(['ai_islem_yuzde' => 20, 'ai_islem_adim' => 'İmla düzeltme yapılıyor']);
            $duzeltilmisMetin = $geminiService->imlaDuzelt($metin);

            $haber->update(['ai_islem_yuzde' => 40, 'ai_islem_adim' => 'Özet üretiliyor']);
            $ozet = filled($haber->ozet) ? $haber->ozet : $geminiService->ozetUret($duzeltilmisMetin);

            $haber->update(['ai_islem_yuzde' => 60, 'ai_islem_adim' => 'Meta description üretiliyor']);
            $metaDescription = filled($haber->meta_description)
                ? $haber->meta_description
                : $geminiService->metaDescriptionUret($duzeltilmisMetin);

            $haber->update([
                'icerik' => $duzeltilmisMetin,
                'ozet' => $ozet,
                'meta_description' => $metaDescription,
            ]);

            $haber->update(['ai_islem_yuzde' => 75, 'ai_islem_adim' => 'Kişi tespiti yapılıyor']);
            $kisiSonuclar = $geminiService->kisiTespitEt($duzeltilmisMetin);
            foreach ($kisiSonuclar as $kisiVerisi) {
                $adSoyad = trim((string) ($kisiVerisi['ad_soyad'] ?? ''));
                if (! filled($adSoyad)) {
                    continue;
                }

                $parcalar = preg_split('/\s+/', $adSoyad);
                $ad = $parcalar[0] ?? null;
                $soyad = count($parcalar) > 1 ? implode(' ', array_slice($parcalar, 1)) : null;

                if (! filled($ad) || ! filled($soyad)) {
                    continue;
                }

                $kisi = Kisi::query()->firstOrCreate(
                    ['ad' => $ad, 'soyad' => $soyad],
                    ['ai_onaylandi' => false]
                );

                DB::table('haber_kisiler')->updateOrInsert(
                    ['haber_id' => $haber->id, 'kisi_id' => $kisi->id],
                    [
                        'rol' => $kisiVerisi['rol'] ?? null,
                        'onay_durumu' => 'beklemede',
                        'updated_at' => now(),
                        'created_at' => now(),
                        'deleted_at' => null,
                    ]
                );
            }

            $haber->update(['ai_islem_yuzde' => 90, 'ai_islem_adim' => 'Kurum tespiti yapılıyor']);
            $kurumSonuclar = $geminiService->kurumTespitEt($duzeltilmisMetin);
            foreach ($kurumSonuclar as $kurumVerisi) {
                $ad = trim((string) ($kurumVerisi['ad'] ?? ''));
                if (! filled($ad)) {
                    continue;
                }

                $kurum = Kurum::query()->firstOrCreate(
                    ['ad' => $ad],
                    ['tip' => 'diger', 'aktif' => false]
                );

                DB::table('haber_kurumlar')->updateOrInsert(
                    ['haber_id' => $haber->id, 'kurum_id' => $kurum->id],
                    [
                        'onay_durumu' => 'beklemede',
                        'updated_at' => now(),
                        'created_at' => now(),
                        'deleted_at' => null,
                    ]
                );
            }

            $haber->update([
                'ai_islendi' => true,
                'ai_islem_yuzde' => 100,
                'ai_islem_adim' => 'AI işlemleri tamamlandı',
                'durum' => HaberDurumu::Incelemede,
            ]);

            dispatch_sync(new OnayEpostasiGonderJob($haber->id));

            return response()->json(['message' => 'AI işlemleri tamamlandı.']);
        } catch (Throwable $exception) {
            $haber->update([
                'ai_islendi' => false,
                'ai_islem_adim' => 'AI işleminde hata oluştu',
            ]);

            $detayRapor = implode("\n", [
                'Hata: ' . $exception->getMessage(),
                'Dosya: ' . $exception->getFile(),
                'Satır: ' . $exception->getLine(),
            ]);

            activity('ai_haber_isleme_hata')
                ->performedOn($haber)
                ->withProperties([
                    'hata' => $exception->getMessage(),
                    'dosya' => $exception->getFile(),
                    'satir' => $exception->getLine(),
                ])
                ->log('AI işlemi controller içinde hata verdi');

            return response()->json([
                'message' => 'AI işlemi sırasında hata oluştu.',
                'detay_rapor' => $detayRapor,
            ], 500);
        }
    }

    public function durum(Haber $haber): JsonResponse
    {
        return response()->json([
            'yuzde' => $haber->ai_islem_yuzde,
            'adim' => $haber->ai_islem_adim,
            'tamamlandi' => $haber->ai_islem_yuzde >= 100,
            'ai_islendi' => $haber->ai_islendi,
            'durum' => $haber->durum?->value,
        ]);
    }
}
