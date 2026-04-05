<?php

namespace App\Http\Controllers;

use App\Enums\KurumsalSablonu;
use App\Models\KurumsalSayfa;
use App\Services\ZeptomailService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IletisimController extends Controller
{
    public function index()
    {
        $sayfa = KurumsalSayfa::query()
            ->where('durum', 'yayinda')
            ->where('sablon', KurumsalSablonu::Iletisim->value)
            ->with(['lokasyonlar' => fn ($query) => $query->orderBy('sira')])
            ->orderBy('sira')
            ->first();

        if (! $sayfa) {
            $sayfa = new KurumsalSayfa([
                'ad' => 'İletişim',
                'slug' => 'iletisim',
                'sablon' => KurumsalSablonu::Iletisim->value,
                'icerik' => 'Soru, öneri ve iş birliği talepleriniz için bizimle iletişime geçebilirsiniz. Ekibimiz size en kısa sürede dönüş yapacaktır.',
                'ozet' => 'Kestanepazarı iletişim sayfasında merkez bilgilerini, şubeleri ve iletişim formunu bulabilirsiniz.',
                'meta_description' => 'Kestanepazarı iletişim bilgileri, şubeler, e-posta adresi ve iletişim formu bu sayfada yer alır.',
                'durum' => 'yayinda',
            ]);
            $sayfa->setRelation('lokasyonlar', collect());
        }

        $lokasyonlar = $sayfa->lokasyonlar
            ->map(function ($lokasyon, int $index): array {
                $sorgu = $lokasyon->konum_lat && $lokasyon->konum_lng
                    ? $lokasyon->konum_lat . ',' . $lokasyon->konum_lng
                    : ($lokasyon->konum_place_id ?: ($lokasyon->lokasyon_adi . ' ' . config('site.adres')));

                return [
                    'kod' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'ad' => $lokasyon->lokasyon_adi,
                    'kisa_ad' => Str::limit((string) $lokasyon->adres, 30, '...'),
                    'baslik' => $lokasyon->lokasyon_adi,
                    'adres' => $lokasyon->adres,
                    'eposta' => $lokasyon->eposta ?: config('iletisim.merkez_eposta'),
                    'yon_tarifi_url' => 'https://maps.google.com/?q=' . urlencode((string) $sorgu),
                    'harita_url' => 'https://www.google.com/maps?q=' . urlencode((string) $sorgu) . '&z=15&output=embed',
                ];
            })
            ->values();

        if ($lokasyonlar->isEmpty()) {
            $lokasyonlar = $this->varsayilanLokasyonlar();
        }

        return view('pages.iletisim', compact('sayfa', 'lokasyonlar'));
    }

    public function store(Request $request)
    {
        $veri = $request->validate([
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'eposta' => ['required', 'email', 'max:150'],
            'telefon' => ['nullable', 'string', 'max:30'],
            'konu' => ['required', 'string', 'max:120'],
            'lokasyon' => ['nullable', 'string', 'max:150'],
            'mesaj' => ['required', 'string', 'min:10', 'max:3000'],
            'kvkk' => ['accepted'],
        ], [
            'ad.required' => 'Ad alanı zorunludur.',
            'soyad.required' => 'Soyad alanı zorunludur.',
            'eposta.required' => 'E-posta alanı zorunludur.',
            'eposta.email' => 'Geçerli bir e-posta adresi girin.',
            'konu.required' => 'Lütfen bir konu seçin.',
            'mesaj.required' => 'Mesaj alanı zorunludur.',
            'mesaj.min' => 'Mesajınız en az 10 karakter olmalıdır.',
            'kvkk.accepted' => 'KVKK onayı gereklidir.',
        ]);

        try {
            $lokasyon = $veri['lokasyon'] ?: 'Belirtilmedi';
            $konu = 'Web İletişim Formu - ' . $veri['konu'];
            $mesaj = "Ad Soyad: {$veri['ad']} {$veri['soyad']}\n"
                . "E-posta: {$veri['eposta']}\n"
                . 'Telefon: ' . ($veri['telefon'] ?: 'Belirtilmedi') . "\n"
                . "Lokasyon: {$lokasyon}\n\n"
                . "Mesaj:\n{$veri['mesaj']}";

            Log::info('İletişim formu mesajı alındı.', [
                'konu' => $veri['konu'],
                'eposta' => $veri['eposta'],
                'lokasyon' => $lokasyon,
                'ip' => $request->ip(),
            ]);

            $aliciEposta = config('iletisim.merkez_eposta') ?: config('site.eposta');

            if (filled($aliciEposta)) {
                app(ZeptomailService::class)->yoneticiAlertGonder([
                    [
                        'eposta' => $aliciEposta,
                        'ad' => config('site.ad') . ' İletişim',
                    ],
                ], $konu, $mesaj);
            }

            return redirect()
                ->route('iletisim.index')
                ->with('success', 'Mesajınız başarıyla iletildi. En kısa sürede size dönüş yapacağız.');
        } catch (\Throwable $exception) {
            Log::error('İletişim formu gönderilemedi.', [
                'hata' => $exception->getMessage(),
                'eposta' => $veri['eposta'] ?? null,
                'ip' => $request->ip(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Mesajınız gönderilirken bir sorun oluştu. Lütfen tekrar deneyin.');
        }
    }

    private function varsayilanLokasyonlar(): Collection
    {
        return collect([
            [
                'kod' => '01',
                'ad' => 'Genel Merkez',
                'kisa_ad' => 'Kestanepazarı',
                'baslik' => config('site.ad') . ' Öğrenci Yetiştirme Derneği',
                'adres' => config('site.adres'),
                'eposta' => config('iletisim.merkez_eposta'),
                'yon_tarifi_url' => 'https://maps.google.com/?q=' . urlencode(config('site.adres')),
                'harita_url' => 'https://www.google.com/maps?q=' . urlencode(config('site.adres')) . '&z=15&output=embed',
            ],
            [
                'kod' => '02',
                'ad' => 'Seferihisar Şubesi',
                'kisa_ad' => 'İlçe Merkezi',
                'baslik' => 'Seferihisar İrtibat Ofisi',
                'adres' => 'Seferihisar merkezinde öğrenci ve veli görüşmeleri için kullanılan irtibat noktası.',
                'eposta' => config('iletisim.merkez_eposta'),
                'yon_tarifi_url' => 'https://maps.google.com/?q=' . urlencode('Seferihisar İzmir'),
                'harita_url' => 'https://www.google.com/maps?q=' . urlencode('Seferihisar İzmir') . '&z=14&output=embed',
            ],
            [
                'kod' => '03',
                'ad' => 'Öğrenci Yurdu',
                'kisa_ad' => 'Konaklama',
                'baslik' => 'Öğrenci Destek ve Barınma Birimi',
                'adres' => 'Öğrenci yurdu, barınma ve rehberlik süreçleri için bilgilendirme noktası.',
                'eposta' => config('iletisim.merkez_eposta'),
                'yon_tarifi_url' => 'https://maps.google.com/?q=' . urlencode('Kestanepazarı Seferihisar öğrenci yurdu'),
                'harita_url' => 'https://www.google.com/maps?q=' . urlencode('Kestanepazarı Seferihisar öğrenci yurdu') . '&z=14&output=embed',
            ],
        ]);
    }
}
