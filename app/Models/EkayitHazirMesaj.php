<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EkayitHazirMesaj extends Model
{
    public $timestamps = false;

    protected $table = 'ekayit_hazir_mesajlar';

    protected $fillable = ['baslik', 'metin', 'tip', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'created_at' => 'datetime'];
    }

    public function scopeOnay(Builder $query): Builder
    {
        return $query->where('tip', 'onay')->where('aktif', true);
    }

    public function scopeRed(Builder $query): Builder
    {
        return $query->where('tip', 'red')->where('aktif', true);
    }

    public function scopeYedek(Builder $query): Builder
    {
        return $query->where('tip', 'yedek')->where('aktif', true);
    }

    public function scopeGenel(Builder $query): Builder
    {
        return $query->where('tip', 'genel')->where('aktif', true);
    }
}
