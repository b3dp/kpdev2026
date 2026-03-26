<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdemeHatasi extends Model
{
    use HasFactory;

    protected $table = 'odeme_hatalari';

    public $timestamps = false;

    protected $fillable = [
        'bagis_id',
        'saglayici',
        'hata_kodu',
        'hata_mesaji',
        'kart_son_haneler',
        'banka_adi',
        'tutar',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tutar' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function bagis(): BelongsTo
    {
        return $this->belongsTo(Bagis::class, 'bagis_id');
    }
}
