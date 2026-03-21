# Teknik Kurallar

Bu dosya tüm modüller için geçerli backend teknik standartlarını içerir.
Modül MD dosyalarında tekrar yazılmaz, buraya referans verilir.

---

## 1. Sunucu Bilgileri

| Özellik | Değer | Notlar |
|---|---|---|
| PHP | 8.2.28 | Laravel 11 için ideal |
| Redis | 6.1.0 | Queue ve cache driver |
| ImageMagick | 3.7.0 | Görsel işleme (GD yerine) |
| MySQL | — | utf8mb4_turkish_ci collation |
| OPcache | Aktif | Production'da açık kalmalı |
| Upload Limit | 16MB | ⚠️ 64MB'a çıkarılmalı (Plesk panel) |

> **Kritik:** `upload_max_filesize` ve `post_max_size` değerleri Plesk panelinden 64MB'a çıkarılmalıdır. Dergi PDF'leri ve yüksek çözünürlüklü görseller için 16MB yetersiz kalır.

---

## 2. Kodlama Standartları

### İsimlendirme — Türkçe

Tüm tablo, model, değişken, method, job isimleri Türkçe olacak.

| Katman | Format | Örnek |
|---|---|---|
| Tablo | snake_case, çoğul | `haber_kategorileri`, `bagis_turleri` |
| Model | PascalCase, tekil | `HaberKategorisi`, `BagisTuru` |
| Foreign key | `{tekil_tablo_adi}_id` | `kisi_id`, `haber_id`, `yonetici_id` |
| Method | camelCase | `haberYayinla()`, `bagisOnayla()` |
| Variable | camelCase | `$mevcutKisi`, `$aktifBagis` |
| Job | PascalCase + Job suffix | `GorselOptimizeJob`, `SmsGonderJob` |
| Service | PascalCase + Service suffix | `HermesService`, `GeminiService` |
| Enum | PascalCase | `BagisDurumu`, `HaberDurumu` |

### Enum — PHP 8.1 Native

```php
// ✅ Doğru
enum HaberDurumu: string
{
    case Taslak = 'taslak';
    case Incelemede = 'incelemede';
    case Yayinda = 'yayinda';
    case Reddedildi = 'reddedildi';
}

// Kullanım
$haber->durum = HaberDurumu::Yayinda;
$haber->durum->value; // 'yayinda'
```

Tüm `durum`, `tip`, `kaynak` gibi sabit değer alanları string backed enum olarak tanımlanır. Migration'da `string` olarak saklanır.

---

## 3. Klasör Yapısı

```
app/
├── Enums/                    → Tüm enum tanımları
│   ├── HaberDurumu.php
│   ├── BagisDurumu.php
│   └── ...
├── Filament/
│   ├── Resources/            → Panel ekranları (Filament Resource)
│   ├── Widgets/              → Panel widget'ları
│   └── Pages/                → Özel panel sayfaları
├── Http/
│   ├── Controllers/
│   │   └── Api/              → Frontend API controller'ları
│   ├── Requests/             → Form validation
│   └── Resources/
│       └── Api/              → Frontend API Resource sınıfları
├── Jobs/                     → Tüm Queue job'ları
├── Models/                   → Tüm Eloquent modeller
└── Services/                 → Dış servis sınıfları
    ├── HermesService.php
    ├── GeminiService.php
    ├── PaymentService.php
    ├── SpacesService.php
    ├── ZeptomailService.php
    ├── HijriService.php
    └── LevenshteinService.php
```

---

## 4. Resource vs API Resource

### Kural

| Kullanım | Sınıf | Yer |
|---|---|---|
| Admin panel ekranları | Filament Resource | `app/Filament/Resources/` |
| Frontend'e JSON veri | API Resource | `app/Http/Resources/Api/` |

### API Resource

Aksini belirtilmeyen tüm modüller frontend'de kullanılacağından her modül için API Resource yazılır. Tüm frontend verileri `/api/v1/` prefix'iyle döner.

```php
// Route tanımı
Route::prefix('api/v1')->group(function () {
    Route::apiResource('haberler', Api\HaberController::class)->only(['index', 'show']);
    Route::apiResource('etkinlikler', Api\EtkinlikController::class)->only(['index', 'show']);
    // ...
});
```

**Sadece backend (panel) olan modüller için API Resource yazılmaz:**
Kişiler, Kurumlar, Loglar, Roller, Yöneticiler, Dergiler, İrşad, Pazarlama - SMS, Pazarlama - E-posta, Kurban Yönetimi

**API Resource yazılacak modüller:**
Haberler, Etkinlikler, Kurumsal Sayfalar, Bağış (frontend form + makbuz), Öğrenci E-Kayıt (frontend form), Mezunlar (profil), Üyeler (auth + profil)

---

## 5. Veritabanı Kuralları

### Genel Kurallar

```php
// Collation — tüm tablolarda
'charset' => 'utf8mb4',
'collation' => 'utf8mb4_turkish_ci',
```

- Tüm ana modül tablolarında `deleted_at` (soft delete) zorunlu
- Tüm tablolarda `created_at` ve `updated_at` zorunlu
- Varsayılan sıralama: `created_at DESC`
- Boolean alanlar: `default(false)` — explicit default zorunlu
- Decimal alanlar: para birimi için `decimal(10, 2)`

### Foreign Key Convention

```
Tablo adı tekil + _id

kisiler         → kisi_id
haberler        → haber_id
etkinlikler     → etkinlik_id
yoneticiler     → yonetici_id
kurumlar        → kurum_id
bagis_turleri   → bagis_turu_id
ekayit_siniflar → ekayit_sinif_id
```

### Migration Örneği

```php
Schema::create('haberler', function (Blueprint $table) {
    $table->id();
    $table->foreignId('yonetici_id')->constrained('yoneticiler');
    $table->foreignId('kategori_id')->constrained('haber_kategorileri');
    $table->string('baslik', 60);
    $table->string('slug', 100)->unique();
    $table->enum('durum', ['taslak', 'incelemede', 'yayinda', 'reddedildi'])
          ->default('taslak');
    $table->boolean('ai_islendi')->default(false);
    $table->timestamps();
    $table->softDeletes();

    // Index'ler
    $table->index('durum');
    $table->index('yayin_tarihi');
    $table->index(['durum', 'yayin_tarihi']); // Composite — frontend listesi için
});
```

---

## 6. Index Stratejisi

### Her Tabloda Zorunlu Index'ler

```
foreign key alanları   → Eloquent otomatik ekler (constrained())
durum / aktif          → Filtre sorguları
created_at             → Sıralama ve tarih filtresi
```

### Modüle Özel Index'ler

| Tablo | Index Alanları | Sebep |
|---|---|---|
| `kisiler` | `ad_soyad`, `telefon`, `eposta` | Arama, Levenshtein kontrol |
| `kurumlar` | `ad` | Arama, Levenshtein kontrol |
| `uyeler` | `telefon`, `eposta` | Kimlik doğrulama, hız kritik |
| `uyeler` | `sms_abonelik`, `eposta_abonelik` | Kampanya filtreleme |
| `haberler` | `slug`, `durum`, `yayin_tarihi`, `kategori_id` | Frontend liste ve detay |
| `haberler` | `(durum, yayin_tarihi)` composite | Frontend yayın sorgusu |
| `etkinlikler` | `slug`, `durum`, `tarih` | Frontend liste |
| `bagislar` | `bagis_no`, `durum`, `uye_id` | Makbuz, filtreleme |
| `sms_gonderim_gecmisi` | `uye_id`, `created_at` | Spam engeli sorgusu |
| `eposta_gonderim_gecmisi` | `uye_id`, `created_at` | Spam engeli sorgusu |
| `mezun_profiller` | `ikamet_il`, `ikamet_ilce`, `mezuniyet_yili` | Segment filtreleme |
| `ekayit_kayitlar` | `durum`, `sinif_id` | Dashboard kartları |
| `kurban_kayitlar` | `durum`, `bildirim_durumu` | Panel filtreleme |

### TNTSearch Index Kapsamı (Global Arama)

TNTSearch `toSearchableArray()` tanımlanan modeller:

```php
// Örnek — Haber modeli
public function toSearchableArray(): array
{
    return [
        'id'       => $this->id,
        'baslik'   => $this->baslik,
        'icerik'   => strip_tags($this->icerik),
        'ozet'     => $this->ozet,
        'yazar'    => $this->yonetici->ad_soyad ?? '',
    ];
}
```

| Modül | Aranabilir Alanlar |
|---|---|
| Haberler | baslik, icerik (tam metin), ozet, yazar adı |
| Etkinlikler | ad, aciklama, konum_ad |
| Kurumsal Sayfalar | ad, icerik |
| Kişiler | ad_soyad, unvan, kurum_aciklama |
| Kurumlar | ad |
| Üyeler | ad_soyad, telefon, eposta |
| Bağış | bagis_no, uye adı |
| Mezunlar | ad_soyad, meslek, kurum |
| İrşad | cami_adi, il, ilce |

---

## 7. Global Arama (Spotlight Benzeri)

Panel üst barında tek arama kutusu. TNTSearch ile tüm modüllerde eş zamanlı arama yapılır. Sonuçlar modül bazlı gruplandırılarak gelir:

```
Arama: "Barış Yılmaz"

Üyeler (1)      → Barış Yılmaz — barisy@example.com
Haberler (3)    → Barış Yılmaz konferans verdi...
Kişiler (1)     → Barış Yılmaz — Müdür, KÖD
Mezunlar (1)    → Barış Yılmaz — 2020 mezunu
```

Her sonuç ilgili modülün detay sayfasına link verir. Filament'in global search özelliği üzerine kurulur.

---

## 8. Queue — Redis

### Driver

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),
```

### Queue Kanalları ve Öncelikleri

| Kanal | Öncelik | İçerik |
|---|---|---|
| `high` | En yüksek | Ödeme bildirimleri, OTP SMS/e-posta |
| `default` | Orta | AI işlemleri, görsel optimize, makbuz PDF, kurban aktarım |
| `low` | Düşük | Pazarlama SMS/e-posta, raporlar, senkronizasyon, temizlik |

```bash
# Worker başlatma (supervisor ile)
php artisan queue:work redis --queue=high,default,low
```

### Job Listesi ve Ayarları

| Job Sınıfı | Queue | Timeout | Retry | Açıklama |
|---|---|---|---|---|
| `SmsGonderJob` | high | 30sn | 3 | OTP ve bildirim SMS |
| `EpostaGonderJob` | high | 30sn | 3 | OTP ve bildirim e-posta |
| `OdemeHataJob` | high | 30sn | 3 | Ödeme hatası bildirimi |
| `AiHaberIsleJob` | default | 120sn | 3 | Gemini AI haber işleme |
| `KisiTespitJob` | default | 120sn | 3 | Kişi/kurum AI tespiti |
| `GorselOptimizeJob` | default | 120sn | 3 | WebP dönüşüm, DO Spaces |
| `MakbuzOlusturJob` | default | 120sn | 3 | Bağış makbuz PDF |
| `KurbanAktarimJob` | default | 60sn | 3 | Kurban modülüne aktarım |
| `EkayitEvrakJob` | default | 120sn | 3 | Evrak PDF oluşturma |
| `ZipOlusturJob` | default | 300sn | 2 | Toplu ZIP oluşturma |
| `PazarlamaSmsJob` | low | 300sn | 3 | Toplu SMS kampanyası |
| `PazarlamaEpostaJob` | low | 300sn | 3 | Toplu e-posta kampanyası |
| `HermesRaporSyncJob` | low | 60sn | 3 | Hermes iletim durumu sync |
| `SpacesDosyaSilJob` | low | 60sn | 2 | DO Spaces dosya temizliği |
| `SepetTerkKontrolJob` | low | 30sn | 1 | 8 saat terk sepet kontrolü |
| `HicriTakvimKontrolJob` | low | 30sn | 1 | Bağış açılış/kapanış |
| `HermesSyncJob` | low | 120sn | 2 | Rehber senkronizasyonu |
| `OtomatikRaporJob` | low | 300sn | 2 | Günlük/haftalık/aylık rapor |

### Retry Stratejisi (Exponential Backoff)

```php
// Örnek Job
public function backoff(): array
{
    return [60, 120, 300]; // 1dk, 2dk, 5dk arayla dener
}
```

### Failed Job Yönetimi

```php
// Tüm job'larda
public function failed(Throwable $exception): void
{
    // Loglar modülüne yaz
    activity('job_hata')
        ->withProperties(['job' => static::class, 'hata' => $exception->getMessage()])
        ->log('Job başarısız oldu');

    // Kritik job'larda admin'e panel bildirimi
    if ($this->isCritical()) {
        Notification::make()->title('Job Hatası')->danger()->send();
    }
}
```

---

## 9. Filament Panel Standartları

### Renk Paleti

| Durum | Renk | Tailwind | Hex |
|---|---|---|---|
| Birincil / Varsayılan | Mavi | `blue-600` | `#2563eb` |
| Başarı / Onayla / Kaydet | Yeşil | `green-600` | `#16a34a` |
| Tehlike / Sil / Reddet | Kırmızı | `red-600` | `#dc2626` |
| Uyarı | Amber | `amber-500` | `#f59e0b` |
| Bilgi | Lacivert | `blue-900` | `#1e3a5f` |

### Filament Config

```php
// config/filament.php veya AppServiceProvider
FilamentColor::register([
    'primary' => Color::Blue,
]);
```

### Panel Stili

- **Mod:** Light (açık tema)
- **Font:** Sistem fontu (Inter veya Tailwind default)
- **Sidebar:** Kompakt, ikonlu
- **Dil:** Türkçe (`lang/tr/`)

### Buton Renk Kuralları

```php
// Filament Action renkleri
Action::make('onayla')->color('success')   // yeşil
Action::make('reddet')->color('danger')    // kırmızı
Action::make('duzenle')->color('primary')  // mavi
Action::make('sil')->color('danger')       // kırmızı
Action::make('kaydet')->color('success')   // yeşil
Action::make('yayinla')->color('primary')  // mavi
Action::make('iptal')->color('gray')       // gri
```

### Sıralanabilir Tablolar

Tüm Filament liste tablosu kolonları `sortable()` olacak:

```php
TextColumn::make('ad_soyad')->sortable(),
TextColumn::make('created_at')->sortable()->label('Kayıt Tarihi'),
TextColumn::make('durum')->sortable(),
```

Varsayılan sıralama:
```php
->defaultSort('created_at', 'desc')
```

### Widget — Role Göre Görünüm

Tüm widget sınıflarında `canView()` methodu zorunlu:

```php
public static function canView(): bool
{
    return auth()->user()->hasAnyRole(['admin', 'editör'])
        || auth()->user()->hasPermissionTo('dashboard.widget.bagis');
}
```

Widget izin isimlendirme: `dashboard.widget.{widget_adi}`

---

## 10. Görsel İşleme

### ImageMagick Driver (GD Değil)

Sunucuda ImageMagick 3.7.0 mevcut. `intervention/image` paketi ImageMagick driver ile kullanılır:

```php
// config/image.php
'driver' => 'imagick',
```

### WebP Dönüşüm Standardı

```php
// GorselOptimizeJob içinde
$gorsel = Image::read($kaynak);

// LG versiyonu
$gorsel->scaleDown(1280, 720)
       ->toWebp(quality: 85)
       ->save($lgYol);

// OG versiyonu
$gorsel->scaleDown(1200, 675)
       ->toWebp(quality: 85)
       ->save($ogYol);

// SM versiyonu
$gorsel->scaleDown(320, 180)
       ->toWebp(quality: 80)
       ->save($smYol);
```

---

## 11. Güvenlik

bkz. `guvenlik.md` — tüm güvenlik kuralları oradan referans alınır.

Ek olarak:
- `expose_php Off` — production'da php versiyonunu gizle
- `display_errors Off` — production'da hata gösterme (zaten Off)
- OPcache açık kalmalı

---

## 12. Scheduler (Cron)

Tüm Scheduler işlemleri `app/Console/Kernel.php` içinde tanımlanır.

```php
// Tek cron satırı — Plesk'te tanımlanacak
* * * * * php /var/www/vhosts/kestanepazari.org.tr/httpdocs/artisan schedule:run
```

| Görev | Sıklık | Job/Command |
|---|---|---|
| Hicri takvim kontrolü | Her saat | `HicriTakvimKontrolJob` |
| Sepet terk kontrolü | Her saat | `SepetTerkKontrolJob` |
| Hermes rapor sync | 10 dakikada bir | `HermesRaporSyncJob` |
| Hermes rehber sync | Günlük 03:00 | `HermesSyncJob` |
| Otomatik günlük rapor | Günlük 08:00 | `OtomatikRaporJob` (günlük) |
| Otomatik haftalık rapor | Pazartesi 08:00 | `OtomatikRaporJob` (haftalık) |
| Otomatik aylık rapor | Ayın 1'i 08:00 | `OtomatikRaporJob` (aylık) |
| DO Spaces dosya temizliği | Günlük 02:00 | `SpacesDosyaSilJob` |
| ZIP dosyası temizliği | Günlük 04:00 | ZIP'leri 1 günden eski sil |

---

## 13. Log Paketi

**`z3d0x/filament-logger`** kullanılır (`spatie/laravel-activitylog` üzerine kurulu).

bkz. `loglar.md` — tüm loglama kuralları oradan referans alınır.

---

## 14. Upload Limiti — Plesk Ayarı

Proje başlamadan önce Plesk panelinden yapılması gereken ayar:

```
PHP Settings → upload_max_filesize → 64M
PHP Settings → post_max_size       → 64M
PHP Settings → max_execution_time  → 120
```
