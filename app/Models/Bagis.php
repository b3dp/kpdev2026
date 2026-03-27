<?php

namespace App\Models;

use App\Enums\BagisDurumu;
use App\Enums\OdemeSaglayici;
use App\Jobs\MakbuzOlusturJob;
use App\Services\BagisNoService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Bagis extends Model
{
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::updated(function (self $bagis): void {
            if (! $bagis->wasChanged('durum')) {
                return;
            }

            $oncekiDurum = (string) $bagis->getOriginal('durum');
            $yeniDurum = $bagis->durum instanceof BagisDurumu
                ? $bagis->durum->value
                : (string) $bagis->durum;

            if ($oncekiDurum !== BagisDurumu::Odendi->value && $yeniDurum === BagisDurumu::Odendi->value) {
                MakbuzOlusturJob::dispatch($bagis)->onQueue('default');
            }
        });
    }

    protected $table = 'bagislar';

    protected $fillable = [
        'bagis_no',
        'sepet_id',
        'uye_id',
        'durum',
        'toplam_tutar',
        'odeme_saglayici',
        'odeme_referans',
        'makbuz_yol',
        'makbuz_gonderildi',
        'kurban_aktarildi',
        'odeme_tarihi',
    ];

    protected function casts(): array
    {
        return [
            'durum' => BagisDurumu::class,
            'toplam_tutar' => 'decimal:2',
            'odeme_saglayici' => OdemeSaglayici::class,
            'makbuz_gonderildi' => 'boolean',
            'kurban_aktarildi' => 'boolean',
            'odeme_tarihi' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'bagis_no',
                'durum',
                'toplam_tutar',
                'odeme_saglayici',
                'odeme_referans',
                'makbuz_gonderildi',
                'kurban_aktarildi',
                'odeme_tarihi',
            ])
            ->logOnlyDirty()
            ->useLogName('bagis')
            ->dontSubmitEmptyLogs();
    }

    public function sepet(): BelongsTo
    {
        return $this->belongsTo(BagisSepet::class, 'sepet_id');
    }

    public function uye(): BelongsTo
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }

    public function kalemler(): HasMany
    {
        return $this->hasMany(BagisKalemi::class, 'bagis_id');
    }

    public function kisiler(): HasMany
    {
        return $this->hasMany(BagisKisi::class, 'bagis_id');
    }

    public function odemeHatalari(): HasMany
    {
        return $this->hasMany(OdemeHatasi::class, 'bagis_id');
    }

    public static function bagisNoUret(): string
    {
        return app(BagisNoService::class)->uret();
    }

    public function makbuzUrl(): ?string
    {
        if (blank($this->makbuz_yol)) {
            return null;
        }

        $cdnUrl = rtrim((string) env('DO_URL', config('filesystems.disks.spaces.url', '')), '/');

        if ($cdnUrl === '') {
            return null;
        }

        return $cdnUrl.'/'.ltrim($this->makbuz_yol, '/');
    }

    public function odeyenKisi(): ?BagisKisi
    {
        return $this->kisiler->first(fn (BagisKisi $kisi) => in_array('odeyen', $kisi->tipListesi(), true));
    }

    public function sahipKisi(): ?BagisKisi
    {
        return $this->kisiler->first(fn (BagisKisi $kisi) => in_array('sahip', $kisi->tipListesi(), true));
    }
}
