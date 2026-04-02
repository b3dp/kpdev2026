<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpostaGonderim extends Model
{
    protected $table = 'eposta_gonderimleri';

    public $timestamps = false;

    protected $fillable = [
        'sablon_kodu',
        'alici_eposta',
        'alici_ad',
        'konu',
        'html_icerik',
        'durum',
        'zeptomail_message_id',
        'hata_mesaji',
        'ilgili_tip',
        'ilgili_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function sablon()
    {
        return $this->belongsTo(EpostaSablon::class, 'sablon_kodu', 'kod');
    }
}
