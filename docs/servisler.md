# Servisler ve API Referansı

Bu dosya projede kullanılan tüm dış servislerin dokümantasyon referansıdır.
Gerçek API anahtarları bu dosyada yer almaz — yalnızca sunucudaki `.env` dosyasında saklanır.

---

## 1. Digital Ocean Spaces

**Amaç:** Yüklenen tüm görseller (orijinal + optimize WebP), oluşturulan tüm PDF ve XLSX dosyaları.

**Kullanıldığı Modüller:** Haberler, Dergiler, Kurban Yönetimi, Bağış (makbuz PDF), Öğrenci E-Kayıt (belge)

**Dokümantasyon:** https://docs.digitalocean.com/products/spaces/

**Laravel Entegrasyonu:** `league/flysystem-aws-s3-v3` paketi, `config/filesystems.php` üzerinden `spaces` diski tanımlanır.

**ENV Değişkenleri:**
```
DO_ACCESS_KEY_ID=
DO_SECRET_ACCESS_KEY=
DO_DEFAULT_REGION=ams3
DO_BUCKET=kestanepazari
DO_ENDPOINT=https://ams3.digitaloceanspaces.com
DO_URL=https://cdn.kestanepazari.org.tr
DO_ROOT=img26
AWS_USE_PATH_STYLE_ENDPOINT=false
```

**Klasör Yapısı:**
```
img26/
  ori/     → Orijinal görseller
             img26/ori/{haber-slug}-original.jpg

  opt/     → Optimize görseller
             img26/opt/{haber-slug}-web.webp
             img26/opt/{haber-slug}-thumb.webp

  pdf26/   → Tüm PDF dosyaları
             img26/pdf26/bagis/{yil}/{ref-no}.pdf
             img26/pdf26/ekayit/{yil}/{ref-no}.pdf
             img26/pdf26/kurban/{yil}/{ref-no}.pdf
             img26/pdf26/raporlar/{yil}/{tarih}-rapor.pdf

  xlsx26/  → Tüm Excel dosyaları
             img26/xlsx26/bagis/{yil}/{tarih}-bagis.xlsx
             img26/xlsx26/ekayit/{yil}/{tarih}-ekayit.xlsx
             img26/xlsx26/kurban/{yil}/{tarih}-kurban.xlsx
             img26/xlsx26/raporlar/{yil}/{tarih}-rapor.xlsx
```

**Notlar:**
- Her görsel yüklendiği haberin slug'una göre adlandırılır
- CDN URL: `https://cdn.kestanepazari.org.tr`
- `spatie/laravel-medialibrary` ile entegre çalışır

**Görsel Boyutları (16:9 standart):**

| Suffix | Boyut | Kullanım Yeri |
|---|---|---|
| `-lg.webp` | 1280×720 | Haber/etkinlik detay sayfası, manşet slider |
| `-og.webp` | 1200×675 | OG image — sosyal medya paylaşım önizlemesi |
| `-sm.webp` | 320×180 | Sidebar, ilgili haberler, küçük widget'lar |
| Manşet masaüstü | 1920×1080 | Full width slider |
| Manşet mobil | 768×432 | Mobil slider |

Yüklenen orijinal görsel boyutundan bağımsız olarak sistem 3 versiyon üretir (lg, og, sm).
Smart crop ile odak noktası algılanır — `intervention/image` paketi kullanılır.

---

## 2. SMS — İletişim Makinesi (Hermes API)

**Amaç:** Tüm SMS bildirimleri ve toplu SMS gönderimi.

**Kullanıldığı Modüller:** Bağış, Öğrenci E-Kayıt, Kurban Yönetimi, Pazarlama - SMS, Üyeler (OTP)

**Dokümantasyon:** https://api.iletisimmakinesi.com/sms-gateway-api/

**Laravel Entegrasyonu:** Özel `HermesService` sınıfı + Laravel Notification channel. Queue/Job ile asenkron çalışır.

**ENV Değişkenleri:**
```
ILETISIM_MAKINESI_USERNAME=
ILETISIM_MAKINESI_PASSWORD=
ILETISIM_MAKINESI_CUSTOMER_CODE=
ILETISIM_MAKINESI_API_KEY=
ILETISIM_MAKINESI_VENDOR_CODE=
```

**Notlar:**
- Tüm SMS'ler Türkçe karakter ile gönderilir (Unicode mod)
- API'den mevcut tüm rehber kayıtları çekilir ve sisteme aktarılır
- Yeni kayıtlar düzenli aralıklarla (Scheduler) API'ye otomatik senkronize edilir
- OTP gönderimi, otomatik bildirimler ve toplu kampanya SMS'leri bu servis üzerinden gider
- Bakiye sorgusu panel dashboard'unda gösterilir

---

## 3. E-posta — Zoho Zeptomail

**Amaç:** Tüm sistem e-postaları — bildirimler, makbuzlar, toplu kampanya mailleri.

**Kullanıldığı Modüller:** Bağış, Öğrenci E-Kayıt, Kurban Yönetimi, Pazarlama - E-posta, Üyeler (OTP, aktivasyon)

**Dokümantasyon:** https://www.zoho.com/zeptomail/

**Laravel Entegrasyonu:** `zoho/zeptomail` paketi veya özel Mailable driver. Queue/Job ile asenkron çalışır.

**ENV Değişkenleri:**
```
MAIL_MAILER=zeptomail
MAIL_FROM_ADDRESS=bildirim@n.kestanepazari.org.tr
MAIL_FROM_NAME="Kestanepazarı Öğrenci Yetiştirme Derneği"
ZEPTOMAIL_API_KEY=
ZEPTOMAIL_FROM_ADDRESS=bildirim@n.kestanepazari.org.tr
ZEPTOMAIL_FROM_NAME="Kestanepazarı Öğrenci Yetiştirme Derneği"
ZEPTOMAIL_TRACK_DOMAIN=metric.kestanepazari.org.tr
ZEPTOMAIL_AGENT_ALIAS=
```

**Notlar:**
- Zeptomail transactional mail için tasarlanmıştır
- Toplu gönderimde 50 alıcı/batch, batch'ler arası 2sn bekleme uygulanır
- İzleme domain'i: `metric.kestanepazari.org.tr`
- Açılma ve tıklama istatistikleri Zeptomail panelinden takip edilir

---

## 4. Google Servisleri

### 4a. Google Cloud AI (Gemini Pro)

**Amaç:** Haber metinlerinde imla düzeltme, özet oluşturma, kişi/kurum tespiti (NER).

**Kullanıldığı Modüller:** Haberler

**Dokümantasyon:** https://ai.google.dev/docs

**Laravel Entegrasyonu:** Özel `GeminiService` sınıfı. Queue/Job ile asenkron çalışır.

**ENV Değişkenleri:**
```
GEMINI_API_KEY=
GOOGLE_CLOUD_PROJECT_ID=
```

**Notlar:**
- Tüm AI işlemleri asenkron çalışır, kullanıcıyı bekletmez
- Hata durumunda log tutulur, editör manuel devam edebilir
- Türkçe dini/idari unvanlar için özel prompt tanımları `config/ai.php` üzerinden yönetilir

---

### 4b. Google Sheets

**Amaç:** Belirli raporların (bağış, e-kayıt, kurban) Google Sheets'e otomatik aktarımı.

**Kullanıldığı Modüller:** Bağış, Öğrenci E-Kayıt, Kurban Yönetimi

**Dokümantasyon:** https://developers.google.com/sheets/api

**Laravel Entegrasyonu:** `google/apiclient` paketi, Service Account kimlik doğrulama.

**ENV Değişkenleri:**
```
GOOGLE_SPREADSHEET_ID=
GOOGLE_SERVICE_ACCOUNT_JSON=
```

**Notlar:**
- Service Account JSON base64 encode edilerek ENV'de saklanır
- Spreadsheet ID config üzerinden modül bazlı ayrılabilir

---

### 4c. Google Maps / Places API

**Amaç:** Etkinlik konumu arama, otomatik tamamlama ve harita gösterimi.

**Kullanıldığı Modüller:** Etkinlikler

**Dokümantasyon:** https://developers.google.com/maps/documentation/places/web-service

**ENV Değişkenleri:**
```
GOOGLE_MAPS_API_KEY=           (backend — kısıtsız)
GOOGLE_MAPS_API_KEY_PUBLIC=    (frontend — domain kısıtlı)
```

**Notlar:**
- Backend ve frontend için ayrı key kullanılır
- Frontend key Google Console'dan domain bazlı kısıtlanır
- Places Autocomplete: konum arama
- Maps Embed: form içi mini harita + etkinlik detay sayfası haritası

---

### 4d. Google Analytics 4 (GA4) + Google Tag Manager (GTM)

**Amaç:** Frontend kullanıcı davranışı takibi, dönüşüm ölçümü. Özellikle Bağış ve E-Kayıt akışları için kritik.

**Kullanıldığı Modüller:** Haberler, Etkinlikler, Kurumsal Sayfalar, Bağış, Öğrenci E-Kayıt, Kurban Yönetimi, Mezunlar

**Dokümantasyon:**
- GA4: https://developers.google.com/analytics/devguides/collection/ga4
- GTM: https://developers.google.com/tag-manager

**Entegrasyon:** GTM container kodu tüm frontend sayfalarına eklenir. Dönüşüm eventleri GTM üzerinden yönetilir — kod değişikliği yapmadan yeni event eklenebilir.

**ENV Değişkenleri:**
```
VITE_GTM_ID=GTM-XXXXXXX
VITE_GA4_ID=G-XXXXXXXXXX
```

**Takip Edilecek Dönüşümler:**

| Event | Tetikleyici | Modül |
|---|---|---|
| `bagis_baslat` | Bağış formu açıldığında | Bağış |
| `bagis_tamamla` | Ödeme başarılı | Bağış |
| `bagis_iptal` | Ödeme başarısız / form terk | Bağış |
| `ekayit_baslat` | E-Kayıt formu açıldığında | Öğrenci E-Kayıt |
| `ekayit_tamamla` | Kayıt başarılı | Öğrenci E-Kayıt |
| `kurban_tamamla` | Kurban kaydı tamamlandı | Kurban Yönetimi |
| `uye_kayit` | Yeni üye oluştu | Üyeler |
| `uye_giris` | Üye giriş yaptı | Üyeler |
| `haber_oku` | Haber detay sayfası görüntülendi | Haberler |
| `etkinlik_goruntule` | Etkinlik detay sayfası görüntülendi | Etkinlikler |

**Notlar:**
- Bağış tutarı `value` parametresiyle GA4'e gönderilir (e-ticaret dönüşüm modeli)
- Bağış dönüşümleri ilerleyen aşamada Google Ads'e de aktarılabilir
- `dataLayer.push()` ile Laravel Blade şablonlarından event tetiklenir

---

## 5. Ödeme Servisleri

### 5a. Albaraka Sanal Pos (Posnet) — Birincil

**Amaç:** Tüm bağış ve kurban ödemeleri varsayılan olarak buradan geçer.

**Kullanıldığı Modüller:** Bağış, Kurban Yönetimi

**ENV Değişkenleri:**
```
ALBARAKA_MERCHANT_ID=
ALBARAKA_TERMINAL_ID=
ALBARAKA_POSNET_KEY=
ALBARAKA_TEST_MODE=false
```

---

### 5b. Paytr — Yedek

**Amaç:** Albaraka'da teknik sorun çıkması durumunda devreye giren yedek sağlayıcı. Kullanıcıya seçim sunulmaz, sistem otomatik yönetir.

**Kullanıldığı Modüller:** Bağış, Kurban Yönetimi

**Dokümantasyon:** https://dev.paytr.com/

**ENV Değişkenleri:**
```
PAYTR_MERCHANT_ID=
PAYTR_MERCHANT_KEY=
PAYTR_MERCHANT_SALT=
PAYTR_TEST_MODE=false
```

---

### Ödeme Genel Notları

- **Birincil: Albaraka** — tüm ödemeler buradan geçer
- **Yedek: Paytr** — Albaraka'da sorun çıkınca devreye girer, kullanıcıya seçim sunulmaz
- İki sağlayıcı ortak `PaymentService` üzerinden yönetilir
- Her sağlayıcı için ayrı callback/webhook endpoint'leri tanımlanır

**Hatalı İşlem Takibi:**

| Alan | Açıklama |
|---|---|
| Tablo | `odeme_hatalari` |
| Kaydedilenler | Hata kodu, sağlayıcıdan dönen tam hata mesajı, işlem tutarı, kişi bilgisi, sağlayıcı adı, tarih/saat |
| Panel | Hatalı işlemler ayrı ekranda listelenir — tarih, sağlayıcı, hata türüne göre filtrelenebilir |
| Bildirim | Her hatalı işlemde SMS + e-posta ile ilgili yöneticilere anlık bildirim |
| Yapılandırma | Bildirim alacak kişi/numaralar `config/payment.php` üzerinden yönetilir |

---

## Genel Notlar

- Tüm API anahtarları yalnızca sunucudaki `.env` dosyasında saklanır
- `.env` dosyası Git'e commit edilmez
- Tüm servis çağrıları `try/catch` ile sarılır, hatalar `Loglar` modülüne yazılır
- Servis sınıfları `app/Services/` altında:

| Sınıf | Sorumluluk |
|---|---|
| `GeminiService` | Google Cloud AI |
| `HermesService` | SMS gönderimi |
| `ZeptomailService` | E-posta gönderimi |
| `PaymentService` | Ödeme (Albaraka birincil, Paytr yedek) |
| `SpacesService` | Dosya depolama (DO Spaces) |

- Test ortamı için her servisin mock/fake implementasyonu yazılır
