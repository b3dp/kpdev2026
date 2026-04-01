<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HaberGorseli extends Model
{
    public $timestamps = false;

    protected $table = 'haber_gorselleri';

    protected $fillable = [
        'haber_id',
        'sira',
        'orijinal_yol',
        'lg_yol',
        'og_yol',
        'sm_yol',
        'alt_text',
    ];

    public function haber(): BelongsTo
    {
        return $this->belongsTo(Haber::class);
    }

    public function lgUrl(): string
    {
        return $this->olusturUrl($this->lg_yol);
    }

    public function ogUrl(): string
    {
        return $this->olusturUrl($this->og_yol);
    }

    public function smUrl(): string
    {
        return $this->olusturUrl($this->sm_yol);
    }

    private function olusturUrl(?string $yol): string
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
