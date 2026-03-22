<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UyeBildirim extends Model
{
    use HasFactory;

    protected $table = 'uye_bildirimler';

    protected $fillable = [
        'uye_id',
        'tip',
        'mesaj',
        'okundu',
    ];

    protected function casts(): array
    {
        return [
            'okundu' => 'boolean',
        ];
    }

    public function uye(): BelongsTo
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }
}
