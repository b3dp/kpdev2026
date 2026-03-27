<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkayitVeliBilgisi extends Model
{
    public $timestamps = false;

    protected $table = 'ekayit_veli_bilgileri';

    protected $fillable = ['kayit_id', 'ad_soyad', 'eposta', 'telefon_1', 'telefon_2'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function kayit(): BelongsTo
    {
        return $this->belongsTo(EkayitKayit::class, 'kayit_id');
    }
}
