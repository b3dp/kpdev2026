<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedDevice extends Model
{
    use HasFactory;

    protected $table = 'trusted_devices';

    protected $fillable = [
        'uye_id',
        'device_token',
        'device_adi',
        'ip_adresi',
        'son_kullanim',
        'gecerlilik_tarihi',
    ];

    protected function casts(): array
    {
        return [
            'son_kullanim' => 'datetime',
            'gecerlilik_tarihi' => 'datetime',
        ];
    }

    public function uye(): BelongsTo
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }
}