# Kestanepazarı — VSCode Copilot SEO Prompt Dosyası

Bu dosyayı VSCode Copilot Chat'e vererek tüm AEO (AI arama optimizasyonu)
düzeltmelerini otomatik uygulatabilirsiniz.

**Kullanım:**
```
@workspace bu dosyadaki talimatları uygula
```

Belirli bir sayfayı uygulatmak için:
```
@workspace bu dosyadaki "SAYFA 1: Ana Sayfa" bölümünü uygula
```

---

## GENEL KURALLAR (tüm sayfalarda geçerli)

- Mevcut kodu bozmadan sadece belirtilen değişiklikleri yap
- Her değişikliğin yanına Türkçe yorum satırı ekle
- JSON-LD üretimini her zaman PHP array + json_encode yöntemiyle üret
- `@@type` hatası oluşmaması için `@type` değerlerini doğrudan blade içinde yazma
- Her zaman `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT` flag'lerini kullan
- `config('app.url')` kullan, sabit URL yazma
- Null güvenliği için `??` operatörünü kullan
- Değişiklik yaptığın her dosyayı ve satır numarasını belirt
- Organization adı her yerde şu olmalı: `"Kestanepazarı Öğrenci Yetiştirme Derneği"`

---

## SAYFA 1: Ana Sayfa

**Kapsam:** `resources/views/pages/index.blade.php` ve `resources/views/layouts/app.blade.php`

### Görev 1.1 — Çift WebSite Schema Temizliği

`app.blade.php` içinde layout'tan gelen bir WebSite schema var.
`index.blade.php` içinde sayfa bazlı olarak aynı WebSite schema tekrar eklenmiş.

Yapılacak:
- `index.blade.php` içindeki WebSite schema bloğunu tamamen kaldır
- `app.blade.php`'deki WebSite schema'yı koru, dokunma

### Görev 1.2 — Organization Adı Düzeltmesi

`app.blade.php` içindeki Organization schema'da:
- YANLIŞ: `"Kestanepazarı Öğrenci Yetiştirme Derneği Öğrenci Yetiştirme Derneği"`
- DOĞRU: `"Kestanepazarı Öğrenci Yetiştirme Derneği"`

WebSite schema'daki name alanında da aynı tekrar var, onu da düzelt.

### Görev 1.3 — Ana Sayfadan BreadcrumbList Kaldır

Ana sayfada tek öğeli BreadcrumbList değersiz. Ana sayfada BreadcrumbList çıkmasın.
Diğer sayfalarda çıkmaya devam etmeli.

`app.blade.php`'de BreadcrumbList üretim koduna şu kontrolü ekle:

```php
@if(!request()->is('/'))
    {{-- BreadcrumbList schema buraya --}}
@endif
```

### Görev 1.4 — Ana Sayfa WebPage Schema'sını Zenginleştir

`index.blade.php` içindeki WebPage schema'yı PHP array yöntemiyle yeniden yaz:

```php
@php
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'Ana Sayfa — Kestanepazarı',
    'description' => 'İzmir Karabağlar\'da faaliyet gösteren Kestanepazarı Öğrenci Yetiştirme Derneği — eğitim, etkinlik ve bağış kampanyaları.',
    'url' => config('app.url'),
    'inLanguage' => 'tr-TR',
    'isPartOf' => [
        '@type' => 'WebSite',
        '@id' => config('app.url') . '/#website',
    ],
    'about' => [
        '@type' => 'Organization',
        '@id' => config('app.url') . '/#organization',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Kestanepazarı Öğrenci Yetiştirme Derneği',
    ],
];
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
```

### Görev 1.5 — Organization ve WebSite Schema'ya @id Ekle

`app.blade.php`'deki Organization schema'ya `@id` ekle:
```json
"@id": "{{ config('app.url') }}/#organization"
```

WebSite schema'ya da `@id` ekle:
```json
"@id": "{{ config('app.url') }}/#website"
```

### Görev 1.6 — og:description İyileştirmesi

`index.blade.php`'de og:description ve meta description şu an çok kısa.
Bunu güncelle:
```
İzmir Karabağlar'da faaliyet gösteren Kestanepazarı Öğrenci Yetiştirme Derneği — eğitim, etkinlik ve bağış kampanyaları.
```

---

## SAYFA 2: Haber Liste

**Kapsam:** `resources/views/pages/haberler/index.blade.php`

### Görev 2.1 — CollectionPage + ItemList Schema Ekle

Haber liste sayfasında sayfa bazlı schema boş. Aşağıdaki yapıyı ekle:

```php
@php
$itemListElements = [];
foreach ($haberler as $index => $haber) {
    $itemListElements[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => route('haberler.detay', $haber->slug),
        'name' => $haber->baslik,
        'image' => $haber->kapak_resim ?? '',
        'datePublished' => $haber->created_at->toIso8601String(),
    ];
}

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'Haberler — Kestanepazarı',
    'description' => 'Kestanepazarı Öğrenci Yetiştirme Derneği haberleri ve duyuruları',
    'url' => url()->current(),
    'inLanguage' => 'tr-TR',
    'isPartOf' => [
        '@type' => 'WebSite',
        '@id' => config('app.url') . '/#website',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Kestanepazarı Öğrenci Yetiştirme Derneği',
    ],
    'mainEntity' => [
        '@type' => 'ItemList',
        'name' => 'Haberler',
        'numberOfItems' => $haberler->total() ?? count($haberler),
        'itemListElement' => $itemListElements,
    ],
];
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
```

Notlar:
- `$haberler` değişken adını controller'dan kontrol et, farklıysa güncelle
- `route('haberler.detay', $haber->slug)` route adını `routes/web.php`'den kontrol et
- `$haber->kapak_resim` null olabilir, `?? ''` ile koru

### Görev 2.2 — WebPage Schema'yı Zenginleştir

Mevcut WebPage schema'yı PHP array yöntemiyle yeniden yaz:

```php
@php
$sayfaSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'Haberler — Kestanepazarı',
    'description' => 'Kestanepazarı Öğrenci Yetiştirme Derneği haberleri ve duyuruları',
    'url' => url()->current(),
    'inLanguage' => 'tr-TR',
    'isPartOf' => [
        '@type' => 'WebSite',
        '@id' => config('app.url') . '/#website',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Kestanepazarı Öğrenci Yetiştirme Derneği',
    ],
];
@endphp
<script type="application/ld+json">
{!! json_encode($sayfaSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
```

Not: Bu schema layout'tan geliyorsa orada güncelle, sayfa blade'inden geliyorsa burada.

### Görev 2.3 — og:image Düzeltmesi

`index.blade.php`'deki `og:image` şu an haber detay sayfasının kapak resmini gösteriyor.
Liste sayfası için genel OG görseli kullan:
- `config('app.url') . '/img/og-haberler.jpg'` varsa onu kullan
- Yoksa `config('app.url') . '/img/og-default.jpg'` kullan

---

## SAYFA 3: Haber Detay

**Kapsam:** `resources/views/pages/haberler/detay.blade.php` ve `resources/views/layouts/app.blade.php`

### Görev 3.1 — Çift NewsArticle Schema Temizliği

Haber detay sayfasında iki ayrı NewsArticle bloğu var:
- Biri `app.blade.php` içinde route bazlı üretiliyor
- Diğeri `detay.blade.php` içinde sayfa bazlı üretiliyor

Yapılacak:
- `app.blade.php` içindeki route bazlı NewsArticle bloğunu kaldır
- `detay.blade.php` içindeki NewsArticle bloğunu koru ve Görev 3.2'deki gibi zenginleştir
- Diğer sayfa tipleri için `app.blade.php`'deki route bazlı schema mantığına dokunma

### Görev 3.2 — NewsArticle Schema'yı Birleştir ve Zenginleştir

`detay.blade.php` içindeki NewsArticle schema'yı aşağıdaki yapıya getir:

```php
@php
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $haber->baslik,
    'description' => $haber->ozet ?? '',
    'url' => url()->current(),
    'image' => [$haber->kapak_resim ?? ''],
    'datePublished' => $haber->created_at->toIso8601String(),
    'dateModified' => $haber->updated_at->toIso8601String(),
    'inLanguage' => 'tr-TR',
    'wordCount' => str_word_count(strip_tags($haber->icerik ?? '')),
    'timeRequired' => 'PT' . max(1, (int) ceil(str_word_count(strip_tags($haber->icerik ?? '')) / 200)) . 'M',
    'articleSection' => $haber->kategori->ad ?? '',
    'keywords' => $haber->etiketler->pluck('ad')->toArray() ?? [],
    'author' => [
        '@type' => 'Person',
        'name' => $haber->yazar->name ?? 'Kestanepazarı Öğrenci Yetiştirme Derneği',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Kestanepazarı Öğrenci Yetiştirme Derneği',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => 'https://cdn.kestanepazari.org.tr/logo.png',
        ],
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => url()->current(),
    ],
    'isPartOf' => [
        '@type' => 'WebSite',
        '@id' => config('app.url') . '/#website',
    ],
    'speakable' => [
        '@type' => 'SpeakableSpecification',
        'cssSelector' => ['h1', '.haber-ozet'],
    ],
];
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
```

Notlar:
- `$haber->yazar` ilişkisi yoksa `'Kestanepazarı Öğrenci Yetiştirme Derneği'` yaz
- `$haber->etiketler` ilişkisi yoksa boş array `[]` kullan
- `$haber->kapak_resim` null olabilir, `?? ''` ile koru
- `wordCount` ve `timeRequired` dinamik hesaplansın

### Görev 3.3 — BreadcrumbList Son Öğesini Düzelt

`app.blade.php`'de BreadcrumbList son öğesinin name alanı slug formatında geliyor.
Haber detay sayfasında son öğe için `$haber->baslik` kullan.
Diğer sayfa tipleri için de aynı mantığı uygula:
- Etkinlik detay → `$etkinlik->baslik`
- Kurumsal detay → `$sayfa->baslik`
- Bağış detay → `$bagis->baslik`
- Değişken mevcut değilse: `Str::title(str_replace('-', ' ', $segment))`

### Görev 3.4 — Organization @id Kontrolü

`app.blade.php`'deki Organization schema'da `@id` var mı kontrol et.
Yoksa ekle: `"@id": "{{ config('app.url') }}/#organization"`

WebSite schema'da `@id` var mı kontrol et.
Yoksa ekle: `"@id": "{{ config('app.url') }}/#website"`

---

## GLOBAL DÜZELTMELER

### Görev G.1 — robots.txt

`public/robots.txt` dosyasını oluştur veya güncelle:

```
User-agent: *
Allow: /
Disallow: /admin
Disallow: /api

User-agent: GPTBot
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: anthropic-ai
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: GoogleOther
Allow: /

Sitemap: https://kestanepazari.org.tr/sitemap.xml
```

### Görev G.2 — llms.txt

`resources/views/llms.blade.php` oluştur ve `routes/web.php`'ye route ekle:

```php
Route::get('/llms.txt', function () {
    return response(view('llms'), 200, ['Content-Type' => 'text/plain']);
});
```

`llms.blade.php` içeriği:

```
# Kestanepazarı Öğrenci Yetiştirme Derneği

> İzmir Karabağlar'da faaliyet gösteren eğitim ve öğrenci yetiştirme derneği.

## Önemli Sayfalar
- [Ana Sayfa](/): Dernek ana sayfası
- [Haberler](/haberler): Güncel haberler ve duyurular
- [Etkinlikler](/etkinlikler): Düzenlenen etkinlikler
- [Hakkımızda](/hakkimizda): Dernek hakkında bilgi
- [Bağış](/bagis): Destek olun
- [İletişim](/iletisim): İletişim bilgileri

## İçerik Dili
Tüm içerikler Türkçedir.

## Erişim
Tüm sayfalar herkese açıktır.
```

---

*Bu dosya tüm sayfa tipleri tamamlandıkça güncellenecektir.*
*Mevcut kapsam: Ana Sayfa, Haber Liste, Haber Detay, Global Düzeltmeler*
