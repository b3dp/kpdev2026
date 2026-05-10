<?php

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use App\Models\SmsKredi;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

class SmsKrediController extends Controller
{
    public function ekle(Request $request)
    {
        try {
            $request->validate([
                'miktar' => 'required|integer|min:1|max:999999999',
            ], [
                'miktar.required' => 'Kredi miktarı gereklidir.',
                'miktar.integer' => 'Kredi miktarı sayı olmalıdır.',
                'miktar.min' => 'Kredi miktarı en az 1 olmalıdır.',
            ]);

            $miktar = $request->input('miktar');
            
            SmsKredi::krediEkle($miktar, 'Admin Panel - Manual Kredi Ekleme');
            
            return redirect()
                ->route('filament.admin.pages.sms-yonetim-dashboard')
                ->with('success', "✅ {$miktar} kredi başarıyla eklendi!");
                
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SMS Kredi Ekleme Hatası:', [
                'hata' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->route('filament.admin.pages.sms-yonetim-dashboard')
                ->with('error', '❌ Kredi ekleme sırasında bir hata oluştu. Lütfen tekrar deneyin.');
        }
    }
}
