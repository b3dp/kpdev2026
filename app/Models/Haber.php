<?php

namespace App\Models;

use App\Enums\HaberDurumu;
use App\Enums\HaberOncelik;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Haber extends Model
{
    use HasFactory, HasSlug, LogsActivity, Searchable, SoftDeletes;

    protected $table = 'haberler';

    protected $fillable = [
        'yonetici_id',
        'baslik',
        'slug',
        'ozet',
        'icerik',
        'durum',
        'oncelik',
        'kategori_id',
        'manset',
        'yayin_tarihi',
        'yayin_bitis',
        'goruntuleme',
        'meta_description',
        'robots',
        'canonical_url',
        'ai_islendi',
        'ai_onay',
        'gorsel_orijinal',
        'gorsel_lg',
        'gorsel_og',
        'gorsel_sm',
        'gorsel_mobil_lg',
    ];

    protected function casts(): array
    {
        return [
            'durum' => HaberDurumu::class,
            'oncelik' => HaberOncelik::class,
            'manset' => 'boolean',
            'yayin_tarihi' => 'datetime',
            'yayin_bitis' => 'datetime',
            'goruntuleme' => 'integer',
            'ai_islendi' => 'boolean',
            'ai_onay' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Haber $haber): void {
            $mansetYapiliyor = $haber->manset && ($haber->isDirty('manset') || ! $haber->exists);

            if (! $mansetYapiliyor) {
                return;
            }

            $query = self::query()->where('manset', true);

            if ($haber->exists) {
                $query->where('id', '!=', $haber->id);
            }

            if ($query->count() >= 10) {
                throw ValidationException::withMessages([
                    'manset' => 'Manşet limiti doldu. En fazla 10 haber manşet olabilir.',
                ]);
            }

            if ($haber->manset) {
                $haber->oncelik = HaberOncelik::Manset;
            }
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('baslik')
            ->saveSlugsTo('slug');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'yonetici_id',
                'baslik',
                'slug',
                'durum',
                'oncelik',
                'kategori_id',
                'manset',
                'yayin_tarihi',
                'yayin_bitis',
                'meta_description',
                'robots',
                'canonical_url',
                'ai_islendi',
                'ai_onay',
                'gorsel_orijinal',
                'gorsel_lg',
                'gorsel_og',
                'gorsel_sm',
                'gorsel_mobil_lg',
            ])
            ->logOnlyDirty()
            ->useLogName('haberler')
            ->dontSubmitEmptyLogs();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'baslik' => $this->baslik,
            'slug' => $this->slug,
            'ozet' => $this->ozet,
            'icerik' => strip_tags((string) $this->icerik),
            'durum' => $this->durum?->value,
        ];
    }

    public static function mansetSayisi(): int
    {
        return self::query()->where('manset', true)->count();
    }

    public function yonetici(): BelongsTo
    {
        return $this->belongsTo(Yonetici::class, 'yonetici_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(HaberKategorisi::class, 'kategori_id');
    }

    public function etiketler(): BelongsToMany
    {
        return $this->belongsToMany(Etiket::class, 'haber_etiketler', 'haber_id', 'etiket_id')->withTimestamps();
    }

    public function kisiler(): BelongsToMany
    {
        return $this->belongsToMany(Kisi::class, 'haber_kisiler', 'haber_id', 'kisi_id')
            ->withPivot(['rol', 'onay_durumu'])
            ->withTimestamps();
    }

    public function kurumlar(): BelongsToMany
    {
        return $this->belongsToMany(Kurum::class, 'haber_kurumlar', 'haber_id', 'kurum_id')
            ->withPivot(['onay_durumu'])
            ->withTimestamps();
    }

    public function onayTokenlar(): HasMany
    {
        return $this->hasMany(HaberOnayToken::class, 'haber_id');
    }
}
