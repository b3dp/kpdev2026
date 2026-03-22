<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        return Storage::disk('spaces')->url($this->lg_yol);
    }

    public function ogUrl(): string
    {
        return Storage::disk('spaces')->url($this->og_yol);
    }

    public function smUrl(): string
    {
        return Storage::disk('spaces')->url($this->sm_yol);
    }
}
