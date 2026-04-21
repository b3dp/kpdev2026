<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KurumsalSayfaGorseli extends Model
{
    use HasFactory;

    protected $table = 'kurumsal_sayfa_galerileri';

    public const UPDATED_AT = null;

    protected $fillable = [
        'sayfa_id',
        'sira',
        'orijinal_yol',
        'lg_yol',
        'og_yol',
        'sm_yol',
        'alt_text',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sira' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function sayfa(): BelongsTo
    {
        return $this->belongsTo(KurumsalSayfa::class, 'sayfa_id');
    }

    public function lgUrl(): string
    {
        $yol = $this->lg_yol ?: $this->orijinal_yol;

        if (filter_var((string) $yol, FILTER_VALIDATE_URL)) {
            return (string) $yol;
        }

        return Storage::disk('spaces')->url((string) $yol);
    }

    public function smUrl(): string
    {
        $yol = $this->sm_yol ?: $this->orijinal_yol;

        if (filter_var((string) $yol, FILTER_VALIDATE_URL)) {
            return (string) $yol;
        }

        return Storage::disk('spaces')->url((string) $yol);
    }

    public function ogUrl(): string
    {
        $yol = $this->og_yol ?: $this->orijinal_yol;

        if (filter_var((string) $yol, FILTER_VALIDATE_URL)) {
            return (string) $yol;
        }

        return Storage::disk('spaces')->url((string) $yol);
    }

    public function orijinalUrl(): string
    {
        if (filter_var((string) $this->orijinal_yol, FILTER_VALIDATE_URL)) {
            return (string) $this->orijinal_yol;
        }

        return Storage::disk('spaces')->url((string) $this->orijinal_yol);
    }
}
