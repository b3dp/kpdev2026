<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsKredi extends Model
{
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
        $kredi->update([
            'kalan_kredi' => $kredi->kalan_kredi + $miktar,
            'notlar' => $kredi->notlar . "\n" . now()->format('d.m.Y H:i') . " | +$miktar Kredi Eklendi" . ($neden ? " ($neden)" : ''),
        ]);
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

        $kredi->update([
            'kalan_kredi' => $kredi->kalan_kredi - $miktar,
            'notlar' => $kredi->notlar . "\n" . now()->format('d.m.Y H:i') . " | -$miktar Kredi Kullanıldı" . ($neden ? " ($neden)" : ''),
        ]);

        return true;
    }
}
