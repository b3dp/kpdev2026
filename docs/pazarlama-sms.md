# Modül: Pazarlama - SMS

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Pazarlama - SMS |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Orta |
| API | Hermes (İletişim Makinesi) |
| Base URL | https://live.iletisimmakinesi.com/api |
| Bağımlı Modüller | Üyeler, E-Kayıt, Bağış, Loglar, Roller, Yöneticiler |
| IYS | Vakıf muafiyeti — forceIYS: false |
| Türkçe Karakter | isNLSSAllowed: true |
| İstek Formatı | POST, application/x-www-form-urlencoded |
| Response Formatı | XML |

---

## Roller ve Yetkiler

| Rol | Rehber | Hızlı SMS | Toplu SMS | Geçmiş | Aktarım |
|---|---|---|---|---|---|
| Admin | ✅ Tümü | ✅ | ✅ | ✅ Tümü | ✅ |
| SMS Yetkili Personel | ✅ Sadece kendi | ✅ | ✅ | ✅ Kendi | Role göre |
| Diğerleri | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## ENV Değişkenleri
```env
ILETISIM_MAKINESI_USERNAME=
ILETISIM_MAKINESI_PASSWORD=
ILETISIM_MAKINESI_CUSTOMER_CODE=01015662
ILETISIM_MAKINESI_API_KEY=
ILETISIM_MAKINESI_VENDOR_CODE=2
ILETISIM_MAKINESI_ORIGINATOR_ID=45605
ILETISIM_MAKINESI_ASYNC_LIMIT=500
ILETISIM_MAKINESI_VALIDITY_PERIOD=1440
```

config/services.php:
```php
'iletisim_makinesi' => [
    'username'        => env('ILETISIM_MAKINESI_USERNAME'),
    'password'        => env('ILETISIM_MAKINESI_PASSWORD'),
    'customer_code'   => env('ILETISIM_MAKINESI_CUSTOMER_CODE'),
    'api_key'         => env('ILETISIM_MAKINESI_API_KEY'),
    'vendor_code'     => env('ILETISIM_MAKINESI_VENDOR_CODE', 2),
    'originator_id'   => env('ILETISIM_MAKINESI_ORIGINATOR_ID', 45605),
    'async_limit'     => (int) env('ILETISIM_MAKINESI_ASYNC_LIMIT', 500),
    'validity_period' => (int) env('ILETISIM_MAKINESI_VALIDITY_PERIOD', 1440),
],
```

---

## Hermes API Endpoint Listesi

### Authentication
| Endpoint | Metod | Açıklama |
|---|---|---|
| `/UserGatewayWS/functions/authenticate` | POST | Token al |
| `/UserGatewayWS/functions/checkUserAccount` | POST | Hesap bilgisi |
| `/UserGatewayWS/functions/getActiveCreditTransfers` | POST | Kredi/bakiye bilgisi |

### SMS
| Endpoint | Metod | Açıklama |
|---|---|---|
| `/SMSGatewayWS/functions/sendSMS` | POST | Standart gönderim |
| `/SMSGatewayWS/functions/SetAsyncTransaction` | POST | Büyük toplu async |
| `/SMSGatewayWS/functions/confirmAsyncTransaction` | POST | Async onay |
| `/SMSGatewayWS/functions/calculateCost` | POST | Maliyet hesabı |
| `/SMSGatewayWS/functions/getTransactionSummariesWithinDates` | POST | Özet rapor |
| `/SMSGatewayWS/functions/getTransactionDetails` | POST | Detay rapor |
| `/SMSGatewayWS/functions/abortScheduledTransaction` | POST | Zamanlanmış iptal |
| `/SMSGatewayWS/functions/sendToAllFailedPacketsOfTransaction` | POST | Başarısız tekrar |

### OTP
| Endpoint | Metod | Açıklama |
|---|---|---|
| `/singleshotSMS` | POST | OTP gönderim |
| `/singleshotSMSStatusById` | POST | OTP durum sorgu |

---

## Token Üretimi

Token her oturum başında authenticate endpoint'inden alınır:
```
POST /UserGatewayWS/functions/authenticate
Parametreler: userName, userPass, customerCode, apiKey, vendorCode
Response XML: //TOKEN_NO
```

Token cache'lenir (60 dakika), süresi dolunca yenilenir.

---

## Veritabanı

### Tablo: `sms_kisiler`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| telefon | string(20) | ✅ | Unique — normalize edilmiş 5xxxxxxxxx |
| ad_soyad | string(255) | ❌ | Opsiyonel |
| notlar | text | ❌ | İç not |
| created_by | foreignId | ✅ | → yoneticiler |
| created_at | timestamp | ✅ | — |
| updated_at | timestamp | ✅ | — |
| deleted_at | timestamp | ❌ | Soft delete |

### Tablo: `sms_listeler`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| ad | string(255) | ✅ | Liste adı |
| sahip_yonetici_id | foreignId | ❌ | Liste sahibi yönetici |
| created_at | timestamp | ✅ | — |
| updated_at | timestamp | ✅ | — |

### Tablo: `sms_liste_kisiler`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| liste_id | foreignId | ✅ | → sms_listeler |
| kisi_id | foreignId | ✅ | → sms_kisiler |
| created_at | timestamp | ✅ | — |

Unique: liste_id + kisi_id

### Tablo: `sms_gonderimler`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| yonetici_id | foreignId | ✅ | → yoneticiler |
| tip | enum | ✅ | `hizli` / `toplu` / `bildirim` |
| mesaj | text | ✅ | Gönderilen mesaj |
| liste_idler | json | ❌ | Seçilen liste ID'leri |
| alici_sayisi | integer | ✅ | 0 |
| basarili | integer | ✅ | 0 |
| basarisiz | integer | ✅ | 0 |
| bekleyen | integer | ✅ | 0 |
| durum | enum | ✅ | `beklemede` / `gonderiliyor` / `tamamlandi` / `basarisiz` / `iptal` |
| hermes_transaction_id | string | ❌ | — |
| hermes_async_req_id | string | ❌ | — |
| planli_tarih | timestamp | ❌ | — |
| created_at | timestamp | ✅ | — |
| updated_at | timestamp | ✅ | — |

### Tablo: `sms_gonderim_alicilari`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| gonderim_id | foreignId | ✅ | → sms_gonderimler |
| telefon | string(20) | ✅ | — |
| durum | enum | ✅ | `beklemede` / `basarili` / `basarisiz` |
| hermes_packet_id | string | ❌ | — |
| hata_kodu | string(100) | ❌ | — |
| created_at | timestamp | ✅ | — |

---

## HermesService

Dosya: `app/Services/HermesService.php`

### Kurallar
- Tüm config değerlerini `config('services.iletisim_makinesi.*')` ile oku
- Token `authenticate()` metodundan alınır, 60 dakika cache'lenir
- Tüm istekler POST, `application/x-www-form-urlencoded`
- Tüm response XML — `simplexml_load_string()` ile parse
- `isNLSSAllowed: true`, `isUTF8Allowed: false` (ikisi aynı anda true OLAMAZ)
- `forceIYS: false`
- `isRepeatingDestinationAllowed: false`
- Telefon normalize: sadece rakam, başındaki 90 veya 0 kaldır → 5xxxxxxxxx
- Hata: Log::error() yaz, exception fırlat
- Başarı: Log::info() yaz

### Metodlar
```
authenticate(): string — token al, cache'le
telefonNormalize(string): string — numara temizle
telefonListesiNormalize(array): array — liste temizle + unique
xmlParse(string): array — XML parse et
postIstegi(string $endpoint, array $params): string — HTTP POST yap
sendSMS(array $telefonlar, string $mesaj, ?string $sendDate): array
setAsyncTransaction(array $telefonlar, string $mesaj, ?string $sendDate): array
confirmAsyncTransaction(int $reqLogId): bool
calculateCost(array $telefonlar, string $mesaj): array
getTransactionDetails(int $transactionId): array
getTransactionSummaries(string $baslangic, string $bitis): array
retryFailed(int $transactionId): array
cancelScheduled(int $transactionId): bool
akillıGonder(array $telefonlar, string $mesaj, ?string $sendDate): array
checkUserAccount(): string — ham XML döner
getActiveCreditTransfers(int $serviceId = 1): string — ham XML döner
```

---

## Faz Planı

### Faz 10A-1 — HermesService + Test
- config/services.php güncelle
- HermesService yaz
- Test: 905326847101 numarasına gönderim
- Test: calculateCost, getTransactionDetails
- Test: checkUserAccount ve getActiveCreditTransfers ham response

### Faz 10A-2 — Pazarlama SMS Paneli
- Migration'lar
- Filament sayfaları: Rehber, Hızlı SMS, Toplu SMS, Geçmiş
- Scheduler: her 10 dakika gönderim durumu güncelle

### Faz 10A-3 — Numara Aktarımı
- Excel import
- Her kullanıcı için kendi `2026NisanOncesi` listesi otomatik oluştur
- Mükerrer kontrolü

### Faz 10A-4 — Bildirim SMS'leri
- E-Kayıt, Bağış job'ları

### Faz 10A-5 — OTP SMS
- OtpService::smsDonder() tamamla
- singleshotSMS endpoint entegrasyonu

---

## Kodlama Kuralları

### YASAK
- migrate:fresh, migrate:reset, migrate:rollback, db:wipe, db:seed
- ENV'den direkt okuma — config() kullan
- isUTF8Allowed ve isNLSSAllowed aynı anda true
- {!! !!} blade'de
- hasAnyPermissionTo() — hasAnyPermission() kullan
- maatwebsite/excel — openspout kullan
- ->date() / ->dateTime() — formatStateUsing() kullan

### Terminal
```bash
su -s /bin/bash b3dp2026 -c "cd /var/www/vhosts/2026.kestanepazari.org.tr/httpdocs && /opt/plesk/php/8.2/bin/php artisan ..."
```

### Log Prefix
`[HermesService]`