<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function kurumsalSayfa(): BelongsTo
    {
        return $this->belongsTo(KurumsalSayfa::class, 'kurumsal_sayfa_id');
    }

    public function toSearchableArray(): array
    {
        $tip = $this->tip;

        return [
            'id' => $this->id,
            'ad' => $this->ad,
            'slug' => $this->slug,
            'tip' => $tip instanceof \BackedEnum ? $tip->value : (string) $tip,
            'il' => $this->il,
            'ilce' => $this->ilce,
            'telefon' => $this->telefon,
        ];
    }
}