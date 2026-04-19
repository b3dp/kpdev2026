<?php

namespace App\Services;

use App\Enums\BagisOzelligi;
use App\Enums\KurbanBildirimDurumu;
use App\Enums\KurbanBildirimKanali;
use App\Enums\KurbanBildirimSonucu;
use App\Enums\KurbanDurumu;
use App\Jobs\KurbanBildirimJob;
use App\Jobs\ModulRolBildirimJob;
use App\Models\Bagis;
use App\Models\BagisKalemi;
use App\Models\BagisKisi;
use App\Models\KurbanKayit;
use App\Models\KurbanKisi;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class KurbanService
{
    public function bagisAktar(Bagis $bagis): int
    {
        try {
            $bagis->loadMissing(['kalemler.bagisTuru', 'kisiler']);

            $aktarilan = 0;

            foreach ($bagis->kalemler as $kalem) {
                if (! $this->kalemAktarilabilirMi($kalem)) {
                    continue;
                }

                if ($kalem->kurban_id) {
                    continue;
                }

                $aktarimKisileri = $this->aktarilacakKisileriGetir($bagis, $kalem);

                if ($aktarimKisileri->isEmpty()) {
                    Log::warning('Kurban aktarımı için uygun kişi bulunamadı.', [
                        'bagis_id' => $bagis->id,
                        'kalem_id' => $kalem->id,
                    ]);

                    continue;
                }

                $kurban = $this->kurbanKaydiOlustur($bagis, $kalem);

                if (! $kurban) {
                    continue;
                }

                foreach ($aktarimKisileri as $kisi) {
                    $kurban->kisiler()->create([
                        'bagis_kisi_id' => $kisi->id,
                        'tip' => $kisi->tipListesi(),
                        'ad_soyad' => $kisi->ad_soyad,
                        'tc_kimlik' => $kisi->tc_kimlik,
                        'telefon' => $kisi->telefon,
                        'eposta' => $kisi->eposta,
                        'hisse_no' => $kisi->hisse_no,
                        'vekalet_ad_soyad' => $kisi->vekalet_ad_soyad,
                        'vekalet_tc' => $kisi->vekalet_tc,
                        'vekalet_telefon' => $kisi->vekalet_telefon,
                    ]);
                }

                $kalem->update([
                    'kurban_id' => $kurban->id,
                ]);

                ModulRolBildirimJob::dispatch('kurban', $kurban->id)->onQueue('default');

                $aktarilan++;
            }

            $kurbanKalemleri = $bagis->kalemler
                ->filter(fn (BagisKalemi $kalem) => $this->kalemAktarilabilirMi($kalem))
                ->values();

            $tumKurbanKalemleriAktarildi = $kurbanKalemleri->isNotEmpty()
                && $kurbanKalemleri->every(fn (BagisKalemi $kalem) => filled($kalem->fresh()->kurban_id));

            if ($tumKurbanKalemleriAktarildi) {
                $bagis->update([
                    'kurban_aktarildi' => true,
                ]);
            } else {
                $bagis->update([
                    'kurban_aktarildi' => false,
                ]);
            }

            return $aktarilan;
        } catch (Throwable $exception) {
            Log::error('Bağıştan kurban aktarımı başarısız.', [
                'bagis_id' => $bagis->id,
                'hata' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    public function kesildiOlarakIsaretle(KurbanKayit $kurban, array $veri = []): bool
    {
        try {
            if (($kurban->durum?->value ?? $kurban->durum) === KurbanDurumu::Kesildi->value) {
                return true;
            }

            $kurban->update([
                'durum' => KurbanDurumu::Kesildi->value,
                'kesim_tarihi' => now(),
                'kesim_yeri' => filled($veri['kesim_yeri'] ?? null) ? trim((string) $veri['kesim_yeri']) : $kurban->kesim_yeri,
                'kesim_gorevlisi' => filled($veri['kesim_gorevlisi'] ?? null) ? trim((string) $veri['kesim_gorevlisi']) : $kurban->kesim_gorevlisi,
                'not' => array_key_exists('not', $veri)
                    ? (filled($veri['not']) ? trim((string) $veri['not']) : null)
                    : $kurban->not,
            ]);

            KurbanBildirimJob::dispatch($kurban->id)->onQueue('default');

            return true;
        } catch (Throwable $exception) {
            Log::error('Kurban kesildi olarak işaretlenemedi.', [
                'kurban_id' => $kurban->id,
                'hata' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function bildirimDurumunuGuncelle(KurbanKayit $kurban): void
    {
        try {
            $kurban->loadMissing('bildirimler');

            if ($kurban->bildirimler->isEmpty()) {
                $kurban->update([
                    'bildirim_durumu' => KurbanBildirimDurumu::Gonderilmedi->value,
                ]);

                return;
            }

            $basarili = $kurban->bildirimler->where('durum', KurbanBildirimSonucu::Gonderildi)->count();
            $toplam = $kurban->bildirimler->count();

            $durum = match (true) {
                $basarili === 0 => KurbanBildirimDurumu::Gonderilmedi,
                $basarili === $toplam => KurbanBildirimDurumu::Tamamlandi,
                default => KurbanBildirimDurumu::Kismi,
            };

            $kurban->update([
                'bildirim_durumu' => $durum->value,
            ]);
        } catch (Throwable $exception) {
            Log::error('Kurban bildirim durumu güncellenemedi.', [
                'kurban_id' => $kurban->id,
                'hata' => $exception->getMessage(),
            ]);
        }
    }

    public function bildirimMetniOlustur(KurbanKayit $kurban, KurbanKisi $kisi): string
    {
        try {
            $bagisTuru = $kurban->bagis_turu_adi;

            return trim("Sayın {$kisi->ad_soyad}, {$bagisTuru} kaydınız kesildi. Kurban No: {$kurban->kurban_no}. Allah kabul etsin.");
        } catch (Throwable $exception) {
            Log::error('Kurban SMS metni oluşturulamadı.', [
                'kurban_id' => $kurban->id,
                'kurban_kisi_id' => $kisi->id,
                'hata' => $exception->getMessage(),
            ]);

            return 'Kurban kaydınız kesildi. Allah kabul etsin.';
        }
    }

    public function whatsappLinkiOlustur(?string $telefon, string $mesaj = ''): ?string
    {
        try {
            if (blank($telefon)) {
                return null;
            }

            $numara = preg_replace('/\D+/', '', (string) $telefon) ?: '';

            if (str_starts_with($numara, '0090')) {
                $numara = substr($numara, 4);
            } elseif (str_starts_with($numara, '90')) {
                $numara = substr($numara, 2);
            }

            if (str_starts_with($numara, '0')) {
                $numara = substr($numara, 1);
            }

            if ($numara === '') {
                return null;
            }

            $url = 'https://wa.me/90'.$numara;

            return $mesaj !== '' ? $url.'?text='.urlencode($mesaj) : $url;
        } catch (Throwable $exception) {
            Log::error('Kurban WhatsApp linki oluşturulamadı.', [
                'telefon' => $telefon,
                'hata' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function aktarilacakKisileriGetir(Bagis $bagis, BagisKalemi $kalem): Collection
    {
        try {
            $ozellik = $kalem->bagisTuru?->ozellik?->value ?? (string) $kalem->bagisTuru?->ozellik;
            $kalemKisileri = $bagis->kisiler->where('kalem_id', $kalem->id)->values();

            if ($ozellik === BagisOzelligi::BuyukbasKurban->value) {
                return $kalemKisileri
                    ->filter(fn (BagisKisi $kisi) => in_array('hissedar', $kisi->tipListesi(), true))
                    ->sortBy('hisse_no')
                    ->values();
            }

            $sahip = $kalemKisileri->first(fn (BagisKisi $kisi) => in_array('sahip', $kisi->tipListesi(), true));

            if ($sahip) {
                return collect([$sahip]);
            }

            $odeyen = $kalemKisileri->first(fn (BagisKisi $kisi) => in_array('odeyen', $kisi->tipListesi(), true))
                ?? $bagis->kisiler->first(fn (BagisKisi $kisi) => in_array('odeyen', $kisi->tipListesi(), true));

            return $odeyen ? collect([$odeyen]) : collect();
        } catch (Throwable $exception) {
            Log::error('Aktarılacak kurban kişileri alınamadı.', [
                'bagis_id' => $bagis->id,
                'kalem_id' => $kalem->id,
                'hata' => $exception->getMessage(),
            ]);

            return collect();
        }
    }

    private function kalemAktarilabilirMi(BagisKalemi $kalem): bool
    {
        $ozellik = $kalem->bagisTuru?->ozellik?->value ?? (string) $kalem->bagisTuru?->ozellik;

        return in_array($ozellik, [BagisOzelligi::KucukbasKurban->value, BagisOzelligi::BuyukbasKurban->value], true);
    }

    private function kurbanKaydiOlustur(Bagis $bagis, BagisKalemi $kalem): ?KurbanKayit
    {
        try {
            for ($deneme = 0; $deneme < 5; $deneme++) {
                try {
                    return KurbanKayit::create([
                        'kurban_no' => KurbanKayit::kurbanNoUret($deneme),
                        'bagis_id' => $bagis->id,
                        'bagis_kalem_id' => $kalem->id,
                        'bagis_turu_adi' => (string) ($kalem->bagisTuru?->ad ?? 'Kurban Bağışı'),
                        'bagis_ozelligi' => $kalem->bagisTuru?->ozellik?->value ?? (string) $kalem->bagisTuru?->ozellik,
                        'durum' => KurbanDurumu::Bekliyor->value,
                        'hisse_sayisi' => (($kalem->bagisTuru?->ozellik?->value ?? $kalem->bagisTuru?->ozellik) === BagisOzelligi::BuyukbasKurban->value)
                            ? (int) $kalem->adet
                            : null,
                        'bildirim_durumu' => KurbanBildirimDurumu::Gonderilmedi->value,
                    ]);
                } catch (QueryException $exception) {
                    if ($deneme === 4) {
                        throw $exception;
                    }
                }
            }

            return null;
        } catch (Throwable $exception) {
            Log::error('Kurban kaydı oluşturulamadı.', [
                'bagis_id' => $bagis->id,
                'kalem_id' => $kalem->id,
                'hata' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}