<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsGonderimAlici extends Model
{
    protected $table = 'sms_gonderim_alicilari';

    public const UPDATED_AT = null;

    protected $fillable = [
        'gonderim_id',
        'telefon',
        'durum',
        'hermes_packet_id',
        'hata_kodu',
        'created_at',
    ];

    public function gonderim(): BelongsTo
    {
        return $this->belongsTo(SmsGonderim::class, 'gonderim_id');
    }
}
