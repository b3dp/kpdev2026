<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SmsListe extends Model
{
    protected $table = 'sms_listeler';

    protected $fillable = [
        'ad',
        'sahip_yonetici_id',
    ];

    public function kisiler(): BelongsToMany
    {
        return $this->belongsToMany(SmsKisi::class, 'sms_liste_kisiler', 'liste_id', 'kisi_id')
            ->withPivot('created_at');
    }

    public function sahip(): BelongsTo
    {
        return $this->belongsTo(Yonetici::class, 'sahip_yonetici_id');
    }
}
