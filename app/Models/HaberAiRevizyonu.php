<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HaberAiRevizyonu extends Model
{
    use HasFactory;

    protected $table = 'haber_ai_revizyonlari';

    protected $fillable = [
        'haber_id',
        'olusturan_yonetici_id',
        'islem_tipi',
        'model',
        'orijinal_baslik',
        'duzeltilmis_baslik',
        'orijinal_icerik',
        'duzeltilmis_icerik',
        'orijinal_ozet',
        'duzeltilmis_ozet',
        'orijinal_seo_baslik',
        'duzeltilmis_seo_baslik',
        'orijinal_meta_description',
        'duzeltilmis_meta_description',
        'diff_ozeti_json',
        'uygulandi_mi',
        'geri_alindi_mi',
        'uygulandi_at',
        'geri_alindi_at',
    ];

    protected function casts(): array
    {
        return [
            'diff_ozeti_json' => 'array',
            'uygulandi_mi' => 'boolean',
            'geri_alindi_mi' => 'boolean',
            'uygulandi_at' => 'datetime',
            'geri_alindi_at' => 'datetime',
        ];
    }

    public function haber(): BelongsTo
    {
        return $this->belongsTo(Haber::class, 'haber_id');
    }

    public function olusturanYonetici(): BelongsTo
    {
        return $this->belongsTo(Yonetici::class, 'olusturan_yonetici_id');
    }
}