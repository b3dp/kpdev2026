<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkayitOkulBilgisi extends Model
{
    public $timestamps = false;

    protected $table = 'ekayit_okul_bilgileri';

    protected $fillable = ['kayit_id', 'okul_adi', 'okul_numarasi', 'sube', 'not'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function kayit(): BelongsTo
    {
        return $this->belongsTo(EkayitKayit::class, 'kayit_id');
    }
}
