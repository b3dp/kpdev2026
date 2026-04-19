<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KurbanKisi extends Model
{
    use HasFactory;

    protected $table = 'kurban_kisiler';

    protected $fillable = [
        'kurban_id',
        'bagis_kisi_id',
        'tip',
        'ad_soyad',
        'tc_kimlik',
        'telefon',
        'eposta',
        'hisse_no',
        'vekalet_ad_soyad',
        'vekalet_tc',
        'vekalet_telefon',
    ];

    protected function casts(): array
    {
        return [
            'tip' => 'array',
        ];
    }

    public function kurban(): BelongsTo
    {
        return $this->belongsTo(KurbanKayit::class, 'kurban_id');
    }

    public function bagisKisi(): BelongsTo
    {
        return $this->belongsTo(BagisKisi::class, 'bagis_kisi_id');
    }

    public function bildirimler(): HasMany
    {
        return $this->hasMany(KurbanBildirim::class, 'kurban_kisi_id');
    }

    public function tipListesi(): array
    {
        return is_array($this->tip) ? $this->tip : [];
    }
}