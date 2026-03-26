<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class Yonetici extends Authenticatable implements FilamentUser, HasName
{
    use HasRoles, LogsActivity, Notifiable, SoftDeletes;

    protected $table = 'yoneticiler';

    protected string $guard_name = 'admin';

    protected $fillable = [
        'ad_soyad',
        'eposta',
        'sifre',
        'telefon',
        'aktif',
        'son_giris',
    ];

    protected $hidden = [
        'sifre',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'sifre' => 'hashed',
            'aktif' => 'boolean',
            'son_giris' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Model $model): bool {
            throw new \RuntimeException('Yonetici silme islemi sistem genelinde kapatilmis durumda.');
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ad_soyad', 'eposta', 'telefon', 'aktif'])
            ->logOnlyDirty()
            ->useLogName('yoneticiler')
            ->dontSubmitEmptyLogs();
    }

    public function getAuthPassword(): string
    {
        return $this->sifre;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->eposta;
    }

    public function routeNotificationForMail($notification): string
    {
        return $this->eposta;
    }

    public function getFilamentName(): string
    {
        return $this->ad_soyad;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->aktif;
    }
}
