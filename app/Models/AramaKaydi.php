<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AramaKaydi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arama_kayitlari';

    protected $fillable = [
        'aranan_ifade',
        'arama_sayisi',
        'son_aranma_at',
    ];

    protected function casts(): array
    {
        return [
            'arama_sayisi' => 'integer',
            'son_aranma_at' => 'datetime',
        ];
    }
}
