<?php

namespace App\Models;

use App\Enums\EtkinlikDurumu;
use App\Enums\EtkinlikTipi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Etkinlik extends Model
{
    use HasFactory, HasSlug, LogsActivity, Searchable, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget('sitemap_etkinlikler');
            Cache::forget('sitemap_static');
        });

        static::deleted(function (): void {
            Cache::forget('sitemap_etkinlikler');
            Cache::forget('sitemap_static');
        });

        static::restored(function (): void {
            Cache::forget('sitemap_etkinlikler');
            Cache::forget('sitemap_static');
        });
    }

    public function getGorselLgCdnUrlAttribute(): ?string
    {
        if (!filled($this->gorsel_lg)) {
            return null;
        }
        $path = ltrim(str_replace('storage/', '', $this->gorsel_lg), '/');
        return 'https://cdn.kestanepazari.org.tr/' . $path;
    }

    protected $table = 'etkinlikler';

    protected $fillable = [
        'yonetici_id',
        'baslik',
        'seo_baslik',
        'slug',
        'ozet',
        'aciklama',
        'tip',
        'durum',
        'baslangic_tarihi',
        'bitis_tarihi',
        'konum_ad',
        'konum_adres',
        'konum_il',
        'konum_ilce',
        'konum_lat',
        'konum_lng',
        'konum_place_id',
        'online_link',
        'kontenjan',
        'kayitli_kisi',
        'gorsel_orijinal',
        'gorsel_lg',
        'gorsel_og',
        'gorsel_sm',
        'gorsel_mobil_lg',
        'meta_description',
        'robots',
        'canonical_url',
        'ai_islendi',
    ];

    protected function casts(): array
    {
        return [
            'tip' => EtkinlikTipi::class,
            'durum' => EtkinlikDurumu::class,
            'baslangic_tarihi' => 'datetime',
            'bitis_tarihi' => 'datetime',
            'konum_lat' => 'decimal:7',
            'konum_lng' => 'decimal:7',
            'kontenjan' => 'integer',
            'kayitli_kisi' => 'integer',
            'ai_islendi' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('baslik')
            ->slugsShouldBeNoLongerThan(100)
            ->saveSlugsTo('slug');
    }

    public function getSeoBaslikAttribute(?string $value): string
    {
        if (filled($value)) {
            return $value;
        }

        return mb_substr((string) ($this->attributes['baslik'] ?? ''), 0, 60, 'UTF-8');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'yonetici_id',
                'baslik',
                'seo_baslik',
                'slug',
                'tip',
                'durum',
                'baslangic_tarihi',
                'bitis_tarihi',
                'konum_ad',
                'konum_adres',
                'konum_il',
                'konum_ilce',
                'konum_lat',
                'konum_lng',
                'konum_place_id',
                'online_link',
                'kontenjan',
                'kayitli_kisi',
                'meta_description',
                'robots',
                'canonical_url',
                'ai_islendi',
                'gorsel_orijinal',
                'gorsel_lg',
                'gorsel_og',
                'gorsel_sm',
                'gorsel_mobil_lg',
            ])
            ->logOnlyDirty()
            ->useLogName('etkinlikler')
            ->dontSubmitEmptyLogs();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'baslik' => $this->baslik,
            'ozet' => $this->ozet,
            'aciklama' => strip_tags((string) $this->aciklama),
            'konum_ad' => $this->konum_ad,
            'slug' => $this->slug,
            'durum' => $this->durum?->value,
        ];
    }

    public function yonetici(): BelongsTo
    {
        return $this->belongsTo(Yonetici::class, 'yonetici_id');
    }

    public function gorseller(): HasMany
    {
        return $this->hasMany(EtkinlikGorseli::class, 'etkinlik_id')->orderBy('sira');
    }
}
