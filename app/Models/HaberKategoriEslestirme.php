<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HaberKategoriEslestirme extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'haber_kategori_eslestirmeleri';

    protected $fillable = [
        'haber_id',
        'haber_kategorisi_id',
        'skor',
        'ana_kategori_mi',
        'kaynak',
    ];

    protected function casts(): array
    {
        return [
            'skor' => 'integer',
            'ana_kategori_mi' => 'boolean',
        ];
    }

    public function haber(): BelongsTo
    {
        return $this->belongsTo(Haber::class, 'haber_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(HaberKategorisi::class, 'haber_kategorisi_id');
    }
}