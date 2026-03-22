<?php

namespace App\Http\Controllers;

use App\Services\HaberOnayService;

class HaberOnayController extends Controller
{
    public function onayla(string $token, HaberOnayService $onayService)
    {
        $tokenKaydi = $onayService->tokenDogrula($token);

        if (! $tokenKaydi || $tokenKaydi->tip !== 'yayin') {
            abort(404, 'Geçersiz veya süresi dolmuş onay linki.');
        }

        $onayService->haberiYayinla($tokenKaydi->haber);

        $tokenKaydi->update(['kullanildi' => true]);

        return response('Haber yayına alındı.', 200);
    }

    public function reddet(string $token, HaberOnayService $onayService)
    {
        $tokenKaydi = $onayService->tokenDogrula($token);

        if (! $tokenKaydi || $tokenKaydi->tip !== 'red') {
            abort(404, 'Geçersiz veya süresi dolmuş red linki.');
        }

        $onayService->haberiReddet($tokenKaydi->haber, 'E-posta onay linki üzerinden reddedildi.');

        $tokenKaydi->update(['kullanildi' => true]);

        return response('Haber reddedildi.', 200);
    }
}
