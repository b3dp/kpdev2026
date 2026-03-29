<?php

namespace App\Services;

use GuzzleHttp\Client;
use Throwable;

class GeminiService
{
    private Client $http;

    private array $tespitCache = [];

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com',
            'timeout' => 30,
        ]);
    }

    public function imlaDuzelt(string $metin): string
    {
        return $this->metinCevabiAl(
            "Aşağıdaki metinde sadece yazım ve imla hatalarını düzelt. Anlamı, üslubu ve cümle yapısını değiştirme:\n\n" . $metin,
            $metin
        );
    }

    public function ozetUret(string $metin): string
    {
        $varsayilan = $this->metniAnlamliSinirla((string) strip_tags($metin), 280);

        $yanit = $this->metinCevabiAl(
            "Aşağıdaki metin için Türkçe özet üret. En fazla 280 karakter olsun, cümle yarım kalmasın. "
            . "Gereksiz giriş cümlesi yazma, doğrudan özeti ver:\n\n" . $metin,
            $varsayilan
        );

        return $this->metniAnlamliSinirla($yanit, 280);
    }

    public function metaDescriptionUret(string $metin): string
    {
        $varsayilan = $this->metniAnlamliSinirla((string) strip_tags($metin), 150);

        $yanit = $this->metinCevabiAl(
            "Aşağıdaki metin için Türkçe SEO uyumlu meta description üret. En fazla 150 karakter olsun, "
            . "cümle yarım kalmasın:\n\n" . $metin,
            $varsayilan
        );

        return $this->metniAnlamliSinirla($yanit, 150);
    }

    public function seoBaslikUret(string $baslik): string
    {
        $temizBaslik = trim(preg_replace('/\s+/u', ' ', $baslik) ?? '');

        if ($temizBaslik === '') {
            return '';
        }

        if (mb_strlen($temizBaslik, 'UTF-8') <= 60) {
            return $temizBaslik;
        }

        $prompt = "Aşağıdaki haber başlığını SEO için 60 karakteri geçmeyecek "
            . "şekilde kısalt. Türkçe karakterleri koru. Anlam bütünlüğünü koru. "
            . "Sadece kısaltılmış başlığı yaz, başka hiçbir şey yazma:\n\n"
            . $temizBaslik;

        try {
            $response = $this->apiIstegiYap($prompt);
            $kisaBaslik = mb_substr(trim((string) $response), 0, 60, 'UTF-8');

            return filled($kisaBaslik)
                ? $kisaBaslik
                : mb_substr($temizBaslik, 0, 60, 'UTF-8');
        } catch (Throwable) {
            return mb_substr($temizBaslik, 0, 60, 'UTF-8');
        }
    }

    public function kisiTespitEt(string $metin): array
    {
        return $this->kisiVeKurumTespitEt($metin)['kisiler'] ?? [];
    }

    public function kurumTespitEt(string $metin): array
    {
        return $this->kisiVeKurumTespitEt($metin)['kurumlar'] ?? [];
    }

    private function kisiVeKurumTespitEt(string $metin): array
    {
        $anahtar = md5($metin);

        if (isset($this->tespitCache[$anahtar])) {
            return $this->tespitCache[$anahtar];
        }

        $sistemPrompt = <<<'PROMPT'
Sen Türkiye'deki dini ve eğitim kurumları hakkında haberler üreten bir derneğin
metin analiz asistanısın. Görevin verilen haber metnindeki KİŞİLERİ ve KURUMLARI
doğru tespit etmek.

=== KİŞİ TESPİT KURALLARI ===

TEMEL KURAL: Bir kişi olarak tespit edilmek için mutlaka AD + SOYAD birlikte olmalı.

UNVAN VE GÖREV LİSTESİ (bunlar isim DEĞİLDİR, gorev alanına yaz):
- Dini unvanlar: Hafız, Hafıza, Hoca, İmam, Müftü, Müezzin, Vaiz
- İdari görevler: Müdür, Yönetici, Koordinatör, Başkan, Başkanı
- Eğitim görevleri: Öğretici, Öğretmen, Eğitimci, Kursiyer
- Unvan önekleri: Hacı, Hoca, Doktor, Dr., Prof.
- Kurum içi görevler: Kurs Yöneticisi, Yurt Yöneticisi, Kur'an Kursu Yöneticisi

DOĞRU ÇIKARIM ÖRNEKLERİ:
✓ "Hafız Ömer Baydar" → ad_soyad: "Ömer Baydar", gorev: "Hafız"
✓ "Aliağa Müftüsü Ali Saim Doğru" → ad_soyad: "Ali Saim Doğru", gorev: "Aliağa Müftüsü"
✓ "Kur'an Kursu Yurt Yöneticisi Mustafa Aytekin" → ad_soyad: "Mustafa Aytekin", gorev: "Yurt Yöneticisi"
✓ "Mezunumuz Karabağlar Müftülüğü Kestanepazarı Hatay Kur'an Kursu Öğreticisi Hafız Enes Kaput" → ad_soyad: "Enes Kaput", gorev: "Hafız Öğretici"
✓ "Mezunumuz Karabağlar Müftülüğü Kestanepazarı Hatay Kur'an Kursu Yöneticisi Yasin Uslu" → ad_soyad: "Yasin Uslu", gorev: "Kur'an Kursu Yöneticisi"
✓ "Hacı Tülay Çolakoğlu Kur'an Kursu öğrencisi Mehmet Küççülü" → ad_soyad: "Mehmet Küççülü", gorev: "Öğrenci"

YANLIŞ ÇIKARIM ÖRNEKLERİ (bunları YAPMA):
✗ "Aliağa Müftüsü Ali" → Soyadı yok, çıkarma
✗ "Kursu Yurt Yöneticisi" → Kişi değil, görev tanımı
✗ "Kestanepazarı Hacı Tülay" → Kurum adının parçası, kişi değil
✗ "Kursu Öğreticisi Hafız" → Kişi değil, görev tanımı

EK KURALLAR:
- Aynı kişi metinde birden fazla geçiyorsa sadece bir kez ekle
- "Mezunumuz" ifadesi kişiyi değil mezun durumunu belirtir, gorev alanına ekleme
- Cami, kurum veya organizasyon adı geçen kısımlardan kişi çıkarma

=== KURUM TESPİT KURALLARI ===

TEMEL KURAL: Türkiye'deki resmi kurum ve kuruluşların TAM adını çıkar.

KURUM YAPILARI (bunları bir bütün olarak al):
- "X Müftülüğü" → tek kurum (Aliağa Müftülüğü, Karabağlar Müftülüğü)
- "X Müftülüğü Y Kur'an Kursu" → tek kurum
- "X Müftülüğü Y [Kişi Adı] Kur'an Kursu" → kişi adı içerse bile tek kurum
- "Kestanepazarı Öğrenci Yetiştirme Derneği" → tam adıyla tek kurum
- "X Camii/Camisinde" → cami adı mekan, kurum değil (çıkarma)

DOĞRU ÇIKARIM ÖRNEKLERİ:
✓ "Aliağa İlçe Müftülüğü" → tam kurum adı
✓ "Karabağlar Müftülüğü Kestanepazarı Hatay Kur'an Kursu" → tam kurum adı
✓ "Aliağa Müftülüğü Kestanepazarı Hacı Tülay Çolakoğlu Kur'an Kursu" → kişi adı içerse de tam kurum adı
✓ "Kestanepazarı Öğrenci Yetiştirme Derneği" → tam kurum adı

YANLIŞ ÇIKARIM ÖRNEKLERİ (bunları YAPMA):
✗ "Aliağa Müftülüğü Hacı" → kurum adının yarısı
✗ "Kestanepazarı Mezunları" → böyle bir kurum yok, uydurma
✗ "Hacı Tahir Şimşek Camii" → mekan, kurum değil
✗ "Kestanepazarı Öğrenci Yetiştirme" → eksik kurum adı, tam adı yaz

EK KURALLAR:
- Metinde geçmeyen kurum adı UYDURMA
- Aynı kurum farklı şekillerde geçiyorsa en tam halini kullan
- Kurum adlarını kısaltma veya değiştirme


=== KURUM TİPİ KURALLARI ===

Her kurum için uygun tipi belirle (sadece bu değerleri kullan):
- "X Müftülüğü" (ilçe/bölge adıyla) → tip: "muftuluk"
- "X İlçe Müftülüğü" → tip: "ilce_muftulugu"
- Bakanlık, Diyanet merkez kurumları → tip: "bakanlik"
- "X Kur'an Kursu" → tip: "kuran_kursu"
- "X İmam Hatip Okulu/Lisesi" → tip: "imam_hatip"
- "X Vakfı / X Vakıf" → tip: "vakif"
- "X Derneği / X Dernek" → tip: "dernek"
- "X Camii / X Cami" → tip: "cami"
- Diğer tüm kurumlar → tip: "diger"

ÖRNEKLER:
✓ "Aliağa İlçe Müftülüğü" → tip: "ilce_muftulugu"
✓ "Aliağa Müftülüğü Kestanepazarı Hacı Tülay Çolakoğlu Kur'an Kursu" → tip: "kuran_kursu"
✓ "Kestanepazarı Öğrenci Yetiştirme Derneği" → tip: "dernek"
✓ "Hacı Tahir Şimşek Camii" → tip: "cami" (eğer kurum olarak çıkardıysanız)

=== YANIT FORMATI ===

SADECE geçerli JSON döndür. Başında veya sonunda açıklama, markdown,
kod bloğu YAZMA. Direkt JSON ile başla:

{"kisiler":[{"ad_soyad":"...","gorev":"..."},{"ad_soyad":"...","gorev":""}],"kurumlar":[{"ad":"...","tip":"..."},{"ad":"...","tip":"..."}]}

=== HATA YÖNETİMİ ===
- JSON parse hatası olursa: {"kisiler": [], "kurumlar": []}
- Emin olmadığın varlığı ekleme, yanlış eklemek eksik bırakmaktan kötü
- gorev alanı yoksa boş string: ""
- tip alanı bilinmiyorsa: "diger"
PROMPT;

        $json = $this->jsonCevabiAl("Metni analiz et:\n\n" . $metin, $sistemPrompt);

        $kisiler = [];
        $kurumlar = [];

        if (! empty($json)) {
            $kisiler = $this->listeyiNormalizeEt($json, ['kisiler', 'people', 'kişiler']);
            $kurumlar = $this->listeyiNormalizeEt($json, ['kurumlar', 'institutions', 'organizations']);
        }

        $this->tespitCache[$anahtar] = [
            'kisiler' => $kisiler,
            'kurumlar' => $kurumlar,
        ];

        return $this->tespitCache[$anahtar];
    }

    private function metinCevabiAl(string $prompt, string $fallback): string
    {
        try {
            $metin = $this->apiIstegiYap($prompt);

            return filled($metin) ? trim($metin) : $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function jsonCevabiAl(string $prompt, ?string $sistemPrompt = null): array
    {
        try {
            $metin = $this->apiIstegiYap($prompt, $sistemPrompt);
            if (! filled($metin)) {
                return [];
            }

            $dogrudan = $this->jsonDecodeEt($metin);
            if ($dogrudan !== null) {
                return $dogrudan;
            }

            $jsonParcasi = $this->metindenJsonParcasiAyikla($metin);
            if (! filled($jsonParcasi)) {
                return [];
            }

            $ayiklanan = $this->jsonDecodeEt($jsonParcasi);

            return $ayiklanan ?? [];
        } catch (Throwable) {
            return [];
        }
    }

    private function jsonDecodeEt(string $icerik): ?array
    {
        $temiz = trim($icerik);
        $temiz = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $temiz) ?? $temiz;

        $json = json_decode(trim($temiz), true);

        return is_array($json) ? $json : null;
    }

    private function metindenJsonParcasiAyikla(string $metin): ?string
    {
        if (preg_match('/\[[\s\S]*\]/', $metin, $eslesen)) {
            return $eslesen[0];
        }

        if (preg_match('/\{[\s\S]*\}/', $metin, $eslesen)) {
            return $eslesen[0];
        }

        return null;
    }

    private function listeyiNormalizeEt(array $json, array $olasiListeAnahtarlari): array
    {
        if (array_is_list($json)) {
            return $json;
        }

        foreach ($olasiListeAnahtarlari as $anahtar) {
            $deger = $json[$anahtar] ?? null;
            if (is_array($deger) && array_is_list($deger)) {
                return $deger;
            }
        }

        if (! empty($json) && isset($json[0]) && is_array($json[0])) {
            return $json;
        }

        return [];
    }

    private function apiIstegiYap(string $prompt, ?string $sistemPrompt = null): ?string
    {
        $apiKey = config('services.gemini.api_key');
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');

        if (! filled($apiKey)) {
            return null;
        }

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        if (filled($sistemPrompt)) {
            $body['system_instruction'] = [
                'parts' => [
                    ['text' => $sistemPrompt],
                ],
            ];
        }

        $response = $this->http->post('/v1beta/models/' . $model . ':generateContent', [
            'query' => ['key' => $apiKey],
            'json' => $body,
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    private function metniAnlamliSinirla(string $metin, int $maxKarakter): string
    {
        $temiz = trim(preg_replace('/\s+/u', ' ', strip_tags($metin)) ?? '');
        if ($temiz === '') {
            return '';
        }

        if (mb_strlen($temiz) <= $maxKarakter) {
            return $temiz;
        }

        $cumleler = preg_split('/(?<=[.!?…])\s+/u', $temiz, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $sonuc = '';
        foreach ($cumleler as $cumle) {
            $cumle = trim($cumle);
            if ($cumle === '') {
                continue;
            }

            $aday = $sonuc === '' ? $cumle : $sonuc . ' ' . $cumle;
            if (mb_strlen($aday) > $maxKarakter) {
                break;
            }
            $sonuc = $aday;
        }

        if ($sonuc !== '') {
            return $sonuc;
        }

        $kirpilmis = trim(mb_substr($temiz, 0, $maxKarakter));
        $sonBosluk = mb_strrpos($kirpilmis, ' ');
        if ($sonBosluk !== false && $sonBosluk > 0) {
            $kirpilmis = trim(mb_substr($kirpilmis, 0, $sonBosluk));
        }

        $kirpilmis = rtrim($kirpilmis, " \t\n\r\0\x0B,;:-");
        if ($kirpilmis === '') {
            return trim(mb_substr($temiz, 0, $maxKarakter));
        }

        if (! preg_match('/[.!?…]$/u', $kirpilmis)) {
            if (mb_strlen($kirpilmis) + 1 <= $maxKarakter) {
                $kirpilmis .= '.';
            }
        }

        return $kirpilmis;
    }

    private function satirlardanVarlikListesiUret(string $metin, string $tip): array
    {
        $satirlar = preg_split('/\R+/u', trim($metin), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $liste = [];

        foreach ($satirlar as $satir) {
            $satir = trim(preg_replace('/^[-*\d.)\s]+/u', '', $satir) ?? '');
            if ($satir === '' || mb_strlen($satir) < 2) {
                continue;
            }

            if ($tip === 'kisi') {
                $parcalar = preg_split('/\s*[|\-–]\s*/u', $satir);
                $adSoyad = trim((string) ($parcalar[0] ?? ''));
                if ($adSoyad === '') {
                    continue;
                }
                $liste[] = [
                    'ad_soyad' => $adSoyad,
                    'rol' => isset($parcalar[1]) ? trim((string) $parcalar[1]) : null,
                ];
                continue;
            }

            $liste[] = ['ad' => $satir];
        }

        return $liste;
    }
}
