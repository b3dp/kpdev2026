<?php

namespace App\Services;

use App\Models\Uye;
use Illuminate\Support\Facades\Log;
use Throwable;

class EkayitUyeKayitService
{
    public function veliTelefonlariniKaydet(array $veri): ?Uye
    {
        try {
            $birincilUye = $this->tekUyeyiKaydet(
                telefon: $veri['veli_telefon'] ?? null,
                adSoyad: (string) ($veri['veli_ad_soyad'] ?? 'Veli'),
                eposta: $veri['veli_eposta'] ?? null,
            );

            if (filled($veri['veli_telefon_2'] ?? null)) {
                $this->tekUyeyiKaydet(
                    telefon: $veri['veli_telefon_2'] ?? null,
                    adSoyad: (string) ($veri['veli_ad_soyad'] ?? 'Veli'),
                    eposta: null,
                );
            }

            return $birincilUye;
        } catch (Throwable $e) {
            Log::error('EkayitUyeKayitService@veliTelefonlariniKaydet hatasi', [
                'telefon_1' => $veri['veli_telefon'] ?? null,
                'telefon_2' => $veri['veli_telefon_2'] ?? null,
                'eposta' => $veri['veli_eposta'] ?? null,
                'mesaj' => $e->getMessage(),
                'dosya' => $e->getFile(),
                'satir' => $e->getLine(),
            ]);

            return null;
        }
    }

    private function tekUyeyiKaydet(?string $telefon, string $adSoyad, ?string $eposta = null): ?Uye
    {
        try {
            $telefon = $this->telefonuTemizle($telefon);
            $eposta = filled($eposta) ? mb_strtolower(trim((string) $eposta), 'UTF-8') : null;

            if (! $telefon) {
                return null;
            }

            $uye = Uye::query()
                ->where('telefon', $telefon)
                ->when($eposta, fn ($query) => $query->orWhere('eposta', $eposta))
                ->first();

            if (! $uye) {
                $uye = Uye::query()->create([
                    'ad_soyad' => $adSoyad,
                    'telefon' => $telefon,
                    'eposta' => $eposta,
                    'durum' => 'aktif',
                    'aktif' => true,
                    'telefon_dogrulandi' => false,
                    'eposta_dogrulandi' => false,
                    'sms_abonelik' => true,
                    'eposta_abonelik' => true,
                ]);
            } else {
                $guncellenecekAlanlar = [];

                if (blank($uye->ad_soyad) && filled($adSoyad)) {
                    $guncellenecekAlanlar['ad_soyad'] = $adSoyad;
                }

                if (blank($uye->telefon) && filled($telefon)) {
                    $guncellenecekAlanlar['telefon'] = $telefon;
                }

                if (blank($uye->eposta) && filled($eposta)) {
                    $guncellenecekAlanlar['eposta'] = $eposta;
                }

                if ($guncellenecekAlanlar !== []) {
                    $uye->update($guncellenecekAlanlar);
                }
            }

            app(KisiEslestirmeService::class)->uyeEslestir($uye);

            return $uye;
        } catch (Throwable $e) {
            Log::error('EkayitUyeKayitService@tekUyeyiKaydet hatasi', [
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

    private function telefonuTemizle(?string $telefon): ?string
    {
        $temizTelefon = preg_replace('/\D+/', '', (string) $telefon) ?: '';

        if ($temizTelefon === '') {
            return null;
        }

        if (str_starts_with($temizTelefon, '0090')) {
            $temizTelefon = substr($temizTelefon, 4);
        } elseif (str_starts_with($temizTelefon, '90')) {
            $temizTelefon = substr($temizTelefon, 2);
        }

        if ($temizTelefon === '') {
            return null;
        }

        return str_starts_with($temizTelefon, '0') ? $temizTelefon : ('0'.$temizTelefon);
    }
}
