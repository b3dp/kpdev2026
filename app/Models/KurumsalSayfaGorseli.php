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
        if (filter_var((string) $this->lg_yol, FILTER_VALIDATE_URL)) {
            return (string) $this->lg_yol;
        }

        return Storage::disk('spaces')->url((string) $this->lg_yol);
    }
}
