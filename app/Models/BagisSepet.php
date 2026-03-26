<?php

namespace App\Models;

use App\Enums\SepetDurumu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;

class BagisSepet extends Model
{
    use HasFactory;

    protected $table = 'bagis_sepetler';

    protected $fillable = [
        'uye_id',
        'session_id',
        'durum',
        'toplam_tutar',
    ];

    protected function casts(): array
    {
        return [
            'durum' => SepetDurumu::class,
            'toplam_tutar' => 'decimal:2',
        ];
    }

    public function satirlar(): HasMany
    {
        return $this->hasMany(BagisSepetSatir::class, 'sepet_id');
    }

    public function bagis(): HasOne
    {
        return $this->hasOne(Bagis::class, 'sepet_id');
    }

    public function uye(): BelongsTo
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }

    public function toplamHesapla(): void
    {
        $this->toplam_tutar = (float) $this->satirlar()->sum('toplam');
        $this->save();
    }

    public static function aktifSepet(Request $request): self
    {
        $uye = $request->user('uye');

        if ($uye) {
            return static::query()->firstOrCreate(
                ['uye_id' => $uye->id, 'durum' => SepetDurumu::Aktif->value],
                ['session_id' => $request->session()->getId()]
            );
        }

        return static::query()->firstOrCreate(
            ['session_id' => $request->session()->getId(), 'durum' => SepetDurumu::Aktif->value]
        );
    }
}
