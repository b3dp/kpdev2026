<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagisSepetSatir extends Model
{
    use HasFactory;

    protected $table = 'bagis_sepet_satirlar';

    public $timestamps = false;

    protected $fillable = [
        'sepet_id',
        'bagis_turu_id',
        'adet',
        'birim_fiyat',
        'toplam',
        'sahip_tipi',
        'vekalet_onay',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'birim_fiyat' => 'decimal:2',
            'toplam' => 'decimal:2',
            'vekalet_onay' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function sepet(): BelongsTo
    {
        return $this->belongsTo(BagisSepet::class, 'sepet_id');
    }

    public function bagisTuru(): BelongsTo
    {
        return $this->belongsTo(BagisTuru::class, 'bagis_turu_id');
    }
}
