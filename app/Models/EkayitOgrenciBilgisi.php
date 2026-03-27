<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkayitOgrenciBilgisi extends Model
{
    public $timestamps = false;

    protected $table = 'ekayit_ogrenci_bilgileri';

    protected $fillable = [
        'kayit_id', 'ad_soyad', 'tc_kimlik', 'dogum_yeri',
        'dogum_tarihi', 'baba_adi', 'anne_adi', 'adres', 'ikamet_il',
    ];

    protected function casts(): array
    {
        return ['dogum_tarihi' => 'date', 'created_at' => 'datetime'];
    }

    public function kayit(): BelongsTo
    {
        return $this->belongsTo(EkayitKayit::class, 'kayit_id');
    }
}
