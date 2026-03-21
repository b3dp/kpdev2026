# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Proje Genel Bakış

Bu repo, **Kestanepazarı Öğrenci Yetiştirme Derneği** için geliştirilen Laravel 11 + Filament PHP tabanlı yönetim sisteminin kaynak kodunu içerir (ya da içerecektir). Repo şu an sadece `docs/` klasöründe spesifikasyon dokümanlarına sahiptir; implementasyon bu dokümanlara göre yapılacaktır.

- **Backend:** PHP 8.2, Laravel 11, Filament PHP (admin panel)
- **Veritabanı:** MySQL (`utf8mb4_turkish_ci` collation)
- **Queue:** Database (Redis ilerleyen aşamada eklenecek)
- **Görsel İşleme:** ImageMagick 3.7.0 (GD değil — `'driver' => 'imagick'`)
- **Arama:** Laravel Scout + TNTSearch (dosya tabanlı, dış servis gerektirmez)
- **Dosya Depolama:** Digital Ocean Spaces (S3-uyumlu)
- **Node:** 25 (`.node-version`)

---

## Komutlar

```bash
# Geliştirme
php artisan serve
php artisan migrate
php artisan migrate:fresh --seed

# Queue worker (Supervisor ile çalıştırılır)
php artisan queue:work database --queue=default

# Scheduler (Plesk cron'a tek satır eklenir)
* * * * * php /var/www/vhosts/kestanepazari.org.tr/httpdocs/artisan schedule:run

# Search index
php artisan scout:sync
php artisan scout:import "App\Models\Haber"

# Tek test çalıştırma
php artisan test --filter=HaberTest
php artisan test tests/Feature/HaberTest.php
```

**Plesk'te manuel yapılması gereken ayarlar:**
```
PHP Settings → upload_max_filesize → 64M
PHP Settings → post_max_size       → 64M
PHP Settings → max_execution_time  → 120
```

---

## Klasör Yapısı (Beklenen)

```
app/
├── Enums/                    → Tüm enum tanımları (HaberDurumu, BagisDurumu vb.)
├── Filament/
│   ├── Resources/            → Panel ekranları
│   ├── Widgets/              → Dashboard widget'ları
│   └── Pages/                → Özel panel sayfaları
├── Http/
│   ├── Controllers/Api/      → Frontend API controller'ları
│   ├── Requests/             → Form validation
│   └── Resources/Api/        → Frontend API Resource sınıfları
├── Jobs/                     → Queue job'ları
├── Models/                   → Eloquent modeller
└── Services/                 → Dış servis wrapper'ları
    ├── GeminiService.php
    ├── HermesService.php
    ├── PaymentService.php
    ├── SpacesService.php
    └── ZeptomailService.php
```

---

## İsimlendirme Kuralları (Türkçe)

Tüm tablo, model, değişken, method, job isimleri **Türkçe** olacak.

| Katman | Format | Örnek |
|---|---|---|
| Tablo | snake_case, çoğul | `haber_kategorileri`, `bagis_turleri` |
| Model | PascalCase, tekil | `HaberKategorisi`, `BagisTuru` |
| Foreign key | `{tekil_tablo}_id` | `kisi_id`, `haber_id`, `yonetici_id` |
| Method | camelCase | `haberYayinla()`, `bagisOnayla()` |
| Variable | camelCase | `$mevcutKisi`, `$aktifBagis` |
| Job | PascalCase + Job | `GorselOptimizeJob`, `SmsGonderJob` |
| Service | PascalCase + Service | `HermesService`, `GeminiService` |
| Enum | PascalCase | `BagisDurumu`, `HaberDurumu` |

---

## Veritabanı Kuralları

- Tüm tablolarda `utf8mb4_turkish_ci` collation
- Tüm ana modüllerde `deleted_at` (soft delete) **zorunlu**
- `created_at` + `updated_at` **zorunlu**; varsayılan sıralama `created_at DESC`
- Boolean alanlar: `default(false)` — explicit default zorunlu
- Para birimi: `decimal(10, 2)`
- Enum yerine PHP 8.1 native `string backed enum`, migration'da `string` olarak saklanır

**Zorunlu index'ler:** foreign keys, `durum`/`aktif`, `created_at`; modüle özel ek index'ler `docs/teknik-kurallar.md` §6'da listelendi.

---

## Mimari: Resource vs API Resource

| Kullanım | Sınıf | Yer |
|---|---|---|
| Admin panel ekranları | Filament Resource | `app/Filament/Resources/` |
| Frontend'e JSON | API Resource | `app/Http/Resources/Api/` |

Tüm frontend rotaları `/api/v1/` prefix'iyle tanımlanır.

**API Resource yazılmayacak modüller** (sadece panel): Kişiler, Kurumlar, Loglar, Roller, Yöneticiler, Dergiler, İrşad, Pazarlama SMS/E-posta, Kurban Yönetimi

---

## Queue Kanalları

| Kanal | Öncelik | İçerik |
|---|---|---|
| `high` | En yüksek | OTP SMS/e-posta, ödeme bildirimleri |
| `default` | Orta | AI işlemleri, görsel optimize, makbuz PDF |
| `low` | Düşük | Pazarlama, raporlar, senkronizasyon, temizlik |

Tüm job'larda exponential backoff (`[60, 120, 300]` saniye) ve `failed()` methodu uygulanır.

---

## Görsel İşleme Standardı

Her yüklenen görsel için 3 WebP versiyonu üretilir (`GorselOptimizeJob`):

| Suffix | Boyut | Kullanım |
|---|---|---|
| `-lg.webp` | 1280×720 | Detay sayfası |
| `-og.webp` | 1200×675 | OG image (sosyal medya) |
| `-sm.webp` | 320×180 | Thumbnail, widget |

DO Spaces klasör yapısı: `img26/ori/`, `img26/opt/`, `img26/pdf26/`, `img26/xlsx26/`

---

## Filament Panel Standartları

- **Tema:** Light (açık)
- **Dil:** Türkçe (`lang/tr/`)
- **Primary renk:** Blue (`blue-600`)
- Tüm liste sütunları `sortable()`, varsayılan sıralama `->defaultSort('created_at', 'desc')`
- Tüm widget sınıflarında `canView()` methodu zorunlu (widget izin adı: `dashboard.widget.{widget_adi}`)
- Buton renk kuralı: onayla/kaydet=`success`, sil/reddet=`danger`, düzenle/yayınla=`primary`, iptal=`gray`

---

## Güvenlik Gereksinimleri

Her modül uygulanırken `docs/guvenlik.md` referans alınır. Özetle:

- Tüm formlarda sunucu taraflı validasyon (regex kuralları `docs/guvenlik.md` §1'de)
- Blade şablonlarında `{!! !!}` **asla** kullanılmaz
- Ham SQL (`DB::statement`) kullanıcı girdisiyle **asla** birleştirilmez
- Dosya adları UUID ile yeniden adlandırılır, orijinal isim saklanmaz
- Frontend formlarında: Honeypot (`spatie/laravel-honeypot`) + reCAPTCHA v3 (skor < 0.5 reddedilir)
- Rate limit: form submit 3 req/dk, login 5 req/dk, OTP gönderme 3 req/10dk
- Otomatik IP kara listeye alma: 10 dakikada 20+ başarısız istek → 24 saat ban

---

## Kimlik Bilgileri

Gerçek API anahtarları, DB şifresi ve servis credentials'ları `docs/pass.md` dosyasında saklanmaktadır. `.env` oluştururken veya servis bağlantısı gerektiğinde oradan alınır.

---

## Dış Servisler (ENV Anahtarları)

| Servis | Sınıf | ENV Prefix |
|---|---|---|
| Digital Ocean Spaces | `SpacesService` | `DO_*` |
| SMS (İletişim Makinesi / Hermes) | `HermesService` | `ILETISIM_MAKINESI_*` |
| E-posta (Zoho Zeptomail) | `ZeptomailService` | `ZEPTOMAIL_*` |
| AI (Google Gemini Pro) | `GeminiService` | `GEMINI_API_KEY`, `GOOGLE_CLOUD_PROJECT_ID` |
| Ödeme Birincil (Albaraka Sanal Pos) | `PaymentService` | `ALBARAKA_*` |
| Ödeme Yedek (Paytr) | `PaymentService` | `PAYTR_*` |
| Google Maps / Places | — | `GOOGLE_MAPS_API_KEY`, `GOOGLE_MAPS_API_KEY_PUBLIC` |
| Google Sheets | — | `GOOGLE_SPREADSHEET_ID`, `GOOGLE_SERVICE_ACCOUNT_JSON` |
| reCAPTCHA v3 | — | `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` |
| GA4 / GTM | — | `VITE_GTM_ID`, `VITE_GA4_ID` |

Ödeme akışı: Albaraka birincil, Paytr otomatik yedek (kullanıcıya seçim sunulmaz). İkisi ortak `PaymentService` üzerinden yönetilir.

---

## Modüller

| Modül | Doküman | API? |
|---|---|---|
| Haberler | `docs/haberler.md` | ✅ |
| Üyeler | `docs/uyeler.md` | ✅ (auth + profil) |
| Bağış | `docs/bagis.md` | ✅ |
| Etkinlikler | `docs/etkinlikler.md` | ✅ |
| Kurumsal Sayfalar | `docs/kurumsal-sayfalar.md` | ✅ |
| Öğrenci E-Kayıt | `docs/ekayit.md` | ✅ (frontend form) |
| Mezunlar | `docs/mezunlar.md` | ✅ (profil) |
| Kişiler | `docs/kisiler.md` | ❌ |
| Kurumlar | `docs/kurumlar.md` | ❌ |
| Kurban Yönetimi | `docs/kurban.md` | ❌ |
| Dergiler | `docs/dergiler.md` | ❌ |
| İrşad | `docs/irsad.md` | ❌ |
| Pazarlama SMS | `docs/pazarlama-sms.md` | ❌ |
| Pazarlama E-posta | `docs/pazarlama-eposta.md` | ❌ |
| Roller | `docs/roller.md` | ❌ |
| Yöneticiler | `docs/yoneticiler.md` | ❌ |
| Loglar | `docs/loglar.md` | ❌ |
| Paketler | `docs/paketler.md` | ❌ |

Her modülün kendi `docs/{modul}.md` dosyası detaylı spesifikasyon içerir. Genel panel kuralları için `docs/genel-panel-notlari.md`, teknik standartlar için `docs/teknik-kurallar.md` referans alınır.
