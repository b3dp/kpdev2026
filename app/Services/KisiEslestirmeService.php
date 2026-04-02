<?php

namespace App\Services;

use App\Models\Bagis;
use App\Models\EkayitKayit;
use App\Models\Kisi;
use App\Models\MezunProfil;
use App\Models\Uye;
use App\Models\UyeRozet;
use Illuminate\Support\Facades\Log;
use Throwable;

class KisiEslestirmeService
{
    public function eslestir(?string $telefon, ?string $eposta, ?string $adSoyad): ?Kisi
    {
        try {
            $telefon = $this->telefonTemizle($telefon);
            $eposta = $this->epostaTemizle($eposta);
            $adSoyad = $this->metinTemizle($adSoyad);

            if ($telefon === null && $eposta === null) {
                return null;
            }

            $kisi = null;

            if ($telefon !== null) {
                $kisi = Kisi::query()->where('telefon', $telefon)->first();
            }

            if (! $kisi && $eposta !== null) {
                $kisi = Kisi::query()->where('eposta', $eposta)->first();
            }

            [$ad, $soyad] = $this->adSoyadParcala($adSoyad);
            $yeniAd = $ad ?? 'Bilinmiyor';
            $yeniSoyad = $soyad ?? 'Bilinmiyor';

            if ($kisi) {
                if (blank($kisi->telefon) && $telefon !== null) {
                    $kisi->telefon = $telefon;
                }

                if (blank($kisi->eposta) && $eposta !== null) {
                    $kisi->eposta = $eposta;
                }

                if (blank($kisi->ad) && $ad !== null) {
                    $kisi->ad = $ad;
                }

                if (blank($kisi->soyad) && $soyad !== null) {
                    $kisi->soyad = $soyad;
                }

                if ($kisi->isDirty()) {
                    $kisi->save();
                }

                return $kisi;
            }

            return Kisi::create([
                'telefon' => $telefon,
                'eposta' => $eposta,
                'ad' => $yeniAd,
                'soyad' => $yeniSoyad,
            ]);
        } catch (Throwable $e) {
            Log::error('KisiEslestirmeService@eslestir hatasi', [
                'telefon' => $telefon,
                'eposta' => $eposta,
                'ad_soyad' => $adSoyad,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);

            return null;
        }
    }

    public function uyeEslestir(Uye $uye): void
    {
        try {
            if ($uye->kisi_id !== null) {
                return;
            }

            $kisi = $this->eslestir($uye->telefon, $uye->eposta, $uye->ad_soyad);

            if (! $kisi) {
                return;
            }

            $uye->update(['kisi_id' => $kisi->id]);
        } catch (Throwable $e) {
            Log::error('KisiEslestirmeService@uyeEslestir hatasi', [
                'uye_id' => $uye->id,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);
        }
    }

    public function rozetEkle(Uye $uye, string $rozetSlug, string $kaynakTip, int $kaynakId): void
    {
        try {
            $mevcut = UyeRozet::query()
                ->where('uye_id', $uye->id)
                ->where('tip', $rozetSlug)
                ->exists();

            if ($mevcut) {
                return;
            }

            UyeRozet::create([
                'uye_id' => $uye->id,
                'tip' => $rozetSlug,
                'kazanilma_tarihi' => now(),
                'kaynak_tip' => $kaynakTip,
                'kaynak_id' => $kaynakId,
            ]);
        } catch (Throwable $e) {
            Log::error('KisiEslestirmeService@rozetEkle hatasi', [
                'uye_id' => $uye->id,
                'rozet' => $rozetSlug,
                'kaynak_tip' => $kaynakTip,
                'kaynak_id' => $kaynakId,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);
        }
    }

    public function bagisEslestir(Bagis $bagis): void
    {
        try {
            $bagis->loadMissing(['kisiler', 'uye']);

            $bagisKisi = $bagis->odeyenKisi() ?? $bagis->sahipKisi() ?? $bagis->kisiler->first();

            if (! $bagisKisi) {
                return;
            }

            $kisi = $this->eslestir($bagisKisi->telefon, $bagisKisi->eposta, $bagisKisi->ad_soyad);

            if (! $kisi) {
                return;
            }

            $bagis->update(['kisi_id' => $kisi->id]);

            if ($bagis->uye) {
                $this->uyeEslestir($bagis->uye);
                $this->rozetEkle($bagis->uye, 'bagisci', 'bagis', (int) $bagis->id);
            }
        } catch (Throwable $e) {
            Log::error('KisiEslestirmeService@bagisEslestir hatasi', [
                'bagis_id' => $bagis->id,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);
        }
    }

    public function ekayitEslestir(EkayitKayit $kayit): void
    {
        try {
            $kayit->loadMissing(['veliBilgisi', 'uye']);

            if (! $kayit->veliBilgisi) {
                return;
            }

            $kisi = $this->eslestir(
                $kayit->veliBilgisi->telefon_1,
                $kayit->veliBilgisi->eposta,
                $kayit->veliBilgisi->ad_soyad
            );

            if (! $kisi) {
                return;
            }

            if (! $kayit->uye) {
                return;
            }

            $this->uyeEslestir($kayit->uye);
            $this->rozetEkle($kayit->uye, 'veli', 'ekayit_kayit', (int) $kayit->id);
        } catch (Throwable $e) {
            Log::error('KisiEslestirmeService@ekayitEslestir hatasi', [
                'ekayit_kayit_id' => $kayit->id,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);
        }
    }

    public function mezunEslestir(MezunProfil $mezun): void
    {
        try {
            $mezun->loadMissing('uye');

            if (! $mezun->uye) {
                return;
            }

            $this->uyeEslestir($mezun->uye);
            $this->rozetEkle($mezun->uye, 'mezun', 'mezun_profil', (int) $mezun->id);
        } catch (Throwable $e) {
            Log::error('KisiEslestirmeService@mezunEslestir hatasi', [
                'mezun_profil_id' => $mezun->id,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);
        }
    }

    private function telefonTemizle(?string $telefon): ?string
    {
        $telefon = preg_replace('/\D+/', '', (string) $telefon);

        return $telefon !== '' ? $telefon : null;
    }

    private function epostaTemizle(?string $eposta): ?string
    {
        $eposta = trim(mb_strtolower((string) $eposta));

        return $eposta !== '' ? $eposta : null;
    }

    private function metinTemizle(?string $metin): ?string
    {
        $metin = trim((string) $metin);

        return $metin !== '' ? $metin : null;
    }

    private function adSoyadParcala(?string $adSoyad): array
    {
        if ($adSoyad === null) {
            return [null, null];
        }

        $parcalar = preg_split('/\s+/', trim($adSoyad)) ?: [];

        if ($parcalar === []) {
            return [null, null];
        }

        if (count($parcalar) === 1) {
            return [$parcalar[0], null];
        }

        $ad = array_shift($parcalar);
        $soyad = trim(implode(' ', $parcalar));

        return [
            $ad !== '' ? $ad : null,
            $soyad !== '' ? $soyad : null,
        ];
    }
}
