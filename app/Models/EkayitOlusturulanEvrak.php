<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkayitOlusturulanEvrak extends Model
{
    public $timestamps = false;

    protected $table = 'ekayit_olusturulan_evraklar';

    protected $fillable = ['kayit_id', 'sablon_id', 'dosya_yol', 'olusturulma_tarihi'];

    protected function casts(): array
    {
        return [
            'olusturulma_tarihi' => 'datetime',
            'created_at'         => 'datetime',
        ];
    }

    public function kayit(): BelongsTo
    {
        return $this->belongsTo(EkayitKayit::class, 'kayit_id');
    }

    public function sablon(): BelongsTo
    {
        return $this->belongsTo(EkayitEvrakSablonu::class, 'sablon_id');
    }
}
