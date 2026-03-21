# Modül: Pazarlama - SMS

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Pazarlama - SMS |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Orta — Üyeler ve diğer modüller tamamlandıktan sonra |
| API | Hermes API (İletişim Makinesi) — bkz. servisler.md |
| Bağımlı Modüller | Üyeler, Mezunlar, Bağış, Kurban Yönetimi, Loglar, Roller, Yöneticiler |
| İYS | Vakıf muafiyeti — forceIYS: false, iysBrandCode gönderilmez |
| Türkçe Karakter | isNLSSAllowed: true (isUTF8Allowed ile birlikte kullanılamaz) |

---

## Roller ve Yetkiler

| Rol | Segment Yönetimi | Kampanya Oluştur | Gönder | Rapor | Şablon Yönetimi |
|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ✅ |
| Pazarlama | ❌ | ✅ | ✅ | ✅ | ❌ |
| Diğerleri | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Veritabanı

### Tablo: `sms_sablonlar`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| ad | string(255) | ✅ | Şablon adı (iç kullanım) |
| metin | text | ✅ | SMS metni. Değişkenler: `{AD_SOYAD}`, `{KURUM}`, `{TARIH}`, `{TUTAR}` vb. |
| tip | enum | ✅ | `pazarlama` / `bildirim` / `otp` / `kurban` / `bagis` |
| aktif | boolean | ✅ | true |
| created_at | timestamp | ✅ | — |

---

### Tablo: `sms_segmentler`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| ad | string(255) | ✅ | Segment adı (örn: İzmir Mezunları) |
| tip | enum | ✅ | `dinamik` / `manuel` |
| kurallar | json | ❌ | Dinamik segment kural tanımı (aşağıda detaylı) |
| aktif | boolean | ✅ | true |
| son_hesaplama | timestamp | ❌ | Kişi sayısı son ne zaman hesaplandı |
| kisi_sayisi | integer | ❌ | Son hesaplamadaki kişi sayısı (önbellek) |
| created_at | timestamp | ✅ | — |
| updated_at | timestamp | ✅ | — |

---

### Tablo: `sms_segment_kisiler` (Manuel segmentler için)

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| segment_id | foreignId | ✅ | → `sms_segmentler` |
| uye_id | foreignId | ✅ | → `uyeler` |
| created_at | timestamp | ✅ | — |

---

### Tablo: `sms_kampanyalar`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | — |
| ad | string(255) | ✅ | — | Kampanya adı (iç kullanım) |
| sablon_id | foreignId | ❌ | null | → `sms_sablonlar` (şablon yoksa metin alanı) |
| metin | text | ❌ | null | Şablon seçilmezse serbest metin |
| segment_idler | json | ✅ | — | Hedef segmentler: `[1,2,3]` |
| durum | enum | ✅ | taslak | `taslak` / `onay_bekliyor` / `gonderiliyor` / `tamamlandi` / `iptal` |
| otomatik_tetik | boolean | ✅ | false | Otomatik tetiklemeli mi |
| tetik_kurali | json | ❌ | null | Tetikleme kuralı (aşağıda detaylı) |
| tetik_aktif | boolean | ✅ | false | Otomatik tetikleme aktif/pasif |
| planli_tarih | timestamp | ❌ | null | Zamanlı gönderim tarihi |
| hermes_transaction_id | string | ❌ | null | Hermes API transaction ID |
| hermes_async_req_id | string | ❌ | null | Asenkron gönderimde reqLogId |
| toplam_alici | integer | ❌ | 0 | Gönderim öncesi hesaplanan alıcı sayısı |
| toplam_sms | integer | ❌ | 0 | Toplam SMS adedi (Türkçe karakter nedeniyle bölünebilir) |
| basarili | integer | ❌ | 0 | Başarılı gönderim sayısı |
| basarisiz | integer | ❌ | 0 | Başarısız gönderim sayısı |
| bekleyen | integer | ❌ | 0 | Bekleyen gönderim sayısı |
| gonderen_id | foreignId | ✅ | — | → `yoneticiler` |
| gonderim_tarihi | timestamp | ❌ | null | Gerçek gönderim zamanı |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |

---

### Tablo: `sms_gonderim_gecmisi`

Spam engeli + her kişiye ne zaman SMS gönderildiğinin kaydı.

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| uye_id | foreignId | ✅ | → `uyeler` |
| kampanya_id | foreignId | ✅ | → `sms_kampanyalar` |
| telefon | string(20) | ✅ | Gönderim anındaki numara |
| durum | enum | ✅ | `basarili` / `basarisiz` / `beklemede` |
| hermes_packet_id | string | ❌ | Hermes paket ID |
| hata_kodu | string(100) | ❌ | Hermes hata kodu |
| created_at | timestamp | ✅ | — |

---

### Tablo: `sms_otomatik_kampanyalar`

Scheduler tarafından periyodik çalışan kampanyalar.

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kampanya_id | foreignId | ✅ | → `sms_kampanyalar` |
| periyot | enum | ✅ | `gunluk` / `haftalik` / `aylik` / `ozel` |
| cron | string(100) | ❌ | Özel cron ifadesi |
| aktif | boolean | ✅ | true |
| son_calisma | timestamp | ❌ | — |

---

## Dinamik Segment Kural Yapısı

```json
{
  "operator": "AND",
  "kurallar": [
    {
      "modul": "mezun",
      "alan": "ikamet_il",
      "operator": "=",
      "deger": "İzmir"
    },
    {
      "modul": "mezun",
      "alan": "mezuniyet_yili",
      "operator": "between",
      "deger": [2015, 2020]
    },
    {
      "modul": "bagis",
      "alan": "tur",
      "operator": "yapti",
      "deger": "fitre",
      "yil": 2025
    },
    {
      "modul": "uye",
      "alan": "rozet",
      "operator": "=",
      "deger": "mezun"
    },
    {
      "modul": "bagis",
      "alan": "tur",
      "operator": "yapti",
      "deger": "zekat"
    }
  ]
}
```

### Desteklenen Segment Kuralları

| Modül | Alan | Operatör | Örnek |
|---|---|---|---|
| `uye` | rozet | `=` | mezun / bagisci / veli |
| `uye` | kayit_tarihi | `between` | tarih aralığı |
| `uye` | sms_abonelik | `=` | true/false |
| `mezun` | ikamet_il | `=` | İzmir |
| `mezun` | ikamet_ilce | `=` | Karşıyaka |
| `mezun` | mezuniyet_yili | `between` | 2015-2020 |
| `mezun` | kurum_id | `=` | kurum ID |
| `mezun` | hafiz | `=` | true/false |
| `bagis` | tur | `yapti` / `yapmadi` | zekat, fitre vb. |
| `bagis` | tur | `yapti_yil` | belirli yılda yaptı |
| `bagis` | sepet_terk | `=` | true |
| `bagis` | toplam_tutar | `>` / `<` / `between` | tutar |

---

## Otomatik Tetikleme Kuralları

```json
{
  "tip": "sepet_terk",
  "bekleme_suresi_saat": 8,
  "tekrar_gonderme_gun": 30
}
```

```json
{
  "tip": "bagis_yapmadi",
  "modul": "bagis",
  "tur": "fitre",
  "hicri_ay_oncesi": 5
}
```

| Tetikleme Tipi | Açıklama |
|---|---|
| `sepet_terk` | Sepet 8 saat terk edilirse |
| `bagis_yapmadi` | Belirli bağış türünü yapmamış üyelere |
| `dogum_gunu` | Üye doğum günü (varsa) |
| `yildonumu` | Mezuniyet yıl dönümü |

---

## İş Kuralları

### SMS Gönderim Akışı

```
Kampanya oluşturulur / segment seçilir
        │
        ▼
Mesaj yazılır (şablondan veya serbest metin)
Değişkenler: {AD_SOYAD}, {KURUM}, {TARIH} vb.
        │
        ▼
"Önizle" butonuna basılır:
  calculateCost API çağrısı (isNLSSAllowed: true)
  Sonuçlar gösterilir:
    - Toplam alıcı: 342 kişi
    - SMS başına karakter: 70 (Türkçe mod)
    - Mesaj XX karakterde → X SMS'e bölünecek
    - Toplam SMS adedi: 684
    - Deduplicate: 5 mükerrer numara çıkarıldı
        │
        ▼
Spam engeli kontrolü:
  Son 72 saat içinde genel mesaj aldı mı?
  Son 7 günde kampanya aldı mı?
  Uygunsalar listeden çıkarılır, panel'de gösterilir:
    "48 kişi spam filtresi nedeniyle çıkarıldı"
        │
        ▼
SMS abonelik kontrolü:
  sms_abonelik = false olanlar çıkarılır
  "12 kişi abonelik iptali nedeniyle çıkarıldı"
        │
        ▼
Nihai önizleme:
  Net alıcı: 282 kişi
  Toplam SMS: 564
  [Gönder] [Zamanla] [İptal] butonları
        │
        ▼
Gönder / Zamanla:
  282 kişi ise → sendSMS (senkron)
  282+ kişi ise → SetAsyncTransaction → confirmAsyncTransaction
  Zamanlı ise → sendDate parametresi ile
        │
        ▼
Hermes transaction_id kaydedilir
Queue ile her 10 dakikada bir durum sorgulanır:
  getTransactionDetails → sms_gonderim_gecmisi güncellenir
        │
        ▼
Tamamlandı → panel bildirimi
  "282 kişiye SMS gönderildi: 275 başarılı, 7 başarısız"
  Başarısız olanlar için "Tekrar Dene" butonu
    → sendToAllFailedPacketsOfTransaction
```

---

### Spam Engeli Kuralları

| Mesaj Tipi | Minimum Araklık |
|---|---|
| Genel / pazarlama mesajı | 72 saat |
| Kampanya mesajı | 7 gün |
| Otomatik tetiklemeli | 30 gün (aynı tetikleyici) |
| Günlük limit | Kişi başına 1 SMS/gün |

Kontrol `sms_gonderim_gecmisi` tablosundan yapılır.
Spam filtresine takılan kişiler gönderimden çıkarılır, sayıları önizlemede gösterilir.

---

### Abonelik / Çıkış

```
Üye SMS aboneliğinden çıkmak istediğinde:
  Profil sayfası → SMS bildirimleri toggle kapalı
  VEYA gelen SMS'deki çıkış linki (usingIvtboxOptoutSMS: true)
  VEYA "IPTAL" yazıp gönderme (Hermes ivtbox sistemi)
        │
        ▼
uyeler.sms_abonelik = false
Bir sonraki gönderimde bu kişi otomatik çıkarılır
```

---

### İletişim Makinesi ↔ Sistem Senkronizasyonu

```
İlk kurulum:
  Hermes panelindeki mevcut tüm kişiler API ile çekilir
  uyeler tablosuyla telefon bazlı eşleştirilir

Süregelen sync (Scheduler — günlük 03:00):
  Yeni üyeler → Hermes adres defterine eklenir
  Abonelik iptal edenler → Hermes'ten çıkarılır
  Telefon değişikliği → Hermes'te güncellenir

Gönderim sonrası (Scheduler — 10 dakikada bir):
  getTransactionDetails ile durum sorgulanır
  sms_gonderim_gecmisi güncellenir
  Tamamlanan kampanyalar "tamamlandi" yapılır
```

---

## Backend Admin Panel

### Dashboard

- **Kalan SMS bakiyesi** (Hermes `/bakiye` endpoint — anlık)
- **Bu ay gönderilen** SMS adedi ve başarı oranı
- **Bekleyen kampanyalar** (zamanlanmış)
- **Aktif otomatik kampanyalar**
- **Son 10 gönderim** özet listesi

---

### Segment Yönetimi

#### Segment Listesi
| Kolon | Açıklama |
|---|---|
| Segment Adı | — |
| Tip | Dinamik / Manuel rozet |
| Kişi Sayısı | Son hesaplama (güncelle butonu) |
| İşlemler | Düzenle / Sil / Kişileri Gör |

#### Segment Ekle / Düzenle
- Ad
- Tip seçimi: Dinamik / Manuel
- **Dinamik:** Kural oluşturucu (modül + alan + operator + değer, AND/OR)
  - "Önizle" butonu → anlık kişi sayısı hesaplanır
- **Manuel:** Üye arama + çoklu seçim

---

### Şablon Yönetimi

- Şablon listesi: ad, tip, önizleme, aktif/pasif
- Şablon ekle/düzenle: metin + değişken yardımcısı (`{AD_SOYAD}` vb.)
- Önizleme: örnek kişiyle mesaj nasıl görünür

---

### Kampanya Oluştur / Gönder

**Adım 1 — İçerik:**
- Kampanya adı
- Şablon seç VEYA serbest metin yaz
- Mesaj önizlemesi (canlı karakter sayacı)
  - Toplam karakter sayısı
  - Türkçe modda SMS başına 70 karakter
  - Kaç SMS'e bölüneceği anlık gösterilir

**Adım 2 — Hedef:**
- Segment seçimi (çoklu, deduplicate uyarısı)
- Tahmini alıcı sayısı

**Adım 3 — Önizle & Gönder:**
- `calculateCost` API çağrısı sonucu:
  - Net alıcı (spam + abonelik filtresinden sonra)
  - Toplam SMS adedi
  - Çıkarılan kişi sayısı ve sebebi
- Gönderim seçeneği: Şimdi Gönder / Zamanla (tarih-saat)
- Otomatik tetikleme ayarları (opsiyonel)

---

### Kampanya Listesi

| Kolon | Açıklama |
|---|---|
| Kampanya Adı | — |
| Durum | Taslak / Gönderiliyor / Tamamlandı / İptal |
| Alıcı | Net alıcı sayısı |
| Başarılı / Başarısız | — |
| Tarih | Gönderim tarihi |
| İşlemler | Detay / Tekrar Dene (başarısız varsa) |

---

### Kampanya Detay

- Kampanya bilgileri (ad, metin, segment, tarih)
- Gönderim özeti: toplam / başarılı / başarısız / bekleyen
- Kişi bazlı detay listesi (Hermes'ten `getTransactionDetails`)
  - Filtre: başarılı / başarısız / bekleyen
  - Her satır: telefon, durum, paket ID, tarih
- Başarısız olanlar için "Tekrar Dene" butonu

---

### Otomatik Kampanyalar

- Aktif otomatik kampanyalar listesi
- Her birinde: aktif/pasif toggle, son çalışma, tetikleme kuralı
- Yeni otomatik kampanya oluştur
- Kampanya geçmişi (kaç kez tetiklendi, toplam gönderim)

---

## Hermes API Endpoint Kullanım Özeti

| Endpoint | Ne Zaman |
|---|---|
| `sendSMS` | Küçük toplu gönderim |
| `SetAsyncTransaction` + `confirmAsyncTransaction` | Büyük toplu gönderim |
| `calculateCost` | Önizleme ekranında |
| `getTransactionSummariesWithinDates` | Dashboard istatistikleri |
| `getTransactionDetails` | Kampanya detay raporu |
| `getPacketSummaryWithinDatesByDestination` | Üye bazlı SMS geçmişi |
| `getCombinedDetailListForTransactions` | Toplu rapor |
| `sendToAllFailedPacketsOfTransaction` | Başarısız tekrar dene |
| `abortForwardDatedTransaction` | Zamanlanmış iptal |
| `getScheduledTransactions` | Zamanlanmış liste |
| `setAnnualSmsRule` | Yıllık tekrarlayan kural |
| `clientTransactionId` | Mükerrer gönderim engeli |

---

## Belirsizlikler / Açık Kararlar

- [ ] Otomatik tetiklemeli kampanyalarda "aynı tetikleyici 30 gün" kuralı tüm tipler için mi geçerli, bazıları için daha kısa olabilir mi?
- [ ] SMS bakiye kritik seviyeye düştüğünde (örn: 500 SMS kaldı) admin'e bildirim gönderilsin mi?
- [ ] Hermes adres defteri senkronizasyonu: bizim sistemde silinmiş (soft delete) üyeler Hermes'ten de çıkarılsın mı?
