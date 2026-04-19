<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZeptomailService;
use Illuminate\Support\Facades\Log;

class TestMailTumSablonlar extends Command
{
    protected $signature = 'test:mail-tum-sablonlar {eposta}';
    protected $description = 'Tüm e-posta şablonlarından örnek gönderir';

    public function handle()
    {
        $eposta = $this->argument('eposta');
        $service = app(ZeptomailService::class);
        $sonuclar = [];

        // Örnek veriler
        $vars = [
            'ad' => 'Barış',
            'soyad' => 'Deneme',
            'adSoyad' => 'Barış Deneme',
            'konu' => 'Test Konusu',
            'mesaj' => 'Bu bir test mesajıdır.',
            'bagisNo' => 'BG20260001',
            'hataMesaji' => 'Ödeme reddedildi.',
            'tarihIso' => now()->toIso8601String(),
            'makbuzUrl' => 'https://kestanepazari.org.tr/makbuz/test',
            'tutar' => 500,
            'bagisSlug' => 'fitre',
            'gorselUrl' => 'https://cdn.kestanepazari.org.tr/test.jpg',
            'periyot' => 'Nisan 2026',
            'tarihAraligi' => '01.04.2026 - 30.04.2026',
            'driveUrl' => 'https://drive.google.com/test',
            'veliAdSoyad' => 'Veli Deneme',
            'ogrenciAdSoyad' => 'Öğrenci Deneme',
            'sinif' => '5A',
            'kurum' => 'Kestanepazarı',
            'durum' => 'Onaylandı',
            'evrakUrl' => 'https://cdn.kestanepazari.org.tr/evrak.pdf',
            'durumNotu' => 'Başvurunuz onaylanmıştır.',
            'kayitNo' => 'EK20260001',
            'sinifAd' => '5A',
            'haberBaslik' => 'Test Haberi',
            'haberKategori' => 'Duyuru',
            'kisiler' => 'Barış Deneme',
            'kurumlar' => 'Kestanepazarı',
            'haberIcerik' => 'Bu bir test haberidir.',
            'yayinlaUrl' => 'https://kestanepazari.org.tr/haber/yayinla',
            'duzenleUrl' => 'https://kestanepazari.org.tr/haber/duzenle',
            'kurbanNo' => 'KR20260001',
            'kesimTarihi' => '19.04.2026',
            'redNotu' => 'Eksik belge.',
            'kod' => '123456',
            'islemAdi' => 'Giriş',
            'gecerlilik' => '10 dakika',
            'link' => 'https://kestanepazari.org.tr/sifre-sifirla',
            'girisLink' => 'https://kestanepazari.org.tr/giris',
            'tarih' => '19.04.2026 14:00',
        ];

        $sablonlar = [
            'bagis_hatasi' => function() use ($vars) { try { return view('emails.bagis_hatasi', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'bagis_makbuz' => function() use ($vars) { try { return view('emails.bagis_makbuz', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'bagis_rapor' => function() use ($vars) { try { return view('emails.bagis_rapor', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'ekayit-durum' => function() use ($vars) { try { return view('emails.ekayit-durum', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'ekayit_onay' => function() use ($vars) { try { return view('emails.ekayit_onay', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'haber_onay' => function() use ($vars) { try { return view('emails.haber_onay', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'iletisim_tesekkur' => function() use ($vars) { try { return view('emails.iletisim_tesekkur', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'kurban_kesildi' => function() use ($vars) { try { return view('emails.kurban_kesildi', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'mezun_onaylandi' => function() use ($vars) { try { return view('emails.mezun_onaylandi', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'mezun_reddedildi' => function() use ($vars) { try { return view('emails.mezun_reddedildi', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'otp' => function() use ($vars) { try { return view('emails.otp', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'otp_giris' => function() use ($vars) { try { return view('emails.otp_giris', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'otp_kayit' => function() use ($vars) { try { return view('emails.otp_kayit', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'sifre_sifirlama' => function() use ($vars) { try { return view('emails.sifre_sifirlama', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'uye_kayit_onay' => function() use ($vars) { try { return view('emails.uye_kayit_onay', $vars)->render(); } catch (\Throwable $e) { return ''; } },
            'yonetici_alert' => function() use ($vars) { try { return view('emails.yonetici_alert', ['konu'=>'Test Yön.','mesaj'=>$vars['mesaj']])->render(); } catch (\Throwable $e) { return ''; } },
        ];

        foreach ($sablonlar as $sablon => $fn) {
            try {
                $icerik = $fn();
                $service->gonderTemel(
                    $eposta,
                    'Test Kullanıcı',
                    'Test: ' . $sablon,
                    $icerik,
                    'default',
                    $sablon
                );
                $sonuclar[] = "$sablon: OK";
            } catch (\Throwable $e) {
                Log::error('TestMailTumSablonlar hata', ['sablon'=>$sablon, 'hata'=>$e->getMessage()]);
                $sonuclar[] = "$sablon: HATA";
            }
        }
        $this->info(implode("\n", $sonuclar));
    }
}
