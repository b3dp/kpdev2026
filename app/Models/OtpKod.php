<?php

namespace App\Models;

use App\Enums\OtpTipi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpKod extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'otp_kodlar';

    protected $fillable = [
        'uye_id',
        'telefon',
        'eposta',
        'kod',
        'tip',
        'kullanildi',
        'gecerlilik_tarihi',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tip' => OtpTipi::class,
            'kullanildi' => 'boolean',
            'gecerlilik_tarihi' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function uye(): BelongsTo
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }
}