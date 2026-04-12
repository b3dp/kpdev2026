<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HaberKisi extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'haber_kisiler';

    protected $fillable = [
        'haber_id',
        'kisi_id',
        'rol',
        'onay_durumu',
    ];

    public function haber(): BelongsTo
    {
        return $this->belongsTo(Haber::class, 'haber_id');
    }

    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id')->withTrashed();
    }
}
