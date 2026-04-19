<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BagisKalemi extends Model
{
    use HasFactory;

    protected $table = 'bagis_kalemleri';

    public $timestamps = false;

    protected $fillable = [
        'bagis_id',
        'bagis_turu_id',
        'adet',
        'birim_fiyat',
        'toplam',
        'sahip_tipi',
        'vekalet_onay',
        'kurban_id',
    ];

    protected function casts(): array
    {
        return [
            'birim_fiyat' => 'decimal:2',
            'toplam' => 'decimal:2',
            'vekalet_onay' => 'boolean',
        ];
    }

    public function bagis(): BelongsTo
    {
        return $this->belongsTo(Bagis::class, 'bagis_id');
    }

    public function bagisTuru(): BelongsTo
    {
        return $this->belongsTo(BagisTuru::class, 'bagis_turu_id');
    }

    public function kisiler(): HasMany
    {
        return $this->hasMany(BagisKisi::class, 'kalem_id');
    }

    public function kurban(): BelongsTo
    {
        return $this->belongsTo(KurbanKayit::class, 'kurban_id');
    }
}
