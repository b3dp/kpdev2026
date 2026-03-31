<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsGonderim extends Model
{
    protected $table = 'sms_gonderimler';

    protected $fillable = [
        'yonetici_id',
        'tip',
        'mesaj',
        'liste_idler',
        'alici_sayisi',
        'basarili',
        'basarisiz',
        'bekleyen',
        'durum',
        'hermes_transaction_id',
        'hermes_async_req_id',
        'planli_tarih',
    ];

    protected function casts(): array
    {
        return [
            'liste_idler' => 'array',
            'planli_tarih' => 'datetime',
        ];
    }

    public function alicilar(): HasMany
    {
        return $this->hasMany(SmsGonderimAlici::class, 'gonderim_id');
    }

    public function yonetici(): BelongsTo
    {
        return $this->belongsTo(Yonetici::class, 'yonetici_id');
    }
}
