<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EkayitEvrakSablonu extends Model
{
    use LogsActivity;

    public $timestamps = false;

    protected $table = 'ekayit_evrak_sablonlari';

    protected $fillable = [
        'ad', 'dosya_adi', 'sablon_yol', 'degiskenler',
        'sadece_onayliya', 'sira', 'aktif',
    ];

    protected function casts(): array
    {
        return [
            'degiskenler'     => 'array',
            'sadece_onayliya' => 'boolean',
            'aktif'           => 'boolean',
            'created_at'      => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ad', 'dosya_adi', 'sablon_yol', 'sira', 'aktif'])
            ->logOnlyDirty()->useLogName('ekayit_evrak')->dontSubmitEmptyLogs();
    }

    public function olusturulanEvraklar(): HasMany
    {
        return $this->hasMany(EkayitOlusturulanEvrak::class, 'sablon_id');
    }
}
