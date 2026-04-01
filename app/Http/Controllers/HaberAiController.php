<?php

namespace App\Http\Controllers;

use App\Enums\HaberDurumu;
use App\Jobs\OnayEpostasiGonderJob;
use App\Models\Haber;
use App\Models\Kisi;
use App\Models\Kurum;
use App\Services\GeminiService;
use App\Services\LevenshteinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HaberAiController extends Controller
{
    public function baslat(Haber $haber, GeminiService $geminiService, LevenshteinService $levenshteinService): JsonResponse
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

            $hamHtml = (string) $haber->icerik;

            // İmla düzeltme için: izin verilen tag'lar korunur, inline style/class temizlenir
            $izinliTaglar = '<p><h1><h2><h3><h4><h5><h6><i><b><strong><u><blockquote><ul><ol><li>';
            $temizHtml = strip_tags($hamHtml, $izinliTaglar);
            // style, class, id attribute'larını temizle
            $temizHtml = preg_replace('/(<[a-z][a-z0-9]*)\s+(?:style|class|id|data-[a-z-]+)="[^"]*"/iu', '$1', $temizHtml);
            $temizHtml = preg_replace('/(<[a-z][a-z0-9]*)\s+(?:style|class|id|data-[a-z-]+)=\'[^\']*\'/iu', '$1', $temizHtml);

            // Kişi/kurum tespiti için: düz metin
            $metin = strip_tags($temizHtml);
            $metin = html_entity_decode($metin, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $metin = trim(preg_replace('/\s+/u', ' ', $metin) ?? $metin);

            $haber->update(['ai_islem_yuzde' => 20, 'ai_islem_adim' => 'İmla düzeltme yapılıyor']);
            $duzeltilmisMetin = $geminiService->imlaDuzelt($temizHtml);
            // Kişi/kurum tespiti için düzeltilmiş metinden düz metin üret
            $metin = strip_tags($duzeltilmisMetin);
            $metin = html_entity_decode($metin, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $metin = trim(preg_replace('/\s+/u', ' ', $metin) ?? $metin);

            $haber->update(['ai_islem_yuzde' => 40, 'ai_islem_adim' => 'Özet üretiliyor']);
            $ozet = $geminiService->ozetUret($duzeltilmisMetin);

            $haber->update(['ai_islem_yuzde' => 60, 'ai_islem_adim' => 'Meta description üretiliyor']);
            $metaDescription = $geminiService->metaDescriptionUret($duzeltilmisMetin);

            $haber->update([
                'icerik' => $duzeltilmisMetin,
                'ozet' => $ozet,
                'meta_description' => $metaDescription,
            ]);

            // Tekrar başlatmada ilişkileri sıfırdan kurmak için mevcut eşleşmeleri tamamen temizle.
            DB::table('haber_kisiler')
                ->where('haber_id', $haber->id)
                ->delete();

            DB::table('haber_kurumlar')
                ->where('haber_id', $haber->id)
                ->delete();

            $haber->update(['ai_islem_yuzde' => 75, 'ai_islem_adim' => 'Kişi tespiti yapılıyor']);
            $kisiSonuclar = $geminiService->kisiTespitEt($duzeltilmisMetin);
            if (empty($kisiSonuclar)) {
                $kisiSonuclar = $this->metindenKisiAdaylariAyikla($duzeltilmisMetin);
            }

            $mevcutKisiler = Kisi::query()->select(['id', 'ad', 'soyad'])->get();
            $kisiAramaListesi = $mevcutKisiler
                ->map(fn (Kisi $kisi) => ['id' => $kisi->id, 'ad' => trim($kisi->ad . ' ' . $kisi->soyad)])
                ->values();
            $islenenKisiAdlari = [];

            $eklenenKisiSayisi = 0;
            foreach ($kisiSonuclar as $kisiVerisi) {
                $adSoyad = $this->kisiAdiAyikla($kisiVerisi);
                if (! $this->kisiAdayiGecerliMi($adSoyad)) {
                    continue;
                }

                $kisiAnahtar = mb_strtolower($adSoyad);
                if (isset($islenenKisiAdlari[$kisiAnahtar])) {
                    continue;
                }
                $islenenKisiAdlari[$kisiAnahtar] = true;

                $parcalar = preg_split('/\s+/', $adSoyad);
                $ad = $parcalar[0] ?? null;
                $soyad = count($parcalar) > 1 ? implode(' ', array_slice($parcalar, 1)) : null;

                if (! filled($ad) || ! filled($soyad)) {
                    continue;
                }

                $kisi = Kisi::query()->where('ad', $ad)->where('soyad', $soyad)->first();
                $onayDurumu = 'onaylandi';

                if (! $kisi) {
                    $benzerKisiler = $levenshteinService->benzerBul($adSoyad, $kisiAramaListesi, 80);
                    $enBenzer = $benzerKisiler->first();

                    if ($enBenzer) {
                        $kisi = $mevcutKisiler->firstWhere('id', $enBenzer['kayit']['id']);
                        $onayDurumu = $enBenzer['skor'] >= 95 ? 'onaylandi' : 'beklemede';
                    } else {
                        $kisi = Kisi::query()->create([
                            'ad' => $ad,
                            'soyad' => $soyad,
                            'ai_onaylandi' => false,
                            'meslek' => $this->kisiRolAyikla($kisiVerisi),
                        ]);
                        $onayDurumu = 'beklemede';
                    }
                }

                DB::table('haber_kisiler')->updateOrInsert(
                    ['haber_id' => $haber->id, 'kisi_id' => $kisi->id],
                    [
                        'rol' => $this->kisiRolAyikla($kisiVerisi),
                        'onay_durumu' => $onayDurumu,
                        'updated_at' => now(),
                        'created_at' => now(),
                        'deleted_at' => null,
                    ]
                );
                $eklenenKisiSayisi++;
            }

            if ($eklenenKisiSayisi === 0) {
                $kisiSonuclar = $this->metindenKisiAdaylariAyikla($duzeltilmisMetin);
                foreach ($kisiSonuclar as $kisiVerisi) {
                    $adSoyad = $this->kisiAdiAyikla($kisiVerisi);
                    if (! $this->kisiAdayiGecerliMi($adSoyad)) {
                        continue;
                    }

                    $kisiAnahtar = mb_strtolower($adSoyad);
                    if (isset($islenenKisiAdlari[$kisiAnahtar])) {
                        continue;
                    }
                    $islenenKisiAdlari[$kisiAnahtar] = true;

                    $parcalar = preg_split('/\s+/', $adSoyad);
                    $ad = $parcalar[0] ?? null;
                    $soyad = count($parcalar) > 1 ? implode(' ', array_slice($parcalar, 1)) : null;

                    if (! filled($ad) || ! filled($soyad)) {
                        continue;
                    }

                    $kisi = Kisi::query()->where('ad', $ad)->where('soyad', $soyad)->first();
                    $onayDurumu = 'onaylandi';

                    if (! $kisi) {
                        $benzerKisiler = $levenshteinService->benzerBul($adSoyad, $kisiAramaListesi, 80);
                        $enBenzer = $benzerKisiler->first();

                        if ($enBenzer) {
                            $kisi = $mevcutKisiler->firstWhere('id', $enBenzer['kayit']['id']);
                            $onayDurumu = $enBenzer['skor'] >= 95 ? 'onaylandi' : 'beklemede';
                        } else {
                            $kisi = Kisi::query()->create([
                                'ad' => $ad,
                                'soyad' => $soyad,
                                'ai_onaylandi' => false,
                                'meslek' => $this->kisiRolAyikla($kisiVerisi),
                            ]);
                            $onayDurumu = 'beklemede';
                        }
                    }

                    DB::table('haber_kisiler')->updateOrInsert(
                        ['haber_id' => $haber->id, 'kisi_id' => $kisi->id],
                        [
                            'rol' => $this->kisiRolAyikla($kisiVerisi),
                            'onay_durumu' => $onayDurumu,
                            'updated_at' => now(),
                            'created_at' => now(),
                            'deleted_at' => null,
                        ]
                    );
                    $eklenenKisiSayisi++;
                }
            }

            $haber->update([
                'ai_islem_yuzde' => 90,
                'ai_islem_adim' => "Kurum tespiti yapılıyor (kişi: {$eklenenKisiSayisi})",
            ]);
            $kurumSonuclar = $geminiService->kurumTespitEt($duzeltilmisMetin);
            if (empty($kurumSonuclar)) {
                $kurumSonuclar = $this->metindenKurumAdaylariAyikla($duzeltilmisMetin);
            }

            $mevcutKurumlar = Kurum::query()->select(['id', 'ad'])->get();
            $kurumAramaListesi = $mevcutKurumlar
                ->map(fn (Kurum $kurum) => ['id' => $kurum->id, 'ad' => $kurum->ad])
                ->values();
            $islenenKurumAdlari = [];

            $eklenenKurumSayisi = 0;
            foreach ($kurumSonuclar as $kurumVerisi) {
                $ad = $this->kurumAdiAyikla($kurumVerisi);
                if (! $this->kurumAdayiGecerliMi($ad)) {
                    continue;
                }

                $kurumAnahtar = mb_strtolower($ad);
                if (isset($islenenKurumAdlari[$kurumAnahtar])) {
                    continue;
                }
                $islenenKurumAdlari[$kurumAnahtar] = true;

                $kurum = Kurum::query()->where('ad', $ad)->first();
                $onayDurumu = 'onaylandi';

                if (! $kurum) {
                    $benzerKurumlar = $levenshteinService->benzerBul($ad, $kurumAramaListesi, 80);
                    $enBenzer = $benzerKurumlar->first();

                    if ($enBenzer) {
                        $kurum = $mevcutKurumlar->firstWhere('id', $enBenzer['kayit']['id']);
                        $onayDurumu = $enBenzer['skor'] >= 95 ? 'onaylandi' : 'beklemede';
                    } else {
                        $kurum = Kurum::query()->create([
                            'ad' => $ad,
                            'tip' => trim((string) ($kurumVerisi['tip'] ?? 'diger')) ?: 'diger',
                            'aktif' => false,
                        ]);
                        $onayDurumu = 'beklemede';
                    }
                }

                DB::table('haber_kurumlar')->updateOrInsert(
                    ['haber_id' => $haber->id, 'kurum_id' => $kurum->id],
                    [
                        'onay_durumu' => $onayDurumu,
                        'updated_at' => now(),
                        'created_at' => now(),
                        'deleted_at' => null,
                    ]
                );
                $eklenenKurumSayisi++;
            }

            if ($eklenenKurumSayisi === 0) {
                $kurumSonuclar = $this->metindenKurumAdaylariAyikla($duzeltilmisMetin);
                foreach ($kurumSonuclar as $kurumVerisi) {
                    $ad = $this->kurumAdiAyikla($kurumVerisi);
                    if (! $this->kurumAdayiGecerliMi($ad)) {
                        continue;
                    }

                    $kurumAnahtar = mb_strtolower($ad);
                    if (isset($islenenKurumAdlari[$kurumAnahtar])) {
                        continue;
                    }
                    $islenenKurumAdlari[$kurumAnahtar] = true;

                    $kurum = Kurum::query()->where('ad', $ad)->first();
                    $onayDurumu = 'onaylandi';

                    if (! $kurum) {
                        $benzerKurumlar = $levenshteinService->benzerBul($ad, $kurumAramaListesi, 80);
                        $enBenzer = $benzerKurumlar->first();

                        if ($enBenzer) {
                            $kurum = $mevcutKurumlar->firstWhere('id', $enBenzer['kayit']['id']);
                            $onayDurumu = $enBenzer['skor'] >= 95 ? 'onaylandi' : 'beklemede';
                        } else {
                            $kurum = Kurum::query()->create([
                                'ad' => $ad,
                                'tip' => trim((string) ($kurumVerisi['tip'] ?? 'diger')) ?: 'diger',
                                'aktif' => false,
                            ]);
                            $onayDurumu = 'beklemede';
                        }
                    }

                    DB::table('haber_kurumlar')->updateOrInsert(
                        ['haber_id' => $haber->id, 'kurum_id' => $kurum->id],
                        [
                            'onay_durumu' => $onayDurumu,
                            'updated_at' => now(),
                            'created_at' => now(),
                            'deleted_at' => null,
                        ]
                    );
                    $eklenenKurumSayisi++;
                }
            }

            $haber->update([
                'ai_islendi' => true,
                'ai_islem_yuzde' => 100,
                'ai_islem_adim' => "AI işlemleri tamamlandı (kişi: {$eklenenKisiSayisi}, kurum: {$eklenenKurumSayisi})",
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

    private function kisiAdiAyikla(mixed $kisiVerisi): string
    {
        if (is_string($kisiVerisi)) {
            return trim($kisiVerisi);
        }

        if (! is_array($kisiVerisi)) {
            return '';
        }

        $adaylar = [
            $kisiVerisi['ad_soyad'] ?? null,
            $kisiVerisi['adSoyad'] ?? null,
            $kisiVerisi['isim'] ?? null,
            $kisiVerisi['ad'] ?? null,
            $kisiVerisi['kisi'] ?? null,
            $kisiVerisi['name'] ?? null,
        ];

        foreach ($adaylar as $aday) {
            $deger = trim((string) ($aday ?? ''));
            if (filled($deger)) {
                return $deger;
            }
        }

        return '';
    }

    private function kurumAdiAyikla(mixed $kurumVerisi): string
    {
        if (is_string($kurumVerisi)) {
            return trim($kurumVerisi);
        }

        if (! is_array($kurumVerisi)) {
            return '';
        }

        $adaylar = [
            $kurumVerisi['ad'] ?? null,
            $kurumVerisi['kurum'] ?? null,
            $kurumVerisi['kurum_adi'] ?? null,
            $kurumVerisi['name'] ?? null,
            $kurumVerisi['organization'] ?? null,
        ];

        foreach ($adaylar as $aday) {
            $deger = trim((string) ($aday ?? ''));
            if (filled($deger)) {
                return $deger;
            }
        }

        return '';
    }

    private function kisiRolAyikla(mixed $kisiVerisi): ?string
    {
        if (! is_array($kisiVerisi)) {
            return null;
        }

        $rol = trim((string) ($kisiVerisi['gorev'] ?? $kisiVerisi['rol'] ?? ''));

        return filled($rol) ? $rol : null;
    }

    private function metindenKisiAdaylariAyikla(string $metin): array
    {
        $adaylar = [];
        $yasakliKelimeler = [
            'Bakanlığı', 'Müdürlüğü', 'Üniversitesi', 'Belediyesi', 'Derneği', 'Vakfı', 'Holding',
            'Kampüsünde', 'Konuşmaları', 'Sahur', 'İkramı', 'Programı', 'Toplantısı', 'Açılış',
        ];
        $desen = '/\b([A-ZÇĞİÖŞÜ][a-zçğıöşü]{2,}(?:\s+[A-ZÇĞİÖŞÜ][a-zçğıöşü]{2,}){1,2})\b/u';

        preg_match_all($desen, $metin, $eslesmeler);
        foreach ($eslesmeler[1] ?? [] as $adSoyad) {
            $adSoyad = trim((string) $adSoyad);
            if (! $this->kisiAdayiGecerliMi($adSoyad)) {
                continue;
            }

            $kurumMu = false;
            foreach ($yasakliKelimeler as $kelime) {
                if (str_contains($adSoyad, $kelime)) {
                    $kurumMu = true;
                    break;
                }
            }

            if ($kurumMu) {
                continue;
            }

            $anahtar = mb_strtolower($adSoyad);
            $adaylar[$anahtar] = ['ad_soyad' => $adSoyad, 'rol' => null];
        }

        return array_values($adaylar);
    }

    private function metindenKurumAdaylariAyikla(string $metin): array
    {
        $adaylar = [];
        $desen = '/\b([A-ZÇĞİÖŞÜ][\pL0-9&.\-]{1,}(?:\s+[A-ZÇĞİÖŞÜ][\pL0-9&.\-]{1,}){0,6}\s+(?:Üniversitesi|Belediyesi|Bakanlığı|Müdürlüğü|Derneği|Vakfı|Holding|A\.Ş\.|AŞ|Ltd\.\s*Şti\.|Genel\s+Müdürlüğü))\b/u';

        preg_match_all($desen, $metin, $eslesmeler);
        foreach ($eslesmeler[1] ?? [] as $ad) {
            $ad = trim((string) $ad);
            if (! filled($ad)) {
                continue;
            }

            $anahtar = mb_strtolower($ad);
            $adaylar[$anahtar] = ['ad' => $ad];
        }

        return array_values($adaylar);
    }

    private function kisiAdayiGecerliMi(string $adSoyad): bool
    {
        $adSoyad = trim($adSoyad);
        if (! filled($adSoyad) || mb_substr_count($adSoyad, ' ') < 1) {
            return false;
        }

        if (mb_strlen($adSoyad) < 5 || mb_strlen($adSoyad) > 80) {
            return false;
        }

        if (preg_match('/\d/u', $adSoyad)) {
            return false;
        }

        $yasakliIfadeler = [
            'açılış', 'konuşma', 'konuşmaları', 'kampüsü', 'kampüsünde', 'sahur', 'ikramı',
            'geleneksel', 'tanınmış', 'programı', 'toplantısı', 'töreni', 'etkinliği', 'haber',
        ];

        $kucuk = mb_strtolower($adSoyad);
        foreach ($yasakliIfadeler as $ifade) {
            if (str_contains($kucuk, $ifade)) {
                return false;
            }
        }

        return true;
    }

    private function kurumAdayiGecerliMi(string $ad): bool
    {
        $ad = trim($ad);
        if (! filled($ad) || mb_strlen($ad) < 3 || mb_strlen($ad) > 255) {
            return false;
        }

        $kurumIpuclari = [
            'derne', 'vakf', 'belediye', 'müdürlüğ', 'bakanlığ', 'üniversite', 'okul', 'kurs',
            'camii', 'cami', 'a.ş', 'aş', 'ltd', 'genel müdürlüğ', 'müftül',
        ];

        $kucuk = mb_strtolower($ad);
        foreach ($kurumIpuclari as $ipucu) {
            if (str_contains($kucuk, $ipucu)) {
                return true;
            }
        }

        return false;
    }
}
