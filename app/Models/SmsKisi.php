<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsKisi extends Model
{
    use SoftDeletes;

    protected $table = 'sms_kisiler';

    protected $fillable = [
        'telefon',
        'telefon_2',
        'ad_soyad',
        'notlar',
        'created_by',
    ];

    public function listeler(): BelongsToMany
    {
        return $this->belongsToMany(SmsListe::class, 'sms_liste_kisiler', 'kisi_id', 'liste_id')
            ->withPivot('created_at');
    }

    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(Yonetici::class, 'created_by');
    }
}
