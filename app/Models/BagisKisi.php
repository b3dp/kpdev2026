<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagisKisi extends Model
{
    use HasFactory;

    protected $table = 'bagis_kisiler';

    public $timestamps = false;

    protected $fillable = [
        'bagis_id',
        'kalem_id',
        'uye_id',
        'tip',
        'ad_soyad',
        'tc_kimlik',
        'telefon',
        'eposta',
        'hisse_no',
        'vekalet_ad_soyad',
        'vekalet_tc',
        'vekalet_telefon',
    ];

    protected function casts(): array
    {
        return [
            'tip' => 'array',
        ];
    }

    public function bagis(): BelongsTo
    {
        return $this->belongsTo(Bagis::class, 'bagis_id');
    }

    public function kalem(): BelongsTo
    {
        return $this->belongsTo(BagisKalemi::class, 'kalem_id');
    }

    public function uye(): BelongsTo
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }

    public function tipListesi(): array
    {
        return is_array($this->tip) ? $this->tip : [];
    }
}
