<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EkayitDonem extends Model
{
    use LogsActivity;

    protected $table = 'ekayit_donemler';

    protected $fillable = ['ad', 'ogretim_yili', 'baslangic', 'bitis', 'aktif'];

    protected function casts(): array
    {
        return [
            'baslangic' => 'datetime',
            'bitis'     => 'datetime',
            'aktif'     => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ad', 'ogretim_yili', 'baslangic', 'bitis', 'aktif'])
            ->logOnlyDirty()->useLogName('ekayit_donem')->dontSubmitEmptyLogs();
    }

    public function siniflar(): HasMany
    {
        return $this->hasMany(EkayitSinif::class, 'donem_id');
    }

    public static function aktifDonem(): ?self
    {
        return self::where('aktif', true)->first();
    }
}
