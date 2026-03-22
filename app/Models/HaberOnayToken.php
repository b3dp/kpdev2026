<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HaberOnayToken extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'haber_onay_tokenlar';

    protected $fillable = [
        'haber_id',
        'token',
        'tip',
        'kullanildi',
        'gecerlilik_tarihi',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'kullanildi' => 'boolean',
            'gecerlilik_tarihi' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function haber(): BelongsTo
    {
        return $this->belongsTo(Haber::class, 'haber_id');
    }
}
