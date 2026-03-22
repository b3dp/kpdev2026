<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Etiket extends Model
{
    use HasFactory, HasSlug;

    protected $table = 'etiketler';

    protected $fillable = [
        'ad',
        'slug',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('ad')
            ->saveSlugsTo('slug');
    }

    public function haberler(): BelongsToMany
    {
        return $this->belongsToMany(Haber::class, 'haber_etiketler', 'etiket_id', 'haber_id')->withTimestamps();
    }
}
