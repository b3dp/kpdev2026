# Modül: Bağış

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Bağış |
| Backend | Evet |
| Frontend | Evet |
| Öncelik | Yüksek |
| Bağımlı Modüller | Kişiler, Kurumlar, Üyeler, Kurban Yönetimi, Pazarlama - SMS, Pazarlama - Eposta, Loglar, Roller, Yöneticiler |
| Paketler | `barryvdh/laravel-dompdf`, `maatwebsite/laravel-excel`, `khalid-alsaqqa/laravel-hijri` |
| Ödeme | Albaraka Sanal Pos (birincil), Paytr (yedek) — bkz. servisler.md |

---

## Roller ve Yetkiler

| Rol | Tür Yönetimi | Bağış Listesi | Bağış Detay | Rapor | Pasif/Aktif |
|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ✅ |
| Muhasebe | ❌ | ✅ | ✅ | ✅ | ❌ |
| Diğerleri | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Veritabanı

### Tablo: `bagis_turleri`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| ad | string(255) | ✅ | — | Bağış türü adı |
| slug | string(255) | ✅ | — | Unique |
| ozellik | enum | ✅ | normal | `normal` / `kucukbas_kurban` / `buyukbas_kurban` |
| fiyat_tipi | enum | ✅ | sabit | `sabit` / `serbest` |
| fiyat | decimal(10,2) | ❌ | null | Sabit fiyat (fiyat_tipi=sabit ise zorunlu) |
| minimum_tutar | decimal(10,2) | ❌ | null | Minimum bağış tutarı (serbest fiyatta) |
| oneri_tutarlar | json | ❌ | null | Önerilen tutarlar: `[100, 500, 1000]` |
| aciklama | text | ❌ | null | — |
| hadis_ayet | text | ❌ | null | — |
| gorsel_kare | string | ❌ | null | DO Spaces: `img26/opt/bagis/{slug}-1x1.webp` |
| gorsel_dikey | string | ❌ | null | DO Spaces: `img26/opt/bagis/{slug}-9x16.webp` |
| gorsel_yatay | string | ❌ | null | DO Spaces: `img26/opt/bagis/{slug}-16x9.webp` |
| gorsel_orijinal | string | ❌ | null | DO Spaces: `img26/ori/bagis/{slug}-original.jpg` |
| video_yol | string | ❌ | null | DO Spaces: `img26/opt/bagis/{slug}-video.mp4` |
| acilis_tipi | enum | ✅ | manuel | `manuel` / `otomatik` |
| acilis_hicri_ay | tinyInteger | ❌ | null | Otomatik açılış için Hicri ay (8=Şaban, 9=Ramazan vb.) |
| acilis_hicri_gun | tinyInteger | ❌ | null | Otomatik açılış için Hicri gün |
| kapanis_hicri_ay | tinyInteger | ❌ | null | Otomatik kapanış için Hicri ay |
| kapanis_hicri_gun | tinyInteger | ❌ | null | Otomatik kapanış için Hicri gün |
| kapanis_saat | time | ❌ | null | Kapanış saati (örn: 20:00) |
| kurban_modulu | boolean | ✅ | false | Kurban modülüne aktarılsın mı |
| aktif | boolean | ✅ | false | Manuel override — Scheduler da yönetir |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |

---

### Tablo: `bagis_sepetler`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | — |
| uye_id | foreignId | ❌ | null | Giriş yapmış üye → `uyeler` |
| session_id | string(100) | ❌ | null | Misafir kullanıcı session ID |
| durum | enum | ✅ | aktif | `aktif` / `odeme_bekleniyor` / `tamamlandi` / `terk_edildi` |
| toplam_tutar | decimal(10,2) | ✅ | 0 | — |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |

---

### Tablo: `bagis_sepet_satirlar`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | — |
| sepet_id | foreignId | ✅ | — | → `bagis_sepetler` |
| bagis_turu_id | foreignId | ✅ | — | → `bagis_turleri` |
| adet | smallInteger | ✅ | 1 | Normal bağışta adet, büyükbaş için hisse sayısı (1-7) |
| birim_fiyat | decimal(10,2) | ✅ | — | Ekleme anındaki fiyat |
| toplam | decimal(10,2) | ✅ | — | adet × birim_fiyat |
| sahip_tipi | enum | ✅ | kendi | `kendi` / `baskasi` |
| vekalet_onay | boolean | ✅ | false | "Başkası adına" ise vekalet onayı |
| created_at | timestamp | ✅ | — | — |

> **Not:** Bir sepette aynı `bagis_turu_id`'den yalnızca 1 satır olabilir (unique: sepet_id + bagis_turu_id).

---

### Tablo: `bagislar`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| bagis_no | string(20) | ✅ | — | Benzersiz bağış numarası (örn: KP-2026-07-12-0001) |
| sepet_id | foreignId | ✅ | — | → `bagis_sepetler` |
| uye_id | foreignId | ❌ | null | → `uyeler` |
| durum | enum | ✅ | beklemede | `beklemede` / `odendi` / `hatali` / `iptal` / `terk_edildi` |
| toplam_tutar | decimal(10,2) | ✅ | — | — |
| odeme_saglayici | enum | ✅ | albaraka | `albaraka` / `paytr` |
| odeme_referans | string(255) | ❌ | null | Ödeme sağlayıcısının referans numarası |
| makbuz_yol | string | ❌ | null | DO Spaces: `img26/pdf26/bagis/{yil}/{bagis-no}-makbuz.pdf` |
| makbuz_gonderildi | boolean | ✅ | false | E-posta ile gönderildi mi |
| kurban_aktarildi | boolean | ✅ | false | Kurban modülüne aktarıldı mı |
| odeme_tarihi | timestamp | ❌ | null | — |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |

---

### Tablo: `bagis_kalemleri`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| bagis_id | foreignId | ✅ | → `bagislar` |
| bagis_turu_id | foreignId | ✅ | → `bagis_turleri` |
| adet | smallInteger | ✅ | Hisse sayısı veya adet |
| birim_fiyat | decimal(10,2) | ✅ | — |
| toplam | decimal(10,2) | ✅ | — |
| sahip_tipi | enum | ✅ | `kendi` / `baskasi` |
| vekalet_onay | boolean | ✅ | false |
| kurban_id | foreignId | ❌ | Kurban modülüne aktarıldıysa → `kurban_kayitlar` |

---

### Tablo: `bagis_kisiler`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| bagis_id | foreignId | ✅ | → `bagislar` |
| kalem_id | foreignId | ❌ | → `bagis_kalemleri` (hissedar için) |
| uye_id | foreignId | ❌ | → `uyeler` (eşleşme varsa) |
| tip | json | ✅ | `["odeyen"]` / `["sahip"]` / `["odeyen","sahip"]` / `["hissedar"]` |
| ad_soyad | string(255) | ✅ | — |
| tc_kimlik | string(11) | ❌ | Sahip/hissedar için zorunlu, ödeyende opsiyonel |
| telefon | string(20) | ❌ | — |
| eposta | string(255) | ❌ | — |
| hisse_no | tinyInteger | ❌ | Büyükbaş kurban hisse sırası (1-7) |
| vekalet_ad_soyad | string(255) | ❌ | Vekalet veren kişi adı |
| vekalet_tc | string(11) | ❌ | Vekalet veren TC |
| vekalet_telefon | string(20) | ❌ | Vekalet veren telefon |

---

### Tablo: `odeme_hatalari`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| bagis_id | foreignId | ✅ | → `bagislar` |
| saglayici | enum | ✅ | `albaraka` / `paytr` |
| hata_kodu | string(100) | ❌ | Sağlayıcıdan dönen hata kodu |
| hata_mesaji | string(500) | ❌ | Sağlayıcıdan dönen tam hata mesajı |
| kart_son_haneler | string(4) | ❌ | Sanal posun izin verdiği ölçüde |
| banka_adi | string(255) | ❌ | Sanal posun verdiği banka bilgisi |
| tutar | decimal(10,2) | ✅ | Denenen tutar |
| created_at | timestamp | ✅ | — |

---

### Tablo: `bagis_otomatik_raporlar`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| periyot | enum | ✅ | `gunluk` / `haftalik` / `aylik` |
| alicilar | json | ✅ | E-posta adresleri: `["muhasebe@example.com"]` |
| aktif | boolean | ✅ | true |
| son_gonderim | timestamp | ❌ | — |

---

## İş Kuralları

### Bağış Türü Açılış/Kapanış Otomasyonu

```
Laravel Scheduler her gün 00:01 ve her saat başı çalışır:
        │
        ▼
acilis_tipi = otomatik olan türler kontrol edilir
Bugünün Hicri tarihi hesaplanır (khalid-alsaqqa/laravel-hijri)
        │
        ├── Hicri tarih acilis_hicri_ay/gun eşleşirse → aktif = true
        └── Hicri tarih kapanis_hicri_ay/gun + kapanis_saat geçtiyse → aktif = false

Manuel override: aktif toggle her zaman çalışır
  Admin kapandı işaretlerse → otomatik açılmaz (override_aktif alanı)
  Admin açtı işaretlerse → scheduler kapanışa kadar açık kalır
```

**Hicri Takvim Özeti:**

| Bağış Türü | Açılış | Kapanış |
|---|---|---|
| Fitre | 28 Şaban otomatik | 30 Ramazan 20:00 |
| Fidye | 28 Şaban otomatik | 30 Ramazan 20:00 |
| Sahur/İftar Sofrası | Manuel | 30 Ramazan 20:00 otomatik |
| Vacip Kurban (K/B) | Manuel | 10 Zilhicce 20:00 otomatik |
| Diğerleri | Manuel | Manuel |

---

### Sepet Mantığı

```
Kullanıcı bağış türünü seçer
        │
        ▼
Aktif sepeti var mı?
  HAYIR → yeni sepet oluşturulur
  EVET → mevcut sepete eklenir
        │
        ▼
Aynı bagis_turu_id sepette var mı?
  EVET → "Bu bağış türü sepetinizde zaten var" uyarısı
  HAYIR → sepet_satirlar tablosuna eklenir
```

---

### Ödeme ve Kişi Akışı

```
Sepet → Ödeme sayfası
        │
        ▼
Her kalem için kişi bilgileri toplanır:

Normal Bağış:
  Kendi adıma → ödeyenle sahip aynı kişi (tip: ["odeyen","sahip"])
  Başkası adına → sahip bilgileri + vekalet formu ayrı

Küçükbaş Kurban:
  Sahip bilgileri: Ad Soyad, E-posta, Telefon, TC (opsiyonel)
  tip: ["sahip"]

Büyükbaş Kurban:
  Hisse sayısı seçilir (1-7)
  Her hisse için hissedar bilgileri
  tip: ["hissedar"], hisse_no: 1..N
        │
        ▼
Ödeme bilgileri (ödeyenin bilgileri):
  Ad Soyad, TC, E-posta veya Telefon (zorunlu)
  "İletişim bilgilerimi kullan" → sahip bilgilerini kopyalar
        │
        ▼
Ödeme sağlayıcısına gönderilir (Albaraka birincil)
        │
        ├── BAŞARILI → durum: odendi
        │             bagis_kisiler kaydedilir
        │             Üyeler modülüne aktarılır
        │             Makbuz PDF oluşturulur (Queue/Job)
        │             E-posta + SMS (makbuz linki) gönderilir
        │             Kurban türüyse → KurbanAktarimJob tetiklenir
        │
        └── HATA → durum: hatali
                   odeme_hatalari tablosuna kaydedilir
                   Yöneticilere SMS + e-posta bildirimi
                   Bağış detayında hata widget gösterilir
                   Kullanıcı tekrar deneyebilir
```

---

### Üyeler Modülü Entegrasyonu

```
Ödeme başarılı olduktan sonra:
        │
        ▼
bagis_kisiler tablosundaki her kişi için:
  Telefon veya e-posta ile uyeler tablosunda arama
        │
        ├── Eşleşme var → uye_id doldurulur, bagisci rozeti eklenir
        └── Eşleşme yok → yeni üye oluşturulur
                          bagisci rozeti eklenir
                          İletişim bilgisi varsa "Hesabınızı aktive edin" gönderilir
                          İletişim bilgisi yoksa (TC only) → üye oluşturulmaz
```

---

### Makbuz PDF

```
Ödeme onaylanınca MakbuzOlusturJob kuyruğa girer:
  barryvdh/laravel-dompdf ile makbuz oluşturulur

Makbuz içeriği:
  - Bağış numarası (KP-2026-07-12-0001)
  - Bağışçı adı
  - Bağış türleri ve tutarlar
  - Toplam tutar
  - Ödeme tarihi/saati
  - Ödeme sağlayıcısı referans no
  - QR kod (makbuz doğrulama linki)

DO Spaces: img26/pdf26/bagis/{yil}/{bagis-no}-makbuz.pdf
        │
        ▼
E-posta eki olarak gönderilir
SMS'de indirme linki gönderilir:
  "Bağışınız alındı. Makbuzunuz: https://cdn.kestanepazari.org.tr/..."
```

---

### Otomatik Raporlama (Scheduler)

```
Günlük  → Her gün 08:00 → önceki günün bağışları Excel → tanımlı adreslere e-posta
Haftalık → Her Pzt 08:00 → önceki haftanın bağışları Excel
Aylık   → Her ayın 1'i 08:00 → önceki ayın bağışları Excel

Excel dosyası DO Spaces'e kaydedilir:
  img26/xlsx26/bagis/{yil}/{periyot}-{tarih}.xlsx
```

---

### Sepet Terk Takibi

```
Sepet 8 saat işlemsiz kalırsa → durum: terk_edildi
Üye ise → Pazarlama modülüne bildirim (e-posta kampanyası)
Üye değilse → kayıt silinmez, anonim olarak istatistikte tutulur
```

---

## Backend Admin Panel

### Bağış Türleri

#### Tür Listesi

| Kolon | Açıklama |
|---|---|
| Bağış Türü | Ad |
| Özellik | Normal / Küçükbaş / Büyükbaş rozeti |
| Fiyat Tipi | Sabit / Serbest |
| Tutar | Sabit ise tutar, serbest ise min. tutar |
| Durum | Aktif / Pasif toggle |
| Açılış/Kapanış | Otomatik ise Hicri tarih gösterilir |
| İşlemler | Düzenle (silme yok) |

#### Tür Ekle / Düzenle

- Bağış Türü Adı
- Bağış Özelliği (radio): Normal / Küçükbaş Kurban / Büyükbaş Kurban
- Fiyat Tipi (radio): Sabit / Serbest
  - Sabit ise: Fiyat alanı
  - Serbest ise: Minimum tutar + Önerilen tutarlar (etiket ekle, örn: 100, 500, 1000)
- Açıklama
- Hadis / Ayet
- Görsel Kare, 9:16, 16:9 (kırpma aracı)
- Video (DO Spaces)
- Açılış Tipi (radio): Manuel / Otomatik
  - Otomatik ise: Hicri ay/gün (açılış + kapanış) + kapanış saati
- Kurban Modülüne Aktar (toggle)
- Aktif / Pasif toggle (manuel override)

---

### Bağış Listesi

**Üst — İnfografik Kartlar:**

| Kart | İçerik |
|---|---|
| Bugün | Başarılı bağış tutarı + adet |
| Bu Ay | Başarılı bağış tutarı + adet |
| Bu Yıl | Başarılı bağış tutarı + adet |

**Filtreler:**
- Bağış türü (çoklu)
- Durum (çoklu): Beklemede / Ödendi / Hatalı / İptal / Terk Edildi
- Tarih aralığı
- Sahip tipi: Kendi adına / Başkası adına

**Liste Kolonları:**

| Kolon | Açıklama |
|---|---|
| Bağış No | KP-2026-07-12-0001 |
| Bağış Türleri | Zekat, Fitre (virgülle ayrılmış) |
| Durum | Renkli rozet |
| Tutar | Toplam |
| Sahip Tipi | Kendi / Başkası adına |
| Bağışçı Adı | — |
| Tarih | — |
| İşlemler | Bağış Detayını Gör |

**Alt — Excel Rapor Butonu:**
Tıklanınca popup açılır. Hazır seçenekler (radio), yan yana tek satırda:

| Seçenek | Tarih Gösterimi |
|---|---|
| Bugün | 12/07/26 Prş. |
| Dün | 11/07/26 Çrş. |
| Bu Ay | Temmuz 26 |
| Geçen Ay | Haziran 26 |
| Bu Yıl | 2026 |
| Geçtiğimiz Yıl | 2025 |
| Özel Tarih | Başlangıç — Bitiş seçici |

**Excel Kolonları:**
Bağış No, Bağış Türleri, Durum, Adet/Hisse, Birim Fiyat, Toplam Tutar, Bağışçı Adı, TC Kimlik, Telefon, E-posta, Sahip Tipi, Sahip Adı, Sahip TC, Ödeme Sağlayıcısı, Ödeme Referans No, Ödeme Tarihi, Makbuz Linki

---

### Bağış Detay Sayfası

#### Card 1 — Bağış Özeti
- Bağış No
- Bağış Türleri (her kalem ayrı satır: tür, adet, tutar)
- Toplam Tutar
- Durum
- Sahip Tipi (Kendi / Başkası adına)
- Bağış Tarihi

#### Card 2 — Ödeyenin Bilgileri
- Ad Soyad
- TC Kimlik
- Telefon
- E-posta
- **İletişim Butonları:** SMS | WhatsApp (`wa.me`) | Ara | E-posta

#### Card 3 — Bağış Sahibi Bilgileri
*(Başkası adına yapıldıysa görünür)*
- Ad Soyad
- TC Kimlik
- Telefon
- E-posta
- **İletişim Butonları:** SMS | WhatsApp | Ara | E-posta

#### Card 4 — Vekalet Bilgileri
*(Vekalet onayı varsa görünür)*
- Vekalet Veren Ad Soyad
- Vekalet Veren TC
- Vekalet Veren Telefon
- Vekalet Onay Tarihi

#### Card 5 — Hissedar Bilgileri
*(Büyükbaş kurban ise görünür)*
Her hisse ayrı satır:
- Hisse No
- Ad Soyad
- TC Kimlik
- Telefon / E-posta

#### Card 6 — Ödeme Bilgileri
- Ödeme Yapan Ad Soyad
- Kart Son Haneleri (sanal posun izin verdiği ölçüde)
- Banka Adı (sanal posun verdiği)
- Ödeme Sağlayıcısı
- Referans No
- Ödeme Tarihi/Saati
- Makbuz İndir butonu

#### Card 7 — Kurban Aktarım Bilgileri
*(kurban_modulu = true ise görünür)*
- Aktarım durumu: Aktarıldı / Bekliyor
- Kurban kayıt linki
- Aktarım tarihi

#### Card 8 — Hata Bilgileri
*(Ödeme hatası varsa görünür — kırmızı card)*
- Hata Kodu
- Hata Mesajı (sanal postan dönen tam metin)
- Kart Son Haneleri
- Banka Adı
- Hata Tarihi/Saati
- Kullanıcı iletişim bilgileri (arama için)

---

## Otomatik Raporlama Ayarları (Panel)

- Günlük / Haftalık / Aylık için ayrı toggle
- Her periyot için alıcı e-posta adresleri (çoklu, tag input)
- Son gönderim tarihi/saati gösterilir
- "Şimdi Gönder" butonu (manuel tetikleme)

---

## Belirsizlikler / Açık Kararlar

- [x] Bağış numarası formatı: `KP-YIL-AY-GUN-0001` (son kısım 4 haneli sıralı)
- [x] Makbuz PDF şablonu tasarımı kullanıcı tarafından yapılacak
- [x] Sepet terk süresi: 8 saat
- [x] Büyükbaş kurban hisse fiyatı: hisse başına fiyat gösterilir (toplam = hisse sayısı × birim fiyat)
- [x] Ödeme hatasında kullanıcıya sanal postan dönen yanıt mesajı gösterilir
