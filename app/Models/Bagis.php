<?php

namespace App\Models;

use App\Enums\BagisDurumu;
use App\Enums\OdemeSaglayici;
use App\Jobs\KurbanAktarimJob;
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

            $oncekiDurumHam = $bagis->getRawOriginal('durum');
            $oncekiDurum = $oncekiDurumHam instanceof BagisDurumu
                ? $oncekiDurumHam->value
                : (string) $oncekiDurumHam;

            $yeniDurum = $bagis->durum instanceof BagisDurumu
                ? $bagis->durum->value
                : (string) $bagis->durum;

            if ($oncekiDurum !== BagisDurumu::Odendi->value && $yeniDurum === BagisDurumu::Odendi->value) {
                MakbuzOlusturJob::dispatch($bagis)->onQueue('default');
                KurbanAktarimJob::dispatch($bagis->id)->onQueue('default');
                app(\App\Services\KisiEslestirmeService::class)->bagisEslestir($bagis);
            }
        });
    }

    protected $table = 'bagislar';

    protected $fillable = [
        'bagis_no',
        'sepet_id',
        'uye_id',
        'kisi_id',
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

    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
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

    public function kurbanKayitlari(): HasMany
    {
        return $this->hasMany(KurbanKayit::class, 'bagis_id');
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

    public function bagisTurleriOzeti(): string
    {
        $turAdlari = $this->kalemler
            ->map(fn (BagisKalemi $kalem) => trim((string) ($kalem->bagisTuru?->ad ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($turAdlari->isEmpty()) {
            return '—';
        }

        if ($turAdlari->count() === 1) {
            return (string) $turAdlari->first();
        }

        if ($turAdlari->count() === 2) {
            return $turAdlari->implode(' + ');
        }

        $ilkIkiTur = $turAdlari->take(2)->implode(' + ');
        $kalanTurSayisi = $turAdlari->count() - 2;

        return $ilkIkiTur.' + '.$kalanTurSayisi.' diğer bağış türü';
    }

    public function bagisKalemOzetleri(): array
    {
        return $this->kalemler
            ->groupBy(fn (BagisKalemi $kalem) => $kalem->bagisTuru?->ad ?? 'Bağış Kalemi')
            ->map(fn ($kalemler, $ad) => [
                'ad' => $ad,
                'adet' => (int) $kalemler->sum(fn (BagisKalemi $kalem) => (int) ($kalem->adet ?? 1)),
                'toplam' => (float) $kalemler->sum(fn (BagisKalemi $kalem) => (float) ($kalem->toplam ?? 0)),
            ])
            ->values()
            ->all();
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
