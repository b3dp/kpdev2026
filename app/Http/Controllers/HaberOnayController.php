<?php

namespace App\Http\Controllers;

use App\Enums\HaberDurumu;
use App\Models\Haber;
use App\Services\HaberOnayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function yayinla(Request $request, Haber $haber)
    {
        $token = $request->query('token');
        $duzenleUrl = config('app.url') . '/yonetim/haberler/' . $haber->id . '/edit';

        // Token kontrolü
        if (! $token || $haber->onay_token !== $token) {
            return redirect($duzenleUrl)
                ->with('warning', 'Geçersiz veya kullanılmış token.');
        }

        // Süre kontrolü
        if (! $haber->onay_token_expires_at || $haber->onay_token_expires_at->isPast()) {
            return redirect($duzenleUrl)
                ->with('warning', 'Token süresi dolmuş. Lütfen panelden yayına alın.');
        }

        // Yayına al
        $haber->update([
            'durum' => HaberDurumu::Yayinda,
            'yayin_tarihi' => $haber->yayin_tarihi ?? now(),
            'onay_token' => null,
            'onay_token_expires_at' => null,
        ]);

        Log::info('[HaberOnayController] Token ile yayına alındı', [
            'haber_id' => $haber->id,
        ]);

        return view('haber-onay-basarili', ['haber' => $haber]);
    }
}
