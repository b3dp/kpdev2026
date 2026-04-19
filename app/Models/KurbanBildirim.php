<?php

namespace App\Models;

use App\Enums\KurbanBildirimKanali;
use App\Enums\KurbanBildirimSonucu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KurbanBildirim extends Model
{
    use HasFactory;

    protected $table = 'kurban_bildirimler';

    protected $fillable = [
        'kurban_id',
        'kurban_kisi_id',
        'kanal',
        'alici_ad',
        'alici_iletisim',
        'durum',
        'hata_mesaji',
        'gonderim_tarihi',
    ];

    protected function casts(): array
    {
        return [
            'kanal' => KurbanBildirimKanali::class,
            'durum' => KurbanBildirimSonucu::class,
            'gonderim_tarihi' => 'datetime',
        ];
    }

    public function kurban(): BelongsTo
    {
        return $this->belongsTo(KurbanKayit::class, 'kurban_id');
    }

    public function kurbanKisi(): BelongsTo
    {
        return $this->belongsTo(KurbanKisi::class, 'kurban_kisi_id');
    }
}