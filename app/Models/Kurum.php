<?php

namespace App\Models;

use App\Enums\KurumTipi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Kurum extends Model
{
    use HasFactory, HasSlug, LogsActivity, Searchable, SoftDeletes;

    protected $table = 'kurumlar';

    protected $fillable = [
        'ad',
        'slug',
        'tip',
        'telefon',
        'eposta',
        'adres',
        'il',
        'ilce',
        'web_sitesi',
        'aciklama',
        'aktif',
        'kurumsal_sayfa_id',
    ];

    protected function casts(): array
    {
        return [
            'tip' => KurumTipi::class,
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
            ->logOnly(['ad', 'slug', 'tip', 'telefon', 'eposta', 'il', 'ilce', 'web_sitesi', 'aktif'])
            ->logOnlyDirty()
            ->useLogName('kurumlar')
            ->dontSubmitEmptyLogs();
    }

    public function kisiler(): BelongsToMany
    {
        return $this->belongsToMany(Kisi::class, 'kisi_kurum', 'kurum_id', 'kisi_id')
            ->withPivot(['gorev', 'baslangic_tarihi', 'bitis_tarihi', 'aktif'])
            ->withTimestamps();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'ad' => $this->ad,
            'slug' => $this->slug,
            'tip' => $this->tip?->value,
            'il' => $this->il,
            'ilce' => $this->ilce,
            'telefon' => $this->telefon,
        ];
    }
}