<?php

namespace App\Models;

use App\Enums\KurbanBildirimDurumu;
use App\Enums\KurbanDurumu;
use App\Services\KurbanNoService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KurbanKayit extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'kurban_kayitlar';

    protected $fillable = [
        'kurban_no',
        'bagis_id',
        'bagis_kalem_id',
        'bagis_turu_adi',
        'bagis_ozelligi',
        'durum',
        'kesim_tarihi',
        'kesim_yeri',
        'kesim_gorevlisi',
        'hisse_sayisi',
        'bildirim_durumu',
        'not',
    ];

    protected function casts(): array
    {
        return [
            'durum' => KurbanDurumu::class,
            'bildirim_durumu' => KurbanBildirimDurumu::class,
            'kesim_tarihi' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'kurban_no',
                'bagis_id',
                'bagis_kalem_id',
                'bagis_turu_adi',
                'bagis_ozelligi',
                'durum',
                'kesim_tarihi',
                'kesim_yeri',
                'kesim_gorevlisi',
                'hisse_sayisi',
                'bildirim_durumu',
                'not',
            ])
            ->logOnlyDirty()
            ->useLogName('kurban')
            ->dontSubmitEmptyLogs();
    }

    public function bagis(): BelongsTo
    {
        return $this->belongsTo(Bagis::class, 'bagis_id');
    }

    public function bagisKalemi(): BelongsTo
    {
        return $this->belongsTo(BagisKalemi::class, 'bagis_kalem_id');
    }

    public function kisiler(): HasMany
    {
        return $this->hasMany(KurbanKisi::class, 'kurban_id');
    }

    public function bildirimler(): HasMany
    {
        return $this->hasMany(KurbanBildirim::class, 'kurban_id');
    }

    public function sahiplerOzeti(): string
    {
        $adlar = $this->kisiler
            ->sortBy('hisse_no')
            ->pluck('ad_soyad')
            ->filter()
            ->unique()
            ->values();

        if ($adlar->isEmpty()) {
            return '—';
        }

        if ($adlar->count() <= 2) {
            return $adlar->implode(', ');
        }

        return $adlar->take(2)->implode(', ').' + '.($adlar->count() - 2).' kişi';
    }

    public static function kurbanNoUret(int $offset = 0): string
    {
        return app(KurbanNoService::class)->uret($offset);
    }
}