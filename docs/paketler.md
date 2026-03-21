# Laravel Paket Listesi

Projede kullanılacak tüm Composer ve NPM paketleri.

---

## Composer Paketleri (Backend)

### Çekirdek / Auth
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `laravel/framework` | ^11.x | — |
| `laravel/sanctum` | ^4.x | Üye guard (frontend login) |
| `spatie/laravel-permission` | ^6.x | Roller, izinler, admin guard |

---

### Admin Panel
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `filament/filament` | ^3.x | Tüm admin panel |
| `filament/spatie-laravel-media-library-plugin` | ^3.x | Filament + medya entegrasyonu |
| `filament/spatie-laravel-settings-plugin` | ^3.x | Panel ayarları |

---

### Medya ve Dosya
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `spatie/laravel-medialibrary` | ^11.x | Görsel yönetimi (DO Spaces) |
| `intervention/image` | ^3.x | Görsel optimize, crop, WebP dönüşüm |
| `league/flysystem-aws-s3-v3` | ^3.x | Digital Ocean Spaces bağlantısı |

---

### Veritabanı / ORM
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `spatie/laravel-activitylog` | ^4.x | Loglar modülü |
| `spatie/laravel-sluggable` | ^3.x | Haberler, Etkinlikler, tüm içerik modülleri |
| `spatie/laravel-honeypot` | ^4.x | Form spam koruması — bkz. guvenlik.md |

---

### Arama
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `laravel/scout` | ^10.x | Arama altyapısı |
| `teamtnt/laravel-scout-tntsearch-driver` | ^13.x | TNTSearch driver |
| `teamtnt/tntsearch` | ^3.x | TNTSearch motoru |

---

### PDF ve Excel
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `barryvdh/laravel-dompdf` | ^2.x | Makbuz, evrak, baskı önizleme PDF |
| `maatwebsite/laravel-excel` | ^3.x | Excel raporlama (Bağış, E-Kayıt, Mezunlar vb.) |

---

### SMS ve E-posta
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `zoho/zeptomail` | ^2.x | Zeptomail e-posta gönderimi |

> **Not:** Hermes SMS API için mevcut hazır Laravel paketi yok. Özel `HermesService` sınıfı yazılacak.

---

### Takvim / Tarih
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `khalid-alsaqqa/laravel-hijri` | ^2.x | Hicri takvim hesaplama (Bağış açılış/kapanış) |
| `nesbot/carbon` | ^3.x | Tarih işlemleri (Laravel ile gelir) |

---

### Ödeme
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `guzzlehttp/guzzle` | ^7.x | Paytr + Albaraka Posnet HTTP istekleri |

> **Not:** Paytr ve Albaraka için özel `PaymentService` sınıfı yazılacak. Hazır paket kullanılmıyor.

---

### Google Entegrasyonları
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `google/apiclient` | ^2.x | Google Sheets entegrasyonu |

> **Not:** Gemini AI için özel `GeminiService` sınıfı + Guzzle kullanılacak.

---

### Güvenlik
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `pragmarx/google2fa-laravel` | ^8.x | 2FA (opsiyonel) — bkz. guvenlik.md |

---

### Yardımcı
| Paket | Versiyon | Kullanım Yeri |
|---|---|---|
| `spatie/laravel-settings` | ^3.x | Uygulama ayarları (iletişim telefonu vb.) |
| `spatie/laravel-data` | ^4.x | DTO ve data transfer nesneleri |
| `tightenco/ziggy` | ^2.x | Laravel route'larını frontend'e aktarma |

---

## NPM Paketleri (Frontend - Tailwind CSS)

### Temel
| Paket | Kullanım Yeri |
|---|---|
| `tailwindcss` | Tüm frontend stilleri |
| `@tailwindcss/forms` | Form stilleri |
| `@tailwindcss/typography` | Haber/içerik metin stilleri |
| `alpinejs` | Interaktif UI (popup, toggle, modal) |
| `axios` | API istekleri |
| `vite` | Asset bundler (Laravel Vite) |
| `laravel-vite-plugin` | Laravel Vite entegrasyonu |

---

### Harita ve Konum
| Paket | Kullanım Yeri |
|---|---|
| `@googlemaps/js-api-loader` | Google Maps (Etkinlikler, Kurumsal Sayfalar, İrşad) |

---

### UI Bileşenler
| Paket | Kullanım Yeri |
|---|---|
| `swiper` | Ana sayfa slider / manşet |
| `glightbox` | Galeri lightbox |
| `flatpickr` | Tarih/saat seçici (Frontend formlar) |

---

### SEO / Analytics
| Paket | Kullanım Yeri |
|---|---|
| `@gtm-support/vue-gtm` veya vanilla GTM | Google Tag Manager |

---

## Özet — Spatie Paket Grubu

Projede yoğun Spatie kullanımı var, hepsini listeleyeyim:

| Paket | Amaç |
|---|---|
| `spatie/laravel-permission` | Roller ve izinler |
| `spatie/laravel-medialibrary` | Medya yönetimi |
| `spatie/laravel-activitylog` | Activity log |
| `spatie/laravel-sluggable` | Otomatik slug üretimi |
| `spatie/laravel-honeypot` | Spam koruması |
| `spatie/laravel-settings` | Uygulama ayarları |
| `spatie/laravel-data` | DTO yapısı |

---

## Özel Servis Sınıfları (Paket Değil, Yazılacak)

| Sınıf | Dosya | Açıklama |
|---|---|---|
| `HermesService` | `app/Services/HermesService.php` | İletişim Makinesi SMS API |
| `GeminiService` | `app/Services/GeminiService.php` | Google Cloud AI |
| `PaymentService` | `app/Services/PaymentService.php` | Albaraka + Paytr ödeme |
| `SpacesService` | `app/Services/SpacesService.php` | DO Spaces dosya yönetimi |
| `ZeptomailService` | `app/Services/ZeptomailService.php` | Zeptomail e-posta |
| `HijriService` | `app/Services/HijriService.php` | Hicri takvim hesaplama |
| `LevenshteinService` | `app/Services/LevenshteinService.php` | Kişi/kurum benzerlik hesaplama |
