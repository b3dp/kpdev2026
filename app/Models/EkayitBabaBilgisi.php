<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkayitBabaBilgisi extends Model
{
    public $timestamps = false;

    protected $table = 'ekayit_baba_bilgileri';

    protected $fillable = ['kayit_id', 'dogum_yeri', 'nufus_il_ilce'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function kayit(): BelongsTo
    {
        return $this->belongsTo(EkayitKayit::class, 'kayit_id');
    }
}
