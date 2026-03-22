<?php

namespace App\Models;

use App\Enums\KisiCinsiyet;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Kisi extends Model
{
    use HasFactory, LogsActivity, Searchable, SoftDeletes;

    protected $table = 'kisiler';

    protected $fillable = [
        'ad',
        'soyad',
        'cinsiyet',
        'dogum_tarihi',
        'tc_kimlik',
        'telefon',
        'eposta',
        'adres',
        'il',
        'ilce',
        'meslek',
        'notlar',
        'ai_onaylandi',
        'ai_skoru',
    ];

    protected function casts(): array
    {
        return [
            'cinsiyet' => KisiCinsiyet::class,
            'dogum_tarihi' => 'date',
            'ai_onaylandi' => 'boolean',
            'ai_skoru' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ad', 'soyad', 'cinsiyet', 'telefon', 'eposta', 'il', 'ilce', 'meslek', 'ai_onaylandi', 'ai_skoru'])
            ->logOnlyDirty()
            ->useLogName('kisiler')
            ->dontSubmitEmptyLogs();
    }

    public function kurumlar(): BelongsToMany
    {
        return $this->belongsToMany(Kurum::class, 'kisi_kurum', 'kisi_id', 'kurum_id')
            ->withPivot(['gorev', 'baslangic_tarihi', 'bitis_tarihi', 'aktif'])
            ->withTimestamps();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'ad' => $this->ad,
            'soyad' => $this->soyad,
            'tam_ad' => $this->full_ad,
            'telefon' => $this->telefon,
            'eposta' => $this->eposta,
            'meslek' => $this->meslek,
            'il' => $this->il,
        ];
    }

    protected function fullAd(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trim(($attributes['ad'] ?? '').' '.($attributes['soyad'] ?? '')),
        );
    }
}