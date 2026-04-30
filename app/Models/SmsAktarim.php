<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsAktarim extends Model
{
    protected $table = 'sms_aktarimlar';

    protected $fillable = [
        'yonetici_id',
        'liste_id',
        'dosya',
        'durum',
        'toplam',
        'eklenen',
        'mukerrer_db',
        'mukerrer_excel',
        'hatali_format',
        'bos',
        'hata_mesaji',
        'basladi_at',
        'tamamlandi_at',
    ];

    protected function casts(): array
    {
        return [
            'toplam' => 'integer',
            'eklenen' => 'integer',
            'mukerrer_db' => 'integer',
            'mukerrer_excel' => 'integer',
            'hatali_format' => 'integer',
            'bos' => 'integer',
            'basladi_at' => 'datetime',
            'tamamlandi_at' => 'datetime',
        ];
    }

    public function yonetici(): BelongsTo
    {
        return $this->belongsTo(Yonetici::class, 'yonetici_id');
    }

    public function liste(): BelongsTo
    {
        return $this->belongsTo(SmsListe::class, 'liste_id');
    }
}
