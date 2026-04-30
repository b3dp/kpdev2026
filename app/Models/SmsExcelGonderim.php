<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsExcelGonderim extends Model
{
    use SoftDeletes;

    protected $table = 'sms_excel_gonderimler';

    protected $fillable = [
        'yonetici_id',
        'dosya',
        'mesaj',
        'durum',
        'toplam_satir',
        'gecerli_satir',
        'mukerrer',
        'hatali_format',
        'bos',
        'alici_sayisi',
        'basarili',
        'basarisiz',
        'bekleyen',
        'hermes_transaction_id',
        'hermes_async_req_id',
        'hata_mesaji',
        'basladi_at',
        'tamamlandi_at',
    ];

    protected function casts(): array
    {
        return [
            'toplam_satir' => 'integer',
            'gecerli_satir' => 'integer',
            'mukerrer' => 'integer',
            'hatali_format' => 'integer',
            'bos' => 'integer',
            'alici_sayisi' => 'integer',
            'basarili' => 'integer',
            'basarisiz' => 'integer',
            'bekleyen' => 'integer',
            'basladi_at' => 'datetime',
            'tamamlandi_at' => 'datetime',
        ];
    }

    public function yonetici(): BelongsTo
    {
        return $this->belongsTo(Yonetici::class, 'yonetici_id');
    }
}
