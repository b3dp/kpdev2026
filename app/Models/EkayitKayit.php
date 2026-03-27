<?php

namespace App\Models;

use App\Enums\EkayitDurumu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EkayitKayit extends Model
{
    use LogsActivity, Searchable, SoftDeletes;

    protected $table = 'ekayit_kayitlar';

    protected $fillable = [
        'sinif_id', 'uye_id', 'durum', 'durum_notu',
        'yonetici_id', 'durum_tarihi', 'yedek_sira', 'genel_not',
    ];

    protected function casts(): array
    {
        return [
            'durum'        => EkayitDurumu::class,
            'durum_tarihi' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['sinif_id', 'uye_id', 'durum', 'durum_notu', 'yonetici_id', 'yedek_sira'])
            ->logOnlyDirty()->useLogName('ekayit_kayit')->dontSubmitEmptyLogs();
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['ogrenciBilgisi', 'veliBilgisi']);

        return [
            'id'        => $this->id,
            'ad_soyad'  => $this->ogrenciBilgisi?->ad_soyad ?? '',
            'telefon_1' => $this->veliBilgisi?->telefon_1 ?? '',
        ];
    }

    public function sinif(): BelongsTo
    {
        return $this->belongsTo(EkayitSinif::class, 'sinif_id');
    }

    public function uye(): BelongsTo
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }

    public function yonetici(): BelongsTo
    {
        return $this->belongsTo(Yonetici::class, 'yonetici_id');
    }

    public function ogrenciBilgisi(): HasOne
    {
        return $this->hasOne(EkayitOgrenciBilgisi::class, 'kayit_id');
    }

    public function kimlikBilgisi(): HasOne
    {
        return $this->hasOne(EkayitKimlikBilgisi::class, 'kayit_id');
    }

    public function okulBilgisi(): HasOne
    {
        return $this->hasOne(EkayitOkulBilgisi::class, 'kayit_id');
    }

    public function veliBilgisi(): HasOne
    {
        return $this->hasOne(EkayitVeliBilgisi::class, 'kayit_id');
    }

    public function babaBilgisi(): HasOne
    {
        return $this->hasOne(EkayitBabaBilgisi::class, 'kayit_id');
    }

    public function olusturulanEvraklar(): HasMany
    {
        return $this->hasMany(EkayitOlusturulanEvrak::class, 'kayit_id');
    }
}
