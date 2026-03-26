<?php

namespace App\Models;

use App\Enums\RaporPeriyot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BagisOtomatikRapor extends Model
{
    use HasFactory;

    protected $table = 'bagis_otomatik_raporlar';

    protected $fillable = [
        'periyot',
        'alicilar',
        'aktif',
        'son_gonderim',
    ];

    protected function casts(): array
    {
        return [
            'periyot' => RaporPeriyot::class,
            'alicilar' => 'array',
            'aktif' => 'boolean',
            'son_gonderim' => 'datetime',
        ];
    }
}
