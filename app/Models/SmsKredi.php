<?php

namespace App\Models;

use App\Services\HermesService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsKredi extends Model
{
    private const KREDI_UYARI_CACHE_KEY = 'sms_kredi_uyari_gonderildi';

    protected $table = 'sms_kredileri';

    protected $fillable = [
        'kalan_kredi',
        'notlar',
    ];

    protected $casts = [
        'kalan_kredi' => 'integer',
    ];

    /**
     * Mevcut kredi getir
     */
    public static function getKalanKredi(): int
    {
        return static::first()?->kalan_kredi ?? 0;
    }

    /**
     * Kredi ekle
     */
    public static function krediEkle(int $miktar, string $neden = ''): void
    {
        $kredi = static::first() ?? static::create(['kalan_kredi' => 0]);
        $yeniKredi = $kredi->kalan_kredi + $miktar;

        $kredi->update([
            'kalan_kredi' => $yeniKredi,
            'notlar' => $kredi->notlar . "\n" . now()->format('d.m.Y H:i') . " | +$miktar Kredi Eklendi" . ($neden ? " ($neden)" : ''),
        ]);

        $esik = (int) config('services.iletisim_makinesi.kredi_uyari_esik', 145690);
        if ($yeniKredi > $esik) {
            Cache::forget(self::KREDI_UYARI_CACHE_KEY);
        }
    }

    /**
     * Kredi düş
     */
    public static function krediDus(int $miktar, string $neden = ''): bool
    {
        $kredi = static::first();
        if (!$kredi || $kredi->kalan_kredi < $miktar) {
            return false;
        }

        $eskiKredi = (int) $kredi->kalan_kredi;
        $yeniKredi = $eskiKredi - $miktar;

        $kredi->update([
            'kalan_kredi' => $yeniKredi,
            'notlar' => $kredi->notlar . "\n" . now()->format('d.m.Y H:i') . " | -$miktar Kredi Kullanıldı" . ($neden ? " ($neden)" : ''),
        ]);

        static::krediEsikUyarisiniGonder($eskiKredi, $yeniKredi);

        return true;
    }

    private static function krediEsikUyarisiniGonder(int $eskiKredi, int $yeniKredi): void
    {
        $esik = (int) config('services.iletisim_makinesi.kredi_uyari_esik', 145690);
        $uyariGonderildi = (bool) Cache::get(self::KREDI_UYARI_CACHE_KEY, false);

        if ($uyariGonderildi || ! ($eskiKredi > $esik && $yeniKredi <= $esik)) {
            return;
        }

        $telefonlar = config('services.iletisim_makinesi.kredi_uyari_telefonlar', []);
        $telefonlar = array_values(array_filter(array_map('strval', is_array($telefonlar) ? $telefonlar : [])));
        $mesaj = (string) config('services.iletisim_makinesi.kredi_uyari_mesaj', 'SMS Krediniz Tukenmek Uzere. Lutfen SMS Yuklemesi yapin.');

        if ($telefonlar === [] || $mesaj === '') {
            Log::warning('[SmsKredi] Kredi uyarı SMS atlandı: telefon veya mesaj tanımsız.', [
                'esik' => $esik,
                'eski_kredi' => $eskiKredi,
                'yeni_kredi' => $yeniKredi,
            ]);

            return;
        }

        try {
            $sonuc = app(HermesService::class)->sendSMS($telefonlar, $mesaj);

            if (($sonuc['basarili'] ?? false) === true) {
                Cache::put(self::KREDI_UYARI_CACHE_KEY, true, now()->addDays(30));

                Log::info('[SmsKredi] Kredi eşik uyarı SMS gönderildi.', [
                    'esik' => $esik,
                    'yeni_kredi' => $yeniKredi,
                    'telefon_sayisi' => count($telefonlar),
                    'transaction_id' => $sonuc['transaction_id'] ?? null,
                ]);

                return;
            }

            Log::error('[SmsKredi] Kredi eşik uyarı SMS gönderilemedi.', [
                'esik' => $esik,
                'yeni_kredi' => $yeniKredi,
                'sonuc' => $sonuc,
            ]);
        } catch (Throwable $exception) {
            Log::error('[SmsKredi] Kredi eşik uyarı SMS hatası.', [
                'esik' => $esik,
                'yeni_kredi' => $yeniKredi,
                'hata' => $exception->getMessage(),
            ]);
        }
    }
}
