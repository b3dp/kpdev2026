<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HaberKurum extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'haber_kurumlar';

    protected $fillable = [
        'haber_id',
        'kurum_id',
        'onay_durumu',
    ];

    public function haber(): BelongsTo
    {
        return $this->belongsTo(Haber::class, 'haber_id');
    }

    public function kurum(): BelongsTo
    {
        return $this->belongsTo(Kurum::class, 'kurum_id');
    }
}
