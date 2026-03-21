# Modül: Öğrenci E-Kayıt

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Öğrenci E-Kayıt |
| Backend | Evet |
| Frontend | Evet (kayıt formu) |
| Öncelik | Yüksek |
| Bağımlı Modüller | Kurumlar, Kurumsal Sayfalar, Üyeler, Pazarlama - SMS, Pazarlama - Eposta, Loglar, Roller, Yöneticiler |
| Paketler | `barryvdh/laravel-dompdf`, `maatwebsite/laravel-excel` |
| Arama | TNTSearch — bkz. genel-panel-notlari.md |

---

## Roller ve Yetkiler

| Rol | Sınıf Yönetimi | Kayıt Listesi | Kayıt Detay | Durum Güncelle | PDF İndir | Sil |
|---|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| E-Kayıt | ❌ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Yazar | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Veritabanı

### Tablo: `ekayit_siniflar`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| ad | string(255) | ✅ | — | Sınıf adı (örn: 8. Sınıf Mezunu Yatılı Hafızlık) |
| ogretim_yili | string(20) | ✅ | — | Örn: 2025-2026 |
| kurum_id | foreignId | ✅ | — | → `kurumlar` (sadece kurumsal_sayfa_id dolu olanlar) |
| kurallar | text | ❌ | null | Kayıt kabul kuralları |
| aciklama | text | ❌ | null | Sınıf açıklaması |
| notlar | text | ❌ | null | İç notlar, panelde görünür |
| gorsel_kare | string | ❌ | null | DO Spaces: `img26/opt/ekayit/{sinif-slug}-1x1.webp` — 1:1 |
| gorsel_dikey | string | ❌ | null | DO Spaces: `img26/opt/ekayit/{sinif-slug}-9x16.webp` — 9:16 |
| gorsel_yatay | string | ❌ | null | DO Spaces: `img26/opt/ekayit/{sinif-slug}-16x9.webp` — 16:9 |
| gorsel_orijinal | string | ❌ | null | DO Spaces: `img26/ori/ekayit/{sinif-slug}-original.jpg` |
| donem_id | foreignId | ✅ | — | → `ekayit_donemler` |
| renk | string(30) | ✅ | — | Tailwind renk adı: `blue`, `green`, `red`, `orange`, `amber`, `yellow`, `lime`, `teal`, `purple`, `pink`. Otomatik atanır, değiştirilebilir |
| aktif | boolean | ✅ | true | Pasif sınıflara yeni kayıt alınmaz |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

---

### Tablo: `ekayit_donemler`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| ad | string(255) | ✅ | — | Dönem adı (örn: 2025-2026 Kayıt Dönemi) |
| ogretim_yili | string(20) | ✅ | — | Örn: 2025-2026 |
| baslangic | datetime | ✅ | — | Kayıt başlangıç tarihi/saati — Scheduler ile otomatik açılır |
| bitis | datetime | ✅ | — | Kayıt bitiş tarihi/saati — Scheduler ile otomatik kapanır |
| aktif | boolean | ✅ | false | Scheduler tarafından yönetilir |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |

---

### Tablo: `ekayit_evrak_sablonlari`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| ad | string(255) | ✅ | — | Evrak adı (örn: Veli Muvafakatnamesi) |
| dosya_adi | string(255) | ✅ | — | Slug formatında (örn: veli-muvafakatnamesi) |
| sablon_yol | string | ✅ | — | DO Spaces: `img26/pdf26/ekayit/sablonlar/{dosya-adi}.pdf` |
| degiskenler | json | ❌ | null | Şablondaki değişkenler: `["AD_SOYAD","TC_KIMLIK","SINIF"]` |
| sadece_onayliya | boolean | ✅ | true | Sadece onaylanan kayıtlar için mi üretilsin |
| sira | tinyInteger | ✅ | — | ZIP içindeki sıralama |
| aktif | boolean | ✅ | true | — |
| created_at | timestamp | ✅ | — | — |

---

### Tablo: `ekayit_olusturulan_evraklar`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kayit_id | foreignId | ✅ | → `ekayit_kayitlar` |
| sablon_id | foreignId | ✅ | → `ekayit_evrak_sablonlari` |
| dosya_yol | string | ✅ | DO Spaces: `img26/pdf26/ekayit/{ogretim-yili}/{kayit-no}/{kayit-no}-{dosya-adi}.pdf` |
| olusturulma_tarihi | timestamp | ✅ | — |
| created_at | timestamp | ✅ | — |

---

### Tablo: `ekayit_kayitlar`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| sinif_id | foreignId | ✅ | — | → `ekayit_siniflar` |
| uye_id | foreignId | ❌ | null | → `uyeler` (veli sisteme kayıtlıysa) |
| durum | enum | ✅ | beklemede | `beklemede` / `onaylandi` / `reddedildi` / `yedek` |
| durum_notu | text | ❌ | null | Onay/Red/Yedek sebebi — hazır mesaj veya manuel |
| yonetici_id | foreignId | ❌ | null | Durumu güncelleyen yönetici → `yoneticiler` |
| durum_tarihi | timestamp | ❌ | null | Durum güncellenme zamanı |
| yedek_sira | smallInteger | ❌ | null | Yedek sırası (1. yedek, 2. yedek...) |
| genel_not | text | ❌ | null | Yönetici notu (tarih/saat ile) |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

---

### Tablo: `ekayit_ogrenci_bilgileri`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kayit_id | foreignId | ✅ | → `ekayit_kayitlar` |
| ad_soyad | string(255) | ✅ | — |
| tc_kimlik | string(11) | ✅ | — |
| dogum_yeri | string(255) | ❌ | — |
| dogum_tarihi | date | ✅ | — |
| baba_adi | string(255) | ❌ | — |
| anne_adi | string(255) | ❌ | — |
| adres | text | ❌ | — |
| ikamet_il | string(100) | ❌ | — |

---

### Tablo: `ekayit_kimlik_bilgileri`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kayit_id | foreignId | ✅ | → `ekayit_kayitlar` |
| kayitli_il | string(100) | ❌ | — |
| kayitli_ilce | string(100) | ❌ | — |
| kayitli_mahalle_koy | string(255) | ❌ | — |
| cilt_no | string(50) | ❌ | — |
| aile_sira_no | string(50) | ❌ | — |
| sira_no | string(50) | ❌ | — |
| cuzdanin_verildigi_yer | string(255) | ❌ | — |
| kimlik_seri_no | string(50) | ❌ | — |
| kan_grubu | string(10) | ❌ | — |

---

### Tablo: `ekayit_okul_bilgileri`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kayit_id | foreignId | ✅ | → `ekayit_kayitlar` |
| okul_adi | string(255) | ❌ | — |
| okul_numarasi | string(50) | ❌ | — |
| sube | string(10) | ❌ | — |
| not | text | ❌ | — |

---

### Tablo: `ekayit_veli_bilgileri`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kayit_id | foreignId | ✅ | → `ekayit_kayitlar` |
| ad_soyad | string(255) | ✅ | — |
| eposta | string(255) | ❌ | — |
| telefon_1 | string(20) | ✅ | WhatsApp bildirimleri için |
| telefon_2 | string(20) | ❌ | WhatsApp bildirimleri için |

---

### Tablo: `ekayit_baba_bilgileri`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kayit_id | foreignId | ✅ | → `ekayit_kayitlar` |
| dogum_yeri | string(255) | ❌ | — |
| nufus_il_ilce | string(255) | ❌ | Nüfusa kayıtlı olduğu il/ilçe |

---

### Tablo: `ekayit_hazir_mesajlar`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| baslik | string(255) | ✅ | Mesaj başlığı (listede görünen) |
| metin | text | ✅ | Mesaj içeriği — değişkenler: `{AD_SOYAD}`, `{SINIF}`, `{KURUM}` |
| tip | enum | ✅ | `onay` / `red` / `yedek` / `genel` |
| aktif | boolean | ✅ | true |
| created_at | timestamp | ✅ | — |

---

## İş Kuralları

### Dönemsel Kayıt Planı

```
Admin ekayit_donemler tablosunda dönem oluşturur:
  Ad, öğretim yılı, başlangıç ve bitiş tarihi girilir

Laravel Scheduler her gün çalışır:
  Bugün >= baslangic → aktif = true (kayıt formu açılır)
  Bugün > bitis      → aktif = false (kayıt formu kapanır)

Her sınıf bir döneme bağlıdır:
  Aynı sınıf adı farklı dönemlerle tekrar kullanılabilir
  Yıllar arasında kayıtlar karışmaz (donem_id üzerinden ayrışır)
```

**Örnek:**
```
Dönem: 2025-2026 (1 Mayıs - 25 Haziran 2025)
  ├── Hafızlık Sınıfı (mavi)   → sinif_id: 1
  ├── Yaz Kursu (yeşil)        → sinif_id: 2

Dönem: 2026-2027 (1 Mayıs - 25 Haziran 2026)
  ├── Hafızlık Sınıfı (mavi)   → sinif_id: 5  ← yeni kayıt, farklı ID
  ├── Yaz Kursu (yeşil)        → sinif_id: 6
```

---

### Sınıf Renk Atama

```
Yeni sınıf oluşturulurken:
  Aynı dönemdeki mevcut sınıfların renkleri kontrol edilir
  Kullanılmayan ilk Tailwind rengi otomatik atanır
  Sıra: blue → green → orange → purple → red → amber → teal → lime → pink → yellow

  İstenirse admin dropdown'dan manuel değiştirebilir
  Renk sınıf kartlarında arka plan, listede sol kenarlık olarak kullanılır
```

---

### Başvuru Teklik Kuralı

```
Bir öğrenci aynı dönemde yalnızca tek bir sınıfa başvurabilir.
Başvuru yapılırken TC Kimlik + donem_id kombinasyonu kontrol edilir.
Aynı kombinasyon varsa:
  "Bu dönem için zaten başvurunuz bulunmaktadır." hatası gösterilir.
```

---

### Sınıf — Kurum Filtresi

```
Sınıf ekle/düzenle formunda kurum seçimi:
  Sadece kurumsal_sayfa_id dolu olan kurumlar listelenir
  (Kurumsal Sayfalarla ilişkilendirilmiş kurumlar)
```

### Kayıt Durum Güncelleme Akışı

```
Yönetici kayıt detayına girer
        │
        ▼
En üstteki 4 widget görünür:

Widget 1 — Onay/Red Sebebi:
  Hazır mesajlardan seç (tip'e göre filtrelenir)
  VEYA manuel mesaj yaz
  → Kaydet butonuyla durum_notu kaydedilir

Widget 2 — İletişim Telefonu 01:
  [✅ Onaylandı] [❌ Reddedildi] [🔵 Yedek]
  Butona basınca:
    → durum güncellenir
    → WhatsApp mesajı Tel 1'e gönderilir
    → Log kaydı oluşturulur

Widget 3 — İletişim Telefonu 02:
  [✅ Onaylandı (TEL 2)] [❌ Reddedildi (TEL 2)] [🔵 Yedek (TEL 2)]
  Butona basınca:
    → Sadece WhatsApp mesajı Tel 2'ye gönderilir
    → Durum değişmez (sadece bildirim)

Widget 4 — Dökümanlar:
  [📄 Dökümanları PDF İndir]
  → Kayda ait tüm bilgiler PDF olarak oluşturulur
  → DO Spaces: img26/pdf26/ekayit/{yil}/{kayit-id}.pdf
```

### WhatsApp Mesaj Akışı

```
Durum butonu tıklanır
        │
        ▼
durum_notu dolu mu?
  EVET → durum_notu mesaj olarak kullanılır
  HAYIR → ilgili durum için varsayılan hazır mesaj kullanılır
        │
        ▼
WhatsApp API (Meta Business veya Twilio) üzerinden mesaj gönderilir
İlgili telefon numarasına
        │
        ▼
Log kaydı: "X Yöneticisi, Y Öğrencisinin kaydını Onayladı/Reddetti/Yedeğe Aldı"
```

### WhatsApp URL Mesaj Akışı

```
Durum butonu (Onaylandı / Reddedildi / Yedek) tıklanır
        │
        ▼
durum_notu dolu mu?
  EVET → durum_notu mesaj metni olarak kullanılır
  HAYIR → ilgili tipe ait varsayılan hazır mesaj kullanılır
        │
        ▼
Mesaj metni URL encode edilir
wa.me linki oluşturulur:
  https://wa.me/90{telefon}?text={url_encode(mesaj)}
        │
        ▼
Yeni sekmede WhatsApp Web açılır
Mesaj hazır dolu gelir — yönetici gönder butonuna basar
```

**Örnek URL:**
```
https://wa.me/905326238335?text=Sn.%20Veli%2C%0A%0A
Öğrenciniz%20MEHMET%20EMİN%20GÜDEN%20kaydı%20*onaylanmıştır.*
```

**Değişkenler:** `{AD_SOYAD}`, `{SINIF}`, `{KURUM}`, `{DURUM}`, `{TARIH}`

Tel 1 butonu → Tel 1 ile wa.me linki oluşturur + durumu günceller
Tel 2 butonu → Tel 2 ile wa.me linki oluşturur + durumu güncellemez

---

### Evrak PDF Üretimi

```
Öğrenci durumu "onaylandi" yapıldığında:
  (veya admin manuel tetikleyebilir)
        │
        ▼
Aktif evrak şablonları listelenir (sadece_onayliya = true olanlar)
        │
        ▼
Her şablon için:
  Şablon PDF DO Spaces'ten çekilir
  Öğrenci verileri şablondaki değişkenlere yerleştirilir
  (barryvdh/laravel-dompdf ile)
        │
        ▼
Oluşan PDF kaydedilir:
  img26/pdf26/ekayit/{ogretim-yili}/{kayit-no}/{kayit-no}-{dosya-adi}.pdf

Örnek:
  img26/pdf26/ekayit/2025-2026/2026001/2026001-veli-muvafakatnamesi.pdf
  img26/pdf26/ekayit/2025-2026/2026001/2026001-saglik-formu.pdf
  img26/pdf26/ekayit/2025-2026/2026001/2026001-kayit-sozlesmesi.pdf
        │
        ▼
ZIP oluşturulur (Queue/Job ile asenkron):
  {kayit-no}-{ogrenci-adsoyad}.zip
  Örnek: 2026001-mehmet-emin-guden.zip

ZIP içi sıralama ekayit_evrak_sablonlari.sira alanına göre
```

---

### Üyeler Modülü Entegrasyonu

```
Kayıt formu doldurulurken veli telefon/e-posta girilir
        │
        ▼
Üyeler tablosunda eşleşme aranır
  Eşleşme var → kayit.uye_id doldurulur
  Eşleşme yok → kayıt beklemede, üye henüz oluşturulmaz
        │
        ▼
Yönetici kaydı ONAYLADIĞINDA:
  Eşleşme var → mevcut üyeye veli rozeti eklenir
  Eşleşme yok → arka planda yeni üye oluşturulur
                veli rozeti eklenir
                "Hesabınızı aktive edin" SMS/e-posta gönderilir
        │
        ▼
Sadece VELİ bilgileri üyeler modülüne aktarılır
Öğrenci bilgileri üyeler modülüne eklenmez
```


### Excel İndirme

```
Kayıt listesinde "Excel İndir" butonu
        │
        ▼
Filtre modalı açılır:
  Dönem seçimi (zorunlu)
  Sınıf seçimi (çoklu)
  Tarih aralığı
  Durum seçimi (çoklu: Beklemede, Onaylandı, Reddedildi, Yedek)
        │
        ▼
maatwebsite/laravel-excel ile Excel oluşturulur
Sınıf rengi Excel'de arka plan rengi olarak yansıtılır
Dosya adı: ekayit-{donem}-{tarih}.xlsx
DO Spaces: img26/xlsx26/ekayit/{yil}/{dosya-adi}.xlsx
```

**Excel kolonları:** Kayıt No, Öğrenci Adı, TC, Sınıf, Dönem, Veli Adı, Tel 1, Tel 2, Durum, Kayıt Tarihi, Onay Tarihi

---

### Silme Kuralı

Soft delete. Silindiğinde bağlı alt tablolar (`ogrenci_bilgileri`, `kimlik_bilgileri` vb.) da silinir.

---

## Backend Admin Panel

### Ana Sayfa — Dönem Kartları

```
Üstte dönem seçici: aktif dönem varsayılan seçili

Her sınıf için kart (sınıf rengiyle):
┌─────────────────────────────┐
│  Hafızlık Sınıfı            │
│  ─────────────────────────  │
│  Bekleyen:   5  →(link)     │
│  Onaylanan: 11  →(link)     │
│  Yedek:     22  →(link)     │
│  Toplam:    37              │
└─────────────────────────────┘

Rakamlar tıklanabilir link:
  "Bekleyen 5" → Kayıt listesi (filtre: bu sınıf + durum: beklemede)
  "Onaylanan 11" → Kayıt listesi (filtre: bu sınıf + durum: onaylandi)
  "Yedek 22" → Kayıt listesi (filtre: bu sınıf + durum: yedek)
```

### Sınıf Yönetimi

#### Sınıf Listesi

| Kolon | Açıklama |
|---|---|
| Sınıf Adı | — |
| Öğretim Yılı | — |
| Kurum Adı | — |
| Aktif/Pasif | Toggle |
| İşlemler | Düzenle / Sil / Kayıtları Göster |

- Filtre: öğretim yılı, kurum, aktif/pasif

#### Sınıf Ekle / Düzenle

- Sınıf Adı
- Öğretim Yılı
- Kurum Adı (dropdown — sadece kurumsal sayfası olan kurumlar)
- Kurallar (rich text)
- Açıklama (rich text)
- Notlar (textarea — panelde görünür)
- Görsel 1:1 (kırpma aracı)
- Görsel 9:16 (kırpma aracı)
- Görsel 16:9 (kırpma aracı)

---

### Kayıt Yönetimi

#### Kayıt Listesi

| Kolon | Açıklama |
|---|---|
| Ad Soyad | Öğrenci adı |
| Başvuru Sınıfı | — |
| Veli Adı | — |
| Veli Telefonu | Tel 1 |
| Durum | Onaylandı / Reddedildi / Yedek / Beklemede (renkli rozet) |
| İşlemler | Düzenle / PDF İndir |

- **Kolay filtre butonları (üst bar):** Sınıf adları buton olarak sıralanır, tıklanınca o sınıfın kayıtları filtrelenir. Aktif sınıf butonu sınıf rengiyle vurgulanır
- Filtre: dönem, sınıf, tarih aralığı, durum (çoklu)
- Arama: öğrenci adı, veli adı, TC kimlik, telefon
- **Excel İndir** butonu — filtreli indirme modalı açılır

#### Hazır Mesaj Yönetimi

- Hazır mesajlar listelenir: başlık, tip, metin önizleme, aktif/pasif
- Yeni mesaj ekle / düzenle / sil
- Mesaj tipine göre filtre: onay / red / yedek / genel
- Değişken desteği: `{AD_SOYAD}`, `{SINIF}`, `{KURUM}`

---

### Kayıt Detay Sayfası

Her Card'ın sağ üstünde **Düzenle** butonu. Tıklanınca o card'a ait alanlar düzenlenebilir hale gelir. Alt kısımda **Kaydet / Vazgeç** butonları çıkar.

#### En Üst — 4 Widget

**Widget 1: Onay/Red Sebebi**
- Hazır mesaj dropdown (tipe göre filtrelenir)
- VEYA manuel mesaj textarea
- Kaydet butonu

**Widget 2: İletişim Telefonu 01**
- Telefon numarası gösterilir
- [✅ Onaylandı] [❌ Reddedildi] [🔵 Yedek]

**Widget 3: İletişim Telefonu 02**
- Telefon numarası gösterilir
- [✅ Onaylandı (TEL 2)] [❌ Reddedildi (TEL 2)] [🔵 Yedek (TEL 2)]

**Widget 4: Dökümanlar**
- [📄 Dökümanları PDF İndir]

---

#### Card: Kayıt Bilgisi
- Durumu
- Sınıfı
- Tarih
- İsim Soyisim
- TC Kimlik
- Doğum Yeri
- Baba Adı
- Anne Adı
- Doğum Tarihi
- Adres
- İkamet Ettiği İl

#### Card: Okul Bilgisi
- Okul Adı
- Okul Numarası
- Öğrencinin Şubesi
- Not

#### Card: Kimlik Bilgisi
- Kayıtlı Olduğu İl
- Kayıtlı Olduğu İlçe
- Kayıtlı Olduğu Mahalle/Köy
- Kayıtlı Olduğu Cilt No
- Kayıtlı Olduğu Aile Sıra No
- Kayıtlı Olduğu Sıra No
- Cüzdanın Verildiği Yer
- Kimlik Seri No
- Kan Grubu

#### Card: Öğrenci Veli Bilgisi
- Velinin Adı Soyadı
- E-posta Adresi
- İletişim Telefonu 01
- İletişim Telefonu 02

#### Card: Öğrencinin Babasının Bilgisi
- Babasının Doğum Yeri
- Nüfusa Kayıtlı Olduğu İl/İlçe

---

## Belirsizlikler / Açık Kararlar

- [x] WhatsApp: API değil `wa.me` URL kullanılacak
- [x] PDF: Evrak şablonları ayrı tablo, her şablon ayrı PDF, ZIP ile toplu indirilir
- [ ] Kayıt formu frontend detayı sonra verilecek
- [x] Bir öğrenci aynı dönemde yalnızca tek sınıfa başvurabilir (TC + donem_id unique)
- [x] Yedek sırası: `yedek_sira` alanı eklendi
