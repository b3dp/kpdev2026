<?php

namespace App\Models;

use App\Enums\UyeDurumu;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Uye extends Authenticatable implements HasName
{
    use HasFactory, LogsActivity, Notifiable, SoftDeletes;

    protected $table = 'uyeler';

    protected string $guard_name = 'uye';

    protected $fillable = [
        'ad_soyad',
        'telefon',
        'eposta',
        'sifre',
        'telefon_dogrulandi',
        'eposta_dogrulandi',
        'sms_abonelik',
        'eposta_abonelik',
        'durum',
        'aktif',
        'son_giris',
        'remember_token',
        'abonelik_token',
        'kisi_id',
    ];

    protected $hidden = [
        'sifre',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'sifre' => 'hashed',
            'telefon_dogrulandi' => 'boolean',
            'eposta_dogrulandi' => 'boolean',
            'sms_abonelik' => 'boolean',
            'eposta_abonelik' => 'boolean',
            'aktif' => 'boolean',
            'durum' => UyeDurumu::class,
            'son_giris' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ad_soyad',
                'telefon',
                'eposta',
                'telefon_dogrulandi',
                'eposta_dogrulandi',
                'sms_abonelik',
                'eposta_abonelik',
                'durum',
                'kisi_id',
            ])
            ->logOnlyDirty()
            ->useLogName('uyeler')
            ->dontSubmitEmptyLogs();
    }

    public function getAuthIdentifierName(): string
    {
        return 'telefon';
    }

    public function getAuthPassword(): ?string
    {
        return $this->sifre;
    }

    public function getFilamentName(): string
    {
        return $this->ad_soyad
            ?? $this->eposta
            ?? $this->telefon
            ?? 'Uye';
    }

    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    public function rozetler(): HasMany
    {
        return $this->hasMany(UyeRozet::class, 'uye_id');
    }

    public function bildirimler(): HasMany
    {
        return $this->hasMany(UyeBildirim::class, 'uye_id');
    }
}