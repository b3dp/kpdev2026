<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EpostaSablon extends Model
{
    use LogsActivity;

    protected $table = 'eposta_sablonlar';

    protected $fillable = [
        'kod',
        'ad',
        'konu',
        'tip',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('eposta_sablon')
            ->logFillable()
            ->dontSubmitEmptyLogs();
    }

    public function bladePath(): string
    {
        return 'emails.' . $this->kod;
    }
}
