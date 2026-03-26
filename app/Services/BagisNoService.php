<?php

namespace App\Services;

use App\Models\Bagis;
use Illuminate\Support\Facades\DB;

class BagisNoService
{
    public function uret(): string
    {
        return DB::transaction(function (): string {
            $bugun = now();
            $prefix = sprintf('KP-%s-%s-%s-', $bugun->format('Y'), $bugun->format('m'), $bugun->format('d'));

            $sonBagis = Bagis::query()
                ->whereDate('created_at', $bugun->toDateString())
                ->where('bagis_no', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sira = 1;
            if ($sonBagis) {
                $parcalar = explode('-', $sonBagis->bagis_no);
                $sira = ((int) end($parcalar)) + 1;
            }

            return $prefix.str_pad((string) $sira, 4, '0', STR_PAD_LEFT);
        });
    }
}
