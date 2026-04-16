<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MezunProfil extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'mezun_profiller';

    protected $fillable = [
        'uye_id',
        'kurum_id',
        'kurum_manuel',
        'mezuniyet_yili',
        'sinif_id',
        'hafiz',
        'meslek',
        'gorev_il',
        'gorev_ilce',
        'ikamet_il',
        'ikamet_ilce',
        'acik_adres',
        'aciklama',
        'linkedin',
        'instagram',
        'twitter',
        'durum',
        'onaylayan_id',
        'onay_tarihi',
        'red_notu',
    ];

    protected function casts(): array
    {
        return [
            'hafiz' => 'boolean',
            'onay_tarihi' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'kurum_id',
                'kurum_manuel',
                'mezuniyet_yili',
                'hafiz',
                'meslek',
                'gorev_il',
                'gorev_ilce',
                'ikamet_il',
                'ikamet_ilce',
                'acik_adres',
                'aciklama',
                'linkedin',
                'instagram',
                'twitter',
                'durum',
                'onaylayan_id',
                'red_notu',
            ])
            ->logOnlyDirty()
            ->useLogName('mezunlar')
            ->dontSubmitEmptyLogs();
    }

    public function uye(): BelongsTo
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }

    public function kurum(): BelongsTo
    {
        return $this->belongsTo(Kurum::class, 'kurum_id');
    }

    public function onaylayan(): BelongsTo
    {
        return $this->belongsTo(Yonetici::class, 'onaylayan_id');
    }

    public function sinif(): BelongsTo
    {
        return $this->belongsTo(EkayitSinif::class, 'sinif_id');
    }
}
