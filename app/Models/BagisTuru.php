<?php

namespace App\Models;

use App\Enums\BagisAcilisTipi;
use App\Enums\BagisFiyatTipi;
use App\Enums\BagisOzelligi;
use App\Services\HicriTakvimService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class BagisTuru extends Model
{
    use HasFactory, HasSlug, LogsActivity;

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget('sitemap_bagis');
            Cache::forget('sitemap_static');
        });

        static::deleted(function (): void {
            Cache::forget('sitemap_bagis');
            Cache::forget('sitemap_static');
        });
    }

    protected $table = 'bagis_turleri';

    protected $fillable = [
        'ad',
        'slug',
        'ozellik',
        'fiyat_tipi',
        'fiyat',
        'minimum_tutar',
        'oneri_tutarlar',
        'aciklama',
        'hadis_ayet',
        'gorsel_kare',
        'gorsel_dikey',
        'gorsel_yatay',
        'gorsel_orijinal',
        'video_yol',
        'acilis_tipi',
        'acilis_hicri_ay',
        'acilis_hicri_gun',
        'kapanis_hicri_ay',
        'kapanis_hicri_gun',
        'kapanis_saat',
        'kurban_modulu',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'ozellik' => BagisOzelligi::class,
            'fiyat_tipi' => BagisFiyatTipi::class,
            'acilis_tipi' => BagisAcilisTipi::class,
            'fiyat' => 'decimal:2',
            'minimum_tutar' => 'decimal:2',
            'oneri_tutarlar' => 'array',
            'kurban_modulu' => 'boolean',
            'aktif' => 'boolean',
            'kapanis_saat' => 'string',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('ad')
            ->saveSlugsTo('slug');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ad',
                'slug',
                'ozellik',
                'fiyat_tipi',
                'fiyat',
                'minimum_tutar',
                'acilis_tipi',
                'aktif',
            ])
            ->logOnlyDirty()
            ->useLogName('bagis_turu')
            ->dontSubmitEmptyLogs();
    }

    public function bagislar(): HasManyThrough
    {
        return $this->hasManyThrough(
            Bagis::class,
            BagisKalemi::class,
            'bagis_turu_id',
            'id',
            'id',
            'bagis_id'
        );
    }

    public function sepetSatirlari(): HasMany
    {
        return $this->hasMany(BagisSepetSatir::class, 'bagis_turu_id');
    }

    public function aktifMi(): bool
    {
        return (bool) $this->aktif && $this->hicriKontrol();
    }

    public function hicriKontrol(): bool
    {
        return app(HicriTakvimService::class)->turAcikMi($this);
    }
}
