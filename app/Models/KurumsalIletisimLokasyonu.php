<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KurumsalIletisimLokasyonu extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'kurumsal_iletisim_lokasyonlari';

    protected $fillable = [
        'sayfa_id',
        'lokasyon_adi',
        'adres',
        'eposta',
        'konum_lat',
        'konum_lng',
        'konum_place_id',
        'sira',
    ];

    protected function casts(): array
    {
        return [
            'konum_lat' => 'decimal:7',
            'konum_lng' => 'decimal:7',
            'sira' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'sayfa_id',
                'lokasyon_adi',
                'adres',
                'eposta',
                'konum_lat',
                'konum_lng',
                'konum_place_id',
                'sira',
            ])
            ->logOnlyDirty()
            ->useLogName('kurumsal_sayfa')
            ->dontSubmitEmptyLogs();
    }

    public function sayfa(): BelongsTo
    {
        return $this->belongsTo(KurumsalSayfa::class, 'sayfa_id');
    }
}
