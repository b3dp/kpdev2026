<?php

namespace App\Models;

use App\Enums\KurumsalSablonu;
use App\Enums\RobotsKurali;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class KurumsalSayfa extends Model
{
    use HasFactory, HasSlug, LogsActivity, Searchable, SoftDeletes;

    protected $table = 'kurumsal_sayfalar';

    protected $fillable = [
        'ust_sayfa_id',
        'sablon',
        'ad',
        'slug',
        'kurum_id',
        'icerik',
        'ozet',
        'meta_description',
        'robots',
        'canonical_url',
        'og_gorsel',
        'banner_masaustu',
        'banner_mobil',
        'banner_orijinal',
        'gorsel_lg',
        'gorsel_og',
        'gorsel_sm',
        'gorsel_orijinal',
        'video_embed_url',
        'durum',
        'ai_islendi',
        'sira',
    ];

    protected function casts(): array
    {
        return [
            'sablon' => KurumsalSablonu::class,
            'robots' => RobotsKurali::class,
            'ai_islendi' => 'boolean',
            'sira' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $sayfa): void {
            if (! filled($sayfa->ust_sayfa_id)) {
                return;
            }

            $ustSayfa = self::query()->find($sayfa->ust_sayfa_id);
            if (! $ustSayfa || ! $ustSayfa->altSayfaEklenebilirMi()) {
                throw ValidationException::withMessages([
                    'ust_sayfa_id' => 'Secilen sayfaya alt sayfa eklenemez.',
                ]);
            }

            if ($sayfa->exists && (int) $sayfa->ust_sayfa_id === (int) $sayfa->id) {
                throw ValidationException::withMessages([
                    'ust_sayfa_id' => 'Sayfa kendisini ust sayfa olarak secemez.',
                ]);
            }
        });

        static::deleting(function (self $sayfa): void {
            if ($sayfa->altSayfalar()->exists()) {
                throw ValidationException::withMessages([
                    'ad' => 'Bu sayfanin alt sayfalari var. Once alt sayfalari tasiyin veya silin.',
                ]);
            }

            Kurum::query()->where('kurumsal_sayfa_id', $sayfa->id)->update(['kurumsal_sayfa_id' => null]);
        });
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
                'ust_sayfa_id',
                'sablon',
                'ad',
                'slug',
                'kurum_id',
                'ozet',
                'meta_description',
                'robots',
                'canonical_url',
                'og_gorsel',
                'banner_masaustu',
                'banner_mobil',
                'banner_orijinal',
                'gorsel_lg',
                'gorsel_og',
                'gorsel_sm',
                'gorsel_orijinal',
                'video_embed_url',
                'durum',
                'ai_islendi',
                'sira',
            ])
            ->logOnlyDirty()
            ->useLogName('kurumsal_sayfa')
            ->dontSubmitEmptyLogs();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'ad' => $this->ad,
            'slug' => $this->slug,
            'icerik' => strip_tags((string) $this->icerik),
            'ozet' => $this->ozet,
            'durum' => $this->durum,
        ];
    }

    public function seviye(): int
    {
        $seviye = 1;
        $ust = $this->ustSayfa;

        while ($ust) {
            $seviye++;
            $ust = $ust->ustSayfa;
        }

        return $seviye;
    }

    public function altSayfaEklenebilirMi(): bool
    {
        return $this->seviye() < 3;
    }

    public function ustSayfa(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ust_sayfa_id');
    }

    public function altSayfalar(): HasMany
    {
        return $this->hasMany(self::class, 'ust_sayfa_id')->orderBy('sira');
    }

    public function kurum(): BelongsTo
    {
        return $this->belongsTo(Kurum::class, 'kurum_id');
    }

    public function lokasyonlar(): HasMany
    {
        return $this->hasMany(KurumsalIletisimLokasyonu::class, 'sayfa_id')->orderBy('sira');
    }

    public function gorseller(): HasMany
    {
        return $this->hasMany(KurumsalSayfaGorseli::class, 'sayfa_id')->orderBy('sira');
    }

    public function gorselLgUrl(): string
    {
        return $this->olusturGorselUrl($this->gorsel_lg);
    }

    public function gorselSmUrl(): string
    {
        return $this->olusturGorselUrl($this->gorsel_sm);
    }

    public function gorselOgUrl(): string
    {
        return $this->olusturGorselUrl($this->gorsel_og);
    }

    public function ogGorselUrl(): string
    {
        return $this->olusturGorselUrl($this->og_gorsel);
    }

    public function bannerMasaustuUrl(): string
    {
        return $this->olusturGorselUrl($this->banner_masaustu);
    }

    public function bannerMobilUrl(): string
    {
        return $this->olusturGorselUrl($this->banner_mobil ?: $this->banner_masaustu);
    }

    private function olusturGorselUrl(?string $yol): string
    {
        if (blank($yol)) {
            return '';
        }

        if (filter_var((string) $yol, FILTER_VALIDATE_URL)) {
            return (string) $yol;
        }

        return Storage::disk('spaces')->url(ltrim((string) $yol, '/'));
    }
}
