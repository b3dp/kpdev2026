# Modül: Pazarlama - E-posta

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Pazarlama - E-posta |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Orta — SMS modülüyle paralel |
| API | Zoho Zeptomail — bkz. servisler.md |
| Bağımlı Modüller | Üyeler, Mezunlar, Bağış, Kurban Yönetimi, Dergiler, Loglar, Roller, Yöneticiler |
| Segment Sistemi | SMS modülüyle ortak — bkz. pazarlama-sms.md |

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

### Tablo: `eposta_sablonlar`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| ad | string(255) | ✅ | Şablon adı (iç kullanım) |
| konu | string(255) | ✅ | Mail konusu. Değişkenler: `{AD_SOYAD}` vb. |
| icerik | longText | ✅ | HTML mail içeriği |
| tip | enum | ✅ | `pazarlama` / `bildirim` / `makbuz` / `kurban` / `bagis` / `ekayit` |
| aktif | boolean | ✅ | true |
| created_at | timestamp | ✅ | — |

---

### Tablo: `eposta_kampanyalar`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | — |
| ad | string(255) | ✅ | — | Kampanya adı (iç kullanım) |
| sablon_id | foreignId | ❌ | null | → `eposta_sablonlar` |
| konu | string(255) | ✅ | — | Mail konusu |
| icerik | longText | ❌ | null | Şablon yoksa serbest HTML içerik |
| segment_idler | json | ✅ | — | Hedef segmentler: `[1,2,3]` |
| durum | enum | ✅ | taslak | `taslak` / `gonderiliyor` / `tamamlandi` / `iptal` |
| otomatik_tetik | boolean | ✅ | false | Otomatik tetiklemeli mi |
| tetik_kurali | json | ❌ | null | Tetikleme kuralı — bkz. pazarlama-sms.md |
| tetik_aktif | boolean | ✅ | false | Aktif/pasif toggle |
| planli_tarih | timestamp | ❌ | null | Zamanlı gönderim |
| zeptomail_batch_id | string | ❌ | null | Zeptomail batch referansı |
| toplam_alici | integer | ❌ | 0 | Net alıcı sayısı |
| basarili | integer | ❌ | 0 | Başarılı gönderim |
| basarisiz | integer | ❌ | 0 | Başarısız gönderim |
| acilma_sayisi | integer | ❌ | 0 | Açılan mail sayısı (Zeptomail tracking) |
| tiklanma_sayisi | integer | ❌ | 0 | Tıklanan link sayısı |
| gonderen_id | foreignId | ✅ | — | → `yoneticiler` |
| gonderim_tarihi | timestamp | ❌ | null | — |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |

---

### Tablo: `eposta_gonderim_gecmisi`

Spam engeli + kişi bazlı gönderim takibi.

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| uye_id | foreignId | ✅ | → `uyeler` |
| kampanya_id | foreignId | ✅ | → `eposta_kampanyalar` |
| eposta | string(255) | ✅ | Gönderim anındaki e-posta |
| durum | enum | ✅ | `basarili` / `basarisiz` / `beklemede` |
| acildi | boolean | ✅ | false |
| tiklanma_sayisi | integer | ✅ | 0 |
| hata_mesaji | string(500) | ❌ | Zeptomail hata kodu/mesajı |
| created_at | timestamp | ✅ | — |

---

### Tablo: `eposta_otomatik_kampanyalar`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kampanya_id | foreignId | ✅ | → `eposta_kampanyalar` |
| periyot | enum | ✅ | `gunluk` / `haftalik` / `aylik` / `ozel` |
| cron | string(100) | ❌ | Özel cron ifadesi |
| aktif | boolean | ✅ | true |
| son_calisma | timestamp | ❌ | — |

---

## İş Kuralları

### E-posta Gönderim Akışı

```
Kampanya oluşturulur / segment seçilir
        │
        ▼
Şablon seç VEYA HTML içerik yaz
Konu satırı girilir
        │
        ▼
"Önizle" butonuna basılır:
  - Spam + abonelik filtresi uygulanır
  - Net alıcı sayısı hesaplanır
  - Çıkarılan kişi sayısı ve sebebi gösterilir
  - Örnek mail önizlemesi (modal içinde)
        │
        ▼
Spam engeli kontrolü:
  Son 72 saat genel mail aldı mı?
  Son 7 günde kampanya aldı mı?
  Uygunsalar listeden çıkarılır
        │
        ▼
Abonelik kontrolü:
  eposta_abonelik = false olanlar çıkarılır
        │
        ▼
Nihai önizleme:
  Net alıcı: X kişi
  [Gönder] [Zamanla] [İptal]
        │
        ▼
Gönderim (Queue/Job ile asenkron):
  40 kişilik batch'ler halinde gönderilir
  Batch'ler arası 10 saniye bekleme (Zeptomail rate limit)
  Her gönderim eposta_gonderim_gecmisi'ne kaydedilir
        │
        ▼
Tamamlandı → panel bildirimi
Açılma/tıklanma istatistikleri Zeptomail tracking ile
  → Scheduler (günlük) ile eposta_kampanyalar güncellenir
```

---

### Spam Engeli Kuralları

SMS modülüyle aynı yapı — bkz. pazarlama-sms.md.

| Mesaj Tipi | Minimum Aralık |
|---|---|
| Genel / pazarlama maili | 72 saat |
| Kampanya maili | 7 gün |
| Otomatik tetiklemeli | 30 gün (aynı tetikleyici) |
| Günlük limit | Kişi başına 1 mail/gün |

---

### Abonelik / Çıkış

```
Üye e-posta aboneliğinden çıkmak istediğinde:
  Profil sayfası → E-posta bildirimleri toggle kapalı
  VEYA gelen maildeki "Abonelikten Çık" linki
    → Token tabanlı tek tıkla çıkış (şifre gerektirmez)
    → uyeler.eposta_abonelik = false
  Bir sonraki gönderimde otomatik çıkarılır
```

---

### Zeptomail Rate Limit Yönetimi

```
Toplu gönderimde:
  5 alıcı/batch → 2 saniye bekleme → sonraki batch
  Queue Worker ile asenkron işlenir
  Limit aşımı hatasında otomatik retry (3 deneme, 5'er dakika arayla)
  3 denemede de başarısız olursa: admin'e panel bildirimi
```

---

### Bounce Yönetimi

```
Zeptomail bounce bildirimi alındığında:
  uyeler.eposta_abonelik = false
  eposta_gonderim_gecmisi.durum = basarisiz
  Admin'e panel bildirimi (toplu — günlük özet)
```

---

### Tracking (Açılma / Tıklanma)

Zeptomail `ZEPTOMAIL_TRACK_DOMAIN` ile açılma ve tıklanmaları izler.
Scheduler (günlük) ile kampanya istatistikleri güncellenir:
- Açılma sayısı ve oranı
- Tıklanma sayısı ve oranı
- Bounce (geri dönen) sayısı

---

## Segment Sistemi

SMS modülüyle tamamen aynı segment yapısı kullanılır.
`sms_segmentler` tablosu e-posta kampanyalarında da kullanılır.
Aynı segment hem SMS hem e-posta kampanyasında seçilebilir.

Desteklenen segmentler için bkz. pazarlama-sms.md — Dinamik Segment Kural Yapısı.

---

## Backend Admin Panel

### Dashboard

- **Bu ay gönderilen** mail sayısı
- **Ortalama açılma oranı** (son 30 gün)
- **Ortalama tıklanma oranı** (son 30 gün)
- **Bekleyen kampanyalar** (zamanlanmış)
- **Aktif otomatik kampanyalar**
- **Son 10 gönderim** özet listesi

---

### Şablon Yönetimi

- Şablon listesi: ad, tip, konu, aktif/pasif
- Şablon ekle/düzenle:
  - Ad, tip, konu satırı
  - HTML editör (rich editor — Filament)
  - Değişken yardımcısı: `{AD_SOYAD}`, `{TUTAR}`, `{BAGIS_TURU}` vb.
  - Önizleme: örnek kişiyle mail nasıl görünür (modal)

---

### Kampanya Oluştur / Gönder

**Adım 1 — İçerik:**
- Kampanya adı
- Şablon seç VEYA HTML yaz
- Konu satırı
- Mail önizlemesi (canlı)

**Adım 2 — Hedef:**
- Segment seçimi (çoklu, deduplicate)
- Tahmini alıcı sayısı

**Adım 3 — Önizle & Gönder:**
- Net alıcı (spam + abonelik filtresi sonrası)
- Çıkarılan kişi sayısı ve sebebi
- Örnek mail modal önizlemesi
- Gönderim seçeneği: Şimdi Gönder / Zamanla

---

### Kampanya Listesi

| Kolon | Açıklama |
|---|---|
| Kampanya Adı | — |
| Durum | Taslak / Gönderiliyor / Tamamlandı / İptal |
| Alıcı | Net alıcı sayısı |
| Açılma | Açılma sayısı ve oranı |
| Tıklanma | Tıklanma sayısı ve oranı |
| Tarih | Gönderim tarihi |
| İşlemler | Detay |

---

### Kampanya Detay

- Kampanya bilgileri (ad, konu, şablon, segment, tarih)
- Gönderim özeti: toplam / başarılı / başarısız
- İstatistikler: açılma oranı, tıklanma oranı, bounce
- Kişi bazlı liste: e-posta, durum, açıldı mı, kaç kez tıklandı
- Filtre: başarılı / başarısız / açıldı / tıklandı

---

### Otomatik Kampanyalar

SMS modülüyle aynı yapı.
Aktif/pasif toggle, son çalışma, tetikleme kuralı, kampanya geçmişi.

---

## Belirsizlikler / Açık Kararlar

- [x] HTML mail şablonları kullanıcı tarafından sağlanacak
- [x] Bounce olan e-posta adresleri otomatik pasife alınır (uyeler.eposta_abonelik = false)
