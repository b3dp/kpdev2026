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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        'seo_baslik',
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
        'legacy_kaynak_id',
        'ai_islendi',
        'ai_islem_yuzde',
        'ai_islem_adim',
        'ai_onay',
        'gorsel_orijinal',
        'gorsel_lg',
        'gorsel_og',
        'gorsel_sm',
        'gorsel_mobil_lg',
        'onay_token',
        'onay_token_expires_at',
        'onay_epostasi_gonderildi_at',
        'onay_sms_gonderildi_at',
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
            'ai_islem_yuzde' => 'integer',
            'ai_onay' => 'boolean',
            'onay_token_expires_at' => 'datetime',
            'onay_epostasi_gonderildi_at' => 'datetime',
            'onay_sms_gonderildi_at' => 'datetime',
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

        static::updated(function (): void {
            self::siteHaritasiniYenile();
        });

        static::deleted(function (): void {
            self::siteHaritasiniYenile();
        });

        static::restored(function (): void {
            self::siteHaritasiniYenile();
        });
    }

    private static function siteHaritasiniYenile(): void
    {
        try {
            Artisan::call('site-haritasi:olustur');
        } catch (\Throwable $e) {
            Log::error('Haber kaydinda sitemap yenileme hatasi', [
                'hata' => $e->getMessage(),
            ]);
        }
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
                'durum',
                'oncelik',
                'kategori_id',
                'manset',
                'yayin_tarihi',
                'yayin_bitis',
                'meta_description',
                'robots',
                'canonical_url',
                'legacy_kaynak_id',
                'ai_islendi',
                'ai_islem_yuzde',
                'ai_islem_adim',
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

    public function kategoriler(): BelongsToMany
    {
        return $this->belongsToMany(HaberKategorisi::class, 'haber_kategori_eslestirmeleri', 'haber_id', 'haber_kategorisi_id')
            ->withPivot(['skor', 'ana_kategori_mi', 'kaynak'])
            ->withTimestamps()
            ->orderByPivot('ana_kategori_mi', 'desc')
            ->orderBy('haber_kategorileri.sira')
            ->orderBy('haber_kategorileri.ad');
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

    public function onaylanmisKisiler(): BelongsToMany
    {
        return $this->belongsToMany(Kisi::class, 'haber_kisiler', 'haber_id', 'kisi_id')
            ->withPivot(['rol', 'onay_durumu'])
            ->wherePivot('onay_durumu', 'onaylandi')
            ->withTimestamps()
            ->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);
    }

    public function onaylanmisKurumlar(): BelongsToMany
    {
        return $this->belongsToMany(Kurum::class, 'haber_kurumlar', 'haber_id', 'kurum_id')
            ->withPivot(['onay_durumu'])
            ->wherePivot('onay_durumu', 'onaylandi')
            ->withTimestamps()
            ->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);
    }

    public function onayTokenlar(): HasMany
    {
        return $this->hasMany(HaberOnayToken::class, 'haber_id');
    }

    public function aiRevizyonlari(): HasMany
    {
        return $this->hasMany(HaberAiRevizyonu::class, 'haber_id')->latest('created_at');
    }

    public function gorseller(): HasMany
    {
        return $this->hasMany(HaberGorseli::class, 'haber_id')->orderBy('sira');
    }

    public function getGosterimTarihiAttribute(): ?Carbon
    {
        return $this->yayin_tarihi ?? $this->created_at;
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

    private function olusturGorselUrl(?string $yol): string
    {
        if (blank($yol)) {
            return '';
        }

        if (Str::startsWith($yol, ['http://', 'https://'])) {
            return $yol;
        }

        return Storage::disk('spaces')->url(ltrim($yol, '/'));
    }
}
