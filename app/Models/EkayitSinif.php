<?php

namespace App\Models;

use App\Services\SinifRenkService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EkayitSinif extends Model
{
    use LogsActivity, Searchable, SoftDeletes;

    protected $table = 'ekayit_siniflar';

    protected $fillable = [
        'ad', 'ogretim_yili', 'kurum_id', 'donem_id',
        'kurallar', 'aciklama', 'notlar',
        'gorsel_kare', 'gorsel_dikey', 'gorsel_yatay', 'gorsel_orijinal',
        'renk', 'aktif',
    ];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $sinif): void {
            if (blank($sinif->renk) || $sinif->renk === 'blue') {
                $sinif->renk = app(SinifRenkService::class)->sonrakiRenk((int) $sinif->donem_id);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ad', 'ogretim_yili', 'kurum_id', 'donem_id', 'renk', 'aktif'])
            ->logOnlyDirty()->useLogName('ekayit_sinif')->dontSubmitEmptyLogs();
    }

    public function toSearchableArray(): array
    {
        return [
            'id'           => $this->id,
            'ad'           => $this->ad,
            'ogretim_yili' => $this->ogretim_yili,
        ];
    }

    public function donem(): BelongsTo
    {
        return $this->belongsTo(EkayitDonem::class, 'donem_id');
    }

    public function kurum(): BelongsTo
    {
        return $this->belongsTo(Kurum::class, 'kurum_id');
    }

    public function kayitlar(): HasMany
    {
        return $this->hasMany(EkayitKayit::class, 'sinif_id');
    }
}
