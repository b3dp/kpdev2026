<?php

namespace App\Models;

use App\Enums\EtkinlikKatilimDurumu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtkinlikKatilimi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'etkinlik_katilimlari';

    protected $fillable = [
        'etkinlik_id',
        'uye_id',
        'durum',
    ];

    protected function casts(): array
    {
        return [
            'durum' => EtkinlikKatilimDurumu::class,
        ];
    }

    public function etkinlik(): BelongsTo
    {
        return $this->belongsTo(Etkinlik::class, 'etkinlik_id');
    }

    public function uye(): BelongsTo
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }
}
