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

            // Yöneticilere ilet
            if (filled($aliciEposta)) {
                app(ZeptomailService::class)->yoneticiAlertGonder([
                    [
                        'eposta' => $aliciEposta,
                        'ad' => config('site.ad') . ' İletişim',
                    ],
                ], $konu, $mesaj);
            }

            // Formu dolduran kişiye teşekkür maili gönder
            try {
                $tesekkurIcerik = view('emails.iletisim_tesekkur', [
                    'ad' => $veri['ad'],
                    'soyad' => $veri['soyad'],
                    'konu' => $veri['konu'],
                    'mesaj' => $veri['mesaj'],
                ])->render();
                app(ZeptomailService::class)->gonderTemel(
                    $veri['eposta'],
                    $veri['ad'] . ' ' . $veri['soyad'],
                    'Kestanepazarı İletişim Formu',
                    $tesekkurIcerik,
                    'default',
                    'iletisim_tesekkur',
                );
            } catch (\Throwable $e) {
                Log::error('İletişim formu teşekkür maili gönderilemedi.', [
                    'hata' => $e->getMessage(),
                    'eposta' => $veri['eposta'],
                ]);
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
                'ad' => 'Kestanepazarı Öğrenci Yetiştirme Derneği Merkez',
                'kisa_ad' => 'Merkez',
                'baslik' => 'Kestanepazarı Öğrenci Yetiştirme Derneği Merkez',
                'adres' => '872. Sk. No:52, 35250 Konak/İzmir',
                'eposta' => config('iletisim.merkez_eposta'),
                'yon_tarifi_url' => 'https://share.google/J7Zdte2AcmbN1BHpZ',
                'harita_url' => 'https://www.google.com/maps?q=' . urlencode('872. Sk. No:52, 35250 Konak/İzmir') . '&z=16&output=embed',
            ],
            [
                'kod' => '02',
                'ad' => 'Kestanepazarı Hatay Kampüs',
                'kisa_ad' => 'Hatay Kampüs',
                'baslik' => 'Kestanepazarı Hatay Kampüs',
                'adres' => 'Adnan Süvari, 175/1. Sk. No:8, 35140 Karabağlar/İzmir',
                'eposta' => config('iletisim.merkez_eposta'),
                'yon_tarifi_url' => 'https://share.google/u06q4MFs0venDIhfj',
                'harita_url' => 'https://www.google.com/maps?q=' . urlencode('Adnan Süvari, 175/1. Sk. No:8, 35140 Karabağlar/İzmir') . '&z=16&output=embed',
            ],
            [
                'kod' => '03',
                'ad' => 'Kestanepazarı Hatay Kur\'an Kursu',
                'kisa_ad' => 'Hatay Kur\'an Kursu',
                'baslik' => 'Kestanepazarı Hatay Kur\'an Kursu',
                'adres' => 'Adnan Süvari, 175/1. Sk. No:8, 35140 Karabağlar/İzmir',
                'eposta' => config('iletisim.merkez_eposta'),
                'yon_tarifi_url' => 'https://share.google/u06q4MFs0venDIhfj',
                'harita_url' => 'https://www.google.com/maps?q=' . urlencode('Adnan Süvari, 175/1. Sk. No:8, 35140 Karabağlar/İzmir') . '&z=16&output=embed',
            ],
            [
                'kod' => '04',
                'ad' => 'Kestanepazarı Hatay Ortaöğretim Erkek Öğrenci Yurdu',
                'kisa_ad' => 'Hatay Yurt',
                'baslik' => 'Kestanepazarı Hatay Ortaöğretim Erkek Öğrenci Yurdu',
                'adres' => 'Adnan Süvari, 175/3. Sk. No:26E, 35140 Karabağlar/İzmir',
                'eposta' => config('iletisim.merkez_eposta'),
                'yon_tarifi_url' => 'https://share.google/Nir82V0jmN4gVaXAc',
                'harita_url' => 'https://www.google.com/maps?q=' . urlencode('Adnan Süvari, 175/3. Sk. No:26E, 35140 Karabağlar/İzmir') . '&z=16&output=embed',
            ],
            [
                'kod' => '05',
                'ad' => 'Kestanepazarı Merkez Kur\'an Kursu',
                'kisa_ad' => 'Merkez Kur\'an Kursu',
                'baslik' => 'Kestanepazarı Merkez Kur\'an Kursu',
                'adres' => '872. Sk. No:52, 35250 Konak/İzmir',
                'eposta' => config('iletisim.merkez_eposta'),
                'yon_tarifi_url' => 'https://share.google/J7Zdte2AcmbN1BHpZ',
                'harita_url' => 'https://www.google.com/maps?q=' . urlencode('872. Sk. No:52, 35250 Konak/İzmir') . '&z=16&output=embed',
            ],
            [
                'kod' => '06',
                'ad' => 'Kestanepazarı Hacı Tülay Çolakoğlu Kur\'an Kursu',
                'kisa_ad' => 'Hacı Tülay Çolakoğlu',
                'baslik' => 'Kestanepazarı Hacı Tülay Çolakoğlu Kur\'an Kursu',
                'adres' => 'Yenişakran Hasbi Efendi Mah, Mimar Sinan Cd. No: 37, 35810 Aliağa/İzmir',
                'eposta' => config('iletisim.merkez_eposta'),
                'yon_tarifi_url' => 'https://share.google/lmrzfemzvenYpNQF4',
                'harita_url' => 'https://www.google.com/maps?q=' . urlencode('Yenişakran Hasbi Efendi Mah, Mimar Sinan Cd. No: 37, 35810 Aliağa/İzmir') . '&z=16&output=embed',
            ],
        ]);
    }
}
