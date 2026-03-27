<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkayitKimlikBilgisi extends Model
{
    public $timestamps = false;

    protected $table = 'ekayit_kimlik_bilgileri';

    protected $fillable = [
        'kayit_id', 'kayitli_il', 'kayitli_ilce', 'kayitli_mahalle_koy',
        'cilt_no', 'aile_sira_no', 'sira_no', 'cuzdanin_verildigi_yer',
        'kimlik_seri_no', 'kan_grubu',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function kayit(): BelongsTo
    {
        return $this->belongsTo(EkayitKayit::class, 'kayit_id');
    }
}
