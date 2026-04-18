<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class HaberKategorisi extends Model
{
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    protected $table = 'haber_kategorileri';

    protected $fillable = [
        'ad',
        'slug',
        'renk',
        'sira',
        'aktif',
        'seo_baslik',
        'meta_description',
        'aciklama',
        'gorsel',
        'ikon',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
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
            ->logOnly(['ad', 'slug', 'renk', 'sira', 'aktif', 'seo_baslik', 'meta_description', 'aciklama', 'gorsel', 'ikon'])
            ->logOnlyDirty()
            ->useLogName('haber_kategorileri')
            ->dontSubmitEmptyLogs();
    }

    public function haberler(): HasMany
    {
        return $this->hasMany(Haber::class, 'kategori_id');
    }

    public function eslesenHaberler(): BelongsToMany
    {
        return $this->belongsToMany(Haber::class, 'haber_kategori_eslestirmeleri', 'haber_kategorisi_id', 'haber_id')
            ->withPivot(['skor', 'ana_kategori_mi', 'kaynak'])
            ->withTimestamps();
    }
}
