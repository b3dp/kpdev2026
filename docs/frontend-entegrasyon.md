# Faz 11 — Frontend Entegrasyon

## Genel Bilgi

| Alan | Değer |
|---|---|
| Görev | HTML mockup'larını Laravel Blade'e çevirme + backend entegrasyonu |
| Framework | Laravel 12 + Filament 3.3 + Tailwind CSS v4 |
| PHP | /opt/plesk/php/8.2/bin/php |
| Web Root | /var/www/vhosts/2026.kestanepazari.org.tr/httpdocs |
| CDN | https://cdn.kestanepazari.org.tr (DO Spaces) |

---

## Tasarım Sistemi

### Fontlar (Google Fonts)
- Başlıklar: 'Libre Baskerville', serif — 700, 700 italic
- UI/Gövde: 'Plus Jakarta Sans', sans-serif — 400, 500, 600, 700

### Renkler (tailwind.config.js)
```js
colors: {
  primary: '#162E4B',
  'primary-dark': '#091420',
  accent: '#B27829',
  'accent-bright': '#FF9300',
  teal: '#28484C',
  'teal-muted': '#62868D',
  cream: '#EBDFB5',
  'orange-cta': '#E95925',
  bg: '#FFFFFF',
  'bg-soft': '#F7F5F0',
}
```

### Tipografi
- H1, H2, H3: Libre Baskerville, font-bold
- Gövde, nav, buton, form: Plus Jakarta Sans
- CTA butonu: orange-cta arka plan, primary-dark yazı, font-semibold
- Link hover: accent-bright

---

## config/site.php

```php
return [
    'ad'        => env('SITE_ADI', 'Kestanepazarı'),
    'aciklama'  => env('SITE_ACIKLAMA', 'Öğrenci Yetiştirme Derneği'),
    'telefon'   => env('ILETISIM_TELEFON', '444 9 232'),
    'eposta'    => env('ILETISIM_EPOSTA', 'info@kestanepazari.org.tr'),
    'adres'     => env('ILETISIM_ADRES', 'Seferihisar, İzmir'),
    'facebook'  => env('SOSYAL_FACEBOOK', ''),
    'instagram' => env('SOSYAL_INSTAGRAM', ''),
    'x'         => env('SOSYAL_X', ''),
];
```

### .env Eklenecekler
```env
SITE_ADI="Kestanepazarı"
SITE_ACIKLAMA="Öğrenci Yetiştirme Derneği"
ILETISIM_TELEFON="444 9 232"
ILETISIM_EPOSTA="info@kestanepazari.org.tr"
ILETISIM_ADRES="Seferihisar, İzmir"
SOSYAL_FACEBOOK=""
SOSYAL_INSTAGRAM=""
SOSYAL_X=""
```

---

## Blade Yapısı

```
resources/views/
├── layouts/
│   └── app.blade.php
├── components/
│   ├── header.blade.php
│   ├── footer.blade.php
│   └── sidebar.blade.php
└── pages/
    ├── index.blade.php
    ├── arama.blade.php
    ├── haberler/
    │   ├── index.blade.php
    │   └── detay.blade.php
    ├── bagis/
    │   ├── index.blade.php
    │   ├── detay.blade.php
    │   └── tesekkur.blade.php
    ├── etkinlikler/
    │   ├── index.blade.php
    │   └── detay.blade.php
    ├── kurumsal/
    │   ├── index.blade.php
    │   └── iletisim.blade.php
    ├── mezunlar/
    │   ├── index.blade.php
    │   └── profil.blade.php
    └── ekayit/
        ├── index.blade.php
        ├── form.blade.php
        └── tesekkur.blade.php
```

---

## Sayfa — Backend Eşleştirmesi

### Bileşenler (3)

**components/header.blade.php**
- `config('site.x')` ile logo, telefon, sosyal medya
- `Auth::check()` ile giriş/çıkış durumu
- Aktif sayfa: `request()->routeIs('haberler*')` gibi kontrol

**components/footer.blade.php**
- `config('site.x')` ile iletişim bilgileri ve sosyal medya linkleri

**components/sidebar.blade.php**
- Veri Controller'lardan `compact()` ile gelecek:
```php
$sonHaberler = Haber::where('durum', 'yayinda')->latest('yayin_tarihi')->take(3)->get();
$kategoriler = HaberKategorisi::where('aktif', 1)->orderBy('sira')->get();
$yaklasanEtkinlik = Etkinlik::where('durum', 'yayinda')
    ->where('baslangic_tarihi', '>=', now())
    ->orderBy('baslangic_tarihi')->first();
```

---

### Ana Sayfalar (2)

#### index.html → HomeController@index
**Route:** GET /

**Veri:**
```php
$mansetHaberler = Haber::where('durum','yayinda')->where('manset',1)->latest('yayin_tarihi')->take(3)->get();
$sonHaberler = Haber::where('durum','yayinda')->latest('yayin_tarihi')->take(6)->get();
$kategoriler = HaberKategorisi::where('aktif',1)->orderBy('sira')->get();
$yaklasanEtkinlikler = Etkinlik::where('durum','yayinda')->where('baslangic_tarihi','>=',now())->orderBy('baslangic_tarihi')->take(3)->get();
$bagisturleri = BagisTuru::orderBy('sira')->get();
```

**Notlar:**
- Hero görseli: `loading="eager" fetchpriority="high"`
- Bağış türleri: `oneri_tutarlar` JSON kolonu — `json_decode($tur->oneri_tutarlar)`
- İstatistik sayaçları: sabit değerler veya settings tablosundan

**Schema:** Organization + WebSite

---

#### arama.html → AramaController@index
**Route:** GET /arama?q=

**Veri:**
```php
$q = request('q');
$haberler = Haber::where('durum','yayinda')
    ->where(fn($query) => $query->where('baslik','like',"%$q%")->orWhere('ozet','like',"%$q%"))
    ->latest()->take(10)->get();
$etkinlikler = Etkinlik::where('durum','yayinda')->where('baslik','like',"%$q%")->latest()->take(5)->get();
$sayfalar = KurumsalSayfa::where('aktif',1)->where('ad','like',"%$q%")->take(5)->get();
```

**Schema:** SearchResultsPage

---

### Haberler (2)

#### haberler.html → HaberController@index
**Route:** GET /haberler?kategori=&sayfa=

**Veri:**
```php
$kategoriler = HaberKategorisi::where('aktif',1)->orderBy('sira')->get();
$aktifKategori = request('kategori');
$haberler = Haber::where('durum','yayinda')
    ->when($aktifKategori, fn($q) => $q->whereHas('kategori', fn($k) => $k->where('slug',$aktifKategori)))
    ->latest('yayin_tarihi')
    ->paginate(12);
```

**DB Kolonları (haberler):**
- `baslik`, `slug`, `ozet`, `durum`, `manset`, `oncelik`
- `yayin_tarihi`, `gorsel_sm`, `gorsel_lg`, `gorsel_og`
- `kategori_id` → haber_kategorileri (ad, slug, renk)
- `goruntuleme`, `meta_description`, `seo_baslik`

**Schema:** ItemList

---

#### haber-detay.html → HaberController@show
**Route:** GET /haberler/{slug}
**Bileşen:** Header + Footer + Sidebar

**Veri:**
```php
$haber = Haber::where('slug',$slug)->where('durum','yayinda')->with(['kategori','etiketler','gorseller'])->firstOrFail();
$haber->increment('goruntuleme');
$ilgiliHaberler = Haber::where('durum','yayinda')
    ->where('kategori_id',$haber->kategori_id)
    ->where('id','!=',$haber->id)
    ->latest('yayin_tarihi')->take(3)->get();
```

**DB Kolonları:**
- `icerik` → `{!! $haber->icerik !!}` ile render
- `gorsel_lg`, `gorsel_og`, `gorsel_sm`, `gorsel_mobil_lg`
- `seo_baslik`, `meta_description`, `robots`, `canonical_url`
- `yayin_tarihi`, `yonetici_id`

**İlişkiler:**
- `haber_gorselleri` → galeri (sira, lg_yol, sm_yol, alt_text)
- `haber_etiketler` → etiketler tablosu ile pivot
- `haber_kisiler` → kisi_id ile kisiler tablosu
- `haber_kurumlar` → kurumlar tablosu

**Schema:** NewsArticle + BreadcrumbList

---

### Bağış (3)

#### bagis.html → BagisController@index
**Route:** GET /bagis

**Veri:**
```php
$bagisturleri = BagisTuru::orderBy('sira')->get();
```

**DB Kolonları (bagis_turleri):**
- `ad`, `slug`, `aciklama`, `hadis_ayet`
- `gorsel_kare`, `gorsel_dikey`, `gorsel_yatay`
- `fiyat_tipi` (sabit/serbest), `fiyat`, `minimum_tutar`
- `oneri_tutarlar` (JSON) → `json_decode($tur->oneri_tutarlar, true)`

**Schema:** ItemList

---

#### bagis-detay.html → BagisController@show
**Route:** GET /bagis/{slug}

**Veri:**
```php
$bagisTuru = BagisTuru::where('slug',$slug)->firstOrFail();
$sepet = session('sepet', []); // mevcut sepet akışına bağlan
```

**Form POST → mevcut sepet akışı:**
- Sepet tabloları: `bagis_sepetler` + `bagis_sepet_satirlar`
- Bağışçı: `bagis_kisiler` (ad_soyad, tc_kimlik, telefon, eposta, vekalet bilgileri)

**Form autocomplete:**
```html
<input autocomplete="name">
<input autocomplete="tel" inputmode="numeric" pattern="[0-9]*">
<input autocomplete="email">
<input inputmode="numeric" maxlength="11"> <!-- TC Kimlik -->
```

**Notlar:**
- Ödeme butonu: Faz 7C (Albaraka + Paytr) tamamlanınca aktif edilecek
- Şimdilik: "Sepete Ekle" → session'a yaz

**Schema:** Product

---

#### tesekkur.html → BagisController@tesekkur
**Route:** GET /bagis/tesekkur

**Veri:**
```php
$bagis = Bagis::where('bagis_no', session('son_bagis_no'))->with('bagisKisiler')->first();
// bagislar: bagis_no, toplam_tutar, odeme_tarihi, makbuz_yol
```

**Schema:** Order (bagis_makbuz.blade.php ile aynı yapı)

---

### Etkinlikler (2)

#### etkinlikler.html → EtkinlikController@index
**Route:** GET /etkinlikler?filtre=

**Veri:**
```php
$etkinlikler = Etkinlik::where('durum','yayinda')
    ->when(request('filtre') === 'bu-ay', fn($q) => $q->whereMonth('baslangic_tarihi', now()->month))
    ->when(request('filtre') === 'gelecek', fn($q) => $q->where('baslangic_tarihi','>=',now()))
    ->orderBy('baslangic_tarihi')
    ->paginate(12);
```

**DB Kolonları (etkinlikler):**
- `baslik`, `slug`, `ozet`, `durum`, `tip` (fiziksel/online)
- `baslangic_tarihi`, `bitis_tarihi`
- `konum_ad`, `konum_adres`, `konum_il`, `konum_ilce`
- `konum_lat`, `konum_lng` (harita embed için)
- `kontenjan`, `online_link`

**Schema:** EventList

---

#### etkinlik-detay.html → EtkinlikController@show
**Route:** GET /etkinlikler/{slug}
**Bileşen:** Header + Footer + Sidebar

**Veri:**
```php
$etkinlik = Etkinlik::where('slug',$slug)->where('durum','yayinda')->with('gorseller')->firstOrFail();
// etkinlik_gorselleri: sira, lg_yol, sm_yol, og_yol, alt_text
```

**Schema:** Event + BreadcrumbList

---

### Kurumsal & İletişim (2)

#### kurumsal.html → KurumsalController@show
**Route:** GET /kurumsal/{slug?}

**Veri:**
```php
$sayfa = KurumsalSayfa::where('slug',$slug)->where('aktif',1)->firstOrFail();
$menu = KurumsalSayfa::whereNull('ust_sayfa_id')->where('aktif',1)->orderBy('sira')->get();
$galerileri = $sayfa->galerileri; // kurumsal_sayfa_galerileri
```

**DB Kolonları (kurumsal_sayfalar):**
- `ad`, `slug`, `icerik`, `ozet`, `sablon`
- `ust_sayfa_id` (hiyerarşi), `meta_description`, `robots`

**Notlar:**
- `icerik` → `{!! $sayfa->icerik !!}` ile render
- `sablon` değerine göre farklı @include yapısı kullan

**Schema:** WebPage + BreadcrumbList

---

#### iletisim.html → IletisimController@index + store
**Route:** GET /iletisim | POST /iletisim

**Form POST:**
```php
// Alanlar: ad_soyad, eposta, telefon, konu, mesaj
// Gönderim: ZeptomailService ile yönetici bildirimi
// Log: eposta_gonderimleri tablosuna yaz
app(ZeptomailService::class)->iletisimFormuGonder($data);
```

**Form autocomplete:**
```html
<input autocomplete="name">
<input autocomplete="email">
<input autocomplete="tel" inputmode="numeric">
```

**Schema:** ContactPage + LocalBusiness

---

### Mezunlar (2)

#### mezunlar.html → MezunController@index
**Route:** GET /mezunlar?yil=&hafiz=

**Veri:**
```php
$mezunlar = MezunProfil::where('onaylandi', 1)
    ->with('uye')
    ->when(request('yil'), fn($q) => $q->where('mezuniyet_yili', request('yil')))
    ->when(request('hafiz'), fn($q) => $q->where('hafiz', 1))
    ->orderByDesc('mezuniyet_yili')
    ->paginate(24);
$yillar = MezunProfil::where('onaylandi',1)->distinct()->pluck('mezuniyet_yili')->sortDesc();
```

**DB Kolonları (mezun_profiller):**
- `uye_id` → uyeler (ad_soyad, telefon, eposta)
- `mezuniyet_yili`, `sinif_id`, `hafiz`
- `meslek`, `gorev_il`, `gorev_ilce`
- `linkedin`, `instagram`, `twitter`
- `kurum_id`, `kurum_manuel`

**Notlar:**
- Mezun adı: `$mezun->uye->ad_soyad`
- `onaylandi` kolonunu MezunProfil modelinde kontrol et — isim farklıysa bul

**Schema:** ItemList

---

#### mezunlar-profil.html → MezunController@show
**Route:** GET /mezunlar/{id}

**Veri:**
```php
$mezun = MezunProfil::where('id',$id)->where('onaylandi',1)->with('uye')->firstOrFail();
```

**Schema:** Person + BreadcrumbList

---

### E-Kayıt (3)

#### ekayit.html → EkayitController@index
**Route:** GET /kayit

**Veri:**
```php
$aktifDonem = EkayitDonem::where('aktif',1)->first();
$siniflar = $aktifDonem ? EkayitSinif::where('donem_id',$aktifDonem->id)->get() : collect();
// ekayit_donemler: ad, ogretim_yili, baslangic, bitis, aktif
```

**Notlar:**
- Aktif dönem yoksa "Kayıt dönemi kapalı" mesajı göster
- `bitis` tarihi geçmişse "Kayıt süresi doldu" mesajı göster

---

#### ekayit-form.html → EkayitController@form + store
**Route:** GET /kayit/form | POST /kayit/store

**Çok adımlı form — mevcut akışa bağlan (Faz 8A-8B):**
- Adım 1: Öğrenci → `ekayit_ogrenci_bilgileri` + `ekayit_kimlik_bilgileri`
- Adım 2: Veli → `ekayit_veli_bilgileri` + `ekayit_baba_bilgileri`
- Adım 3: Okul → `ekayit_okul_bilgileri`
- Adım 4: Onay → özet + gönder

**OTP doğrulama (OtpService — mevcut):**
```html
<input autocomplete="one-time-code" inputmode="numeric" maxlength="6">
```

**Form autocomplete:**
```html
<input autocomplete="given-name">     <!-- Öğrenci Ad -->
<input autocomplete="family-name">    <!-- Öğrenci Soyad -->
<input autocomplete="bday">           <!-- Doğum tarihi -->
<input inputmode="numeric" maxlength="11"> <!-- TC Kimlik -->
<input autocomplete="name">           <!-- Veli Ad Soyad -->
<input autocomplete="tel" inputmode="numeric" pattern="[0-9]*"> <!-- Veli Tel -->
<input autocomplete="email">          <!-- Veli E-posta -->
```

**POST sonrası otomatik tetiklenenler (mevcut):**
- `EkayitSmsJob` → SMS bildirimi
- `OnayEpostasiGonderJob` → E-posta bildirimi
- Onay sonrası: `KisiEslestirmeService::ekayitEslestir()` → Veli rozeti

---

#### ekayit-tesekkur.html → EkayitController@tesekkur
**Route:** GET /kayit/tesekkur

**Veri:**
```php
$kayit = EkayitKayit::find(session('son_ekayit_id'));
```

---

## SEO — layouts/app.blade.php

```html
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', config('site.ad')) — Kestanepazarı</title>
<meta name="description" content="@yield('meta_description', config('site.aciklama'))">
<meta name="robots" content="@yield('robots', 'index, follow')">
<link rel="canonical" href="@yield('canonical', url()->current())">

<!-- Open Graph -->
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:title" content="@yield('title') — Kestanepazarı">
<meta property="og:description" content="@yield('meta_description', config('site.aciklama'))">
<meta property="og:image" content="@yield('og_image', asset('img/og-default.jpg'))">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="tr_TR">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@yield('title') — Kestanepazarı">
<meta name="twitter:description" content="@yield('meta_description')">
<meta name="twitter:image" content="@yield('og_image', asset('img/og-default.jpg'))">

<!-- GEO -->
<meta name="geo.region" content="TR-35">
<meta name="geo.placename" content="Seferihisar, İzmir">
<meta name="geo.position" content="38.1956;26.8344">
<meta name="ICBM" content="38.1956, 26.8344">

<!-- Schema (sayfa bazlı) -->
@yield('schema')
```

---

## Schema.org — Sayfa Bazlı

### Organization (layouts/app.blade.php — tüm sayfalarda)
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "{{ config('site.ad') }} Öğrenci Yetiştirme Derneği",
  "url": "{{ url('/') }}",
  "logo": "https://cdn.kestanepazari.org.tr/logo.png",
  "telephone": "{{ config('site.telefon') }}",
  "email": "{{ config('site.eposta') }}",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Seferihisar",
    "addressRegion": "İzmir",
    "addressCountry": "TR"
  },
  "sameAs": ["{{ config('site.facebook') }}", "{{ config('site.instagram') }}", "{{ config('site.x') }}"]
}
</script>
```

### NewsArticle (haber-detay)
```html
@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "NewsArticle",
  "headline": "{{ $haber->baslik }}",
  "image": "{{ $haber->gorsel_og ? 'https://cdn.kestanepazari.org.tr/'.$haber->gorsel_og : asset('img/og-default.jpg') }}",
  "datePublished": "{{ $haber->yayin_tarihi?->toIso8601String() }}",
  "dateModified": "{{ $haber->updated_at?->toIso8601String() }}",
  "description": "{{ $haber->meta_description ?? $haber->ozet }}",
  "author": {"@type": "Organization", "name": "Kestanepazarı Derneği"},
  "publisher": {
    "@type": "Organization",
    "name": "Kestanepazarı Derneği",
    "logo": {"@type": "ImageObject", "url": "https://cdn.kestanepazari.org.tr/logo.png"}
  }
}
</script>
@endsection
```

### Event (etkinlik-detay)
```html
@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Event",
  "name": "{{ $etkinlik->baslik }}",
  "startDate": "{{ $etkinlik->baslangic_tarihi?->toIso8601String() }}",
  "endDate": "{{ $etkinlik->bitis_tarihi?->toIso8601String() }}",
  "eventStatus": "https://schema.org/EventScheduled",
  "eventAttendanceMode": "{{ $etkinlik->tip === 'online' ? 'https://schema.org/OnlineEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode' }}",
  "location": {
    "@type": "Place",
    "name": "{{ $etkinlik->konum_ad }}",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "{{ $etkinlik->konum_adres }}",
      "addressLocality": "{{ $etkinlik->konum_ilce }}",
      "addressRegion": "{{ $etkinlik->konum_il }}",
      "addressCountry": "TR"
    }
  },
  "organizer": {"@type": "Organization", "name": "Kestanepazarı Derneği"}
}
</script>
@endsection
```

### BreadcrumbList (iç sayfalarda)
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Ana Sayfa", "item": "{{ url('/') }}"},
    {"@type": "ListItem", "position": 2, "name": "{{ $bolumAd }}", "item": "{{ $bolumUrl }}"},
    {"@type": "ListItem", "position": 3, "name": "{{ $baslik }}"}
  ]
}
</script>
```

---

## Hız / Performance

- Görseller: WebP, DO Spaces CDN üzerinden
- Lazy loading: tüm `<img>`'lerde `loading="lazy"` (hero hariç)
- Hero: `loading="eager" fetchpriority="high"`
- Font preload: Libre Baskerville + Plus Jakarta Sans
- Tailwind: production'da CSS purge aktif
- JS: `defer` veya `async`, body sonunda
- Production: `php artisan config:cache && route:cache && view:cache`

---

## Mevcut Servisler

| Servis | Kullanılacak Sayfa |
|---|---|
| ZeptomailService | İletişim formu bildirimi |
| OtpService | E-Kayıt OTP doğrulama |
| HermesService | E-Kayıt SMS (otomatik, mevcut akışta) |
| KisiEslestirmeService | Bağış + E-Kayıt + Mezun (otomatik) |

## Mevcut Job'lar (Otomatik)

| Job | Tetikleyici |
|---|---|
| EkayitSmsJob | E-Kayıt form submit |
| OnayEpostasiGonderJob | E-Kayıt onay |
| MakbuzOlusturJob | Bağış ödeme (Faz 7C sonrası) |

---

## Adım Sırası (19 Dosya)

### Bileşenler — Önce bunlar (3)
1. `_header.html` → components/header.blade.php + layouts/app.blade.php + config/site.php + .env + tailwind.config.js
2. `_footer.html` → components/footer.blade.php
3. `_sidebar.html` → components/sidebar.blade.php (veri: son haberler, kategoriler, yaklaşan etkinlik)

### Ana Sayfalar (2)
4. `index.html` → HomeController@index
5. `arama.html` → AramaController@index

### Haberler (2)
6. `haberler.html` → HaberController@index
7. `haber-detay.html` → HaberController@show + Sidebar

### Bağış (3)
8. `bagis.html` → BagisController@index
9. `bagis-detay.html` → BagisController@show
10. `tesekkur.html` → BagisController@tesekkur

### Etkinlikler (2)
11. `etkinlikler.html` → EtkinlikController@index
12. `etkinlik-detay.html` → EtkinlikController@show + Sidebar

### Kurumsal & İletişim (2)
13. `kurumsal.html` → KurumsalController@show
14. `iletisim.html` → IletisimController@index + store

### Mezunlar (2)
15. `mezunlar.html` → MezunController@index
16. `mezunlar-profil.html` → MezunController@show

### E-Kayıt (3)
17. `ekayit.html` → EkayitController@index
18. `ekayit-form.html` → EkayitController@form + store
19. `ekayit-tesekkur.html` → EkayitController@tesekkur

---

Her dosya için yapılacaklar:
1. HTML'i oku
2. Blade'e çevir (layout extend, component kullan, config('site.x'))
3. Controller + Route ekle (web.php) — yukarıdaki route tablosuna uy
4. Backend veriyi Controller'dan Blade'e aktar (compact ile)
5. SEO @section'ları ekle (title, meta_description, robots, canonical, og_image)
6. Schema.org JSON-LD ekle (@section('schema'))
7. Görsellere lazy loading + hero'ya fetchpriority ekle
8. Commit: `feat: [sayfa] Blade + Controller + SEO + Schema eklendi`
9. PUSH HAZIR - onaylıyor musun? diye sor
