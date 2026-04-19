<?php

namespace App\Services;

use App\Models\KurbanKayit;
use Illuminate\Support\Facades\Log;
use Throwable;

class KurbanNoService
{
    public function uret(int $offset = 0): string
    {
        try {
            $bugun = now();
            $prefix = sprintf('KRB-%s-%s-%s-', $bugun->format('Y'), $bugun->format('m'), $bugun->format('d'));

            $sonKurban = KurbanKayit::query()
                ->whereDate('created_at', $bugun->toDateString())
                ->where('kurban_no', 'like', $prefix.'%')
                ->orderByDesc('id')
                ->first();

            $sira = 1;
            if ($sonKurban) {
                $parcalar = explode('-', $sonKurban->kurban_no);
                $sira = ((int) end($parcalar)) + 1;
            }

            $sira += max($offset, 0);

            return $prefix.str_pad((string) $sira, 4, '0', STR_PAD_LEFT);
        } catch (Throwable $exception) {
            Log::error('Kurban numarası üretilemedi.', [
                'hata' => $exception->getMessage(),
            ]);

            return 'KRB-'.now()->format('Y-m-d-His');
        }
    }
}