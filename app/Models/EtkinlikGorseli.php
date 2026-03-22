<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EtkinlikGorseli extends Model
{
    public $timestamps = false;

    protected $table = 'etkinlik_gorselleri';

    protected $fillable = [
        'etkinlik_id',
        'sira',
        'orijinal_yol',
        'lg_yol',
        'og_yol',
        'sm_yol',
        'alt_text',
    ];

    public function etkinlik(): BelongsTo
    {
        return $this->belongsTo(Etkinlik::class, 'etkinlik_id');
    }

    public function lgUrl(): string
    {
        return Storage::disk('spaces')->url($this->lg_yol);
    }
}
