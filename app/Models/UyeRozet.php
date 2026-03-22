<?php

namespace App\Models;

use App\Enums\RozetTipi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UyeRozet extends Model
{
    use HasFactory;

    protected $table = 'uye_rozetler';

    protected $fillable = [
        'uye_id',
        'tip',
        'kazanilma_tarihi',
        'kaynak_tip',
        'kaynak_id',
    ];

    protected function casts(): array
    {
        return [
            'tip' => RozetTipi::class,
            'kazanilma_tarihi' => 'datetime',
        ];
    }

    public function uye(): BelongsTo
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }
}