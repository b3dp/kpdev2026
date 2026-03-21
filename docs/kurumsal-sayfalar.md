# Modül: Kurumsal Sayfalar

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Kurumsal Sayfalar |
| Backend | Evet |
| Frontend | Evet |
| Öncelik | Orta |
| Bağımlı Modüller | Haberler, Etkinlikler, Kurumlar, Kurban Yönetimi, Loglar, Roller, Yöneticiler |
| Arama | TNTSearch — bkz. genel-panel-notlari.md |
| Güvenlik | bkz. guvenlik.md |

---

## Roller ve Yetkiler

| Rol | Yeni | Düzenle | Yayınla | Sil |
|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ❌ |
| Yazar | ❌ | ❌ | ❌ | ❌ |

---

## Şablonlar

### 1. Standart İçerik Sayfası
Hakkımızda, Tarihçe, Misyon/Vizyon gibi genel içerik sayfaları için.
İçerik: başlık + HTML içerik + galeri + video embed.

### 2. İletişim Sayfası
Birden fazla lokasyon destekler. Her lokasyon için ayrı harita + adres + e-posta bloğu.
Telefon numaraları sabit — `config/iletisim.php` üzerinden yönetilir.
Adres ve e-postalar değişken — `kurumsal_iletisim_lokasyonlari` tablosundan gelir.

### 3. Kurum Sayfası
Bir kurumla etiket ilişkisi kurulur. Frontend'de o kuruma ait haberler ve etkinlikler otomatik listelenir.
Kurumsal Sayfalar menüsü altında görünür.

---

## Veritabanı

### Tablo: `kurumsal_sayfalar`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| ust_sayfa_id | foreignId | ❌ | null | → `kurumsal_sayfalar` (self join). Null ise üst sayfa |
| sablon | enum | ✅ | standart | `standart` / `iletisim` / `kurum` |
| ad | string(255) | ✅ | — | Sayfa adı |
| slug | string(255) | ✅ | — | Otomatik üretilir, unique |
| kurum_id | foreignId | ❌ | null | Kurum şablonunda → `kurumlar` |
| icerik | longText | ❌ | null | Rich editor, HTML kabul |
| ozet | text | ❌ | null | 250-300 karakter. GEO için kritik. AI üretir |
| meta_description | string(160) | ❌ | null | 150-160 karakter. AI üretir |
| robots | enum | ✅ | index | `index` / `noindex` / `noindex_nofollow` |
| canonical_url | string(500) | ❌ | null | Özel canonical URL (boşsa otomatik) |
| og_gorsel | string | ❌ | null | DO Spaces OG image |
| banner_masaustu | string | ❌ | null | DO Spaces: `img26/opt/kurumsal/{slug}-banner-lg.webp` — 1920×1080 |
| banner_mobil | string | ❌ | null | DO Spaces: `img26/opt/kurumsal/{slug}-banner-mobil.webp` — 768×432 |
| banner_orijinal | string | ❌ | null | DO Spaces: `img26/ori/kurumsal/{slug}-banner-original.jpg` |
| gorsel_lg | string | ❌ | null | DO Spaces: `img26/opt/kurumsal/{slug}-lg.webp` — 1280×720 |
| gorsel_og | string | ❌ | null | DO Spaces: `img26/opt/kurumsal/{slug}-og.webp` — 1200×675 |
| gorsel_sm | string | ❌ | null | DO Spaces: `img26/opt/kurumsal/{slug}-sm.webp` — 320×180 |
| gorsel_orijinal | string | ❌ | null | DO Spaces: `img26/ori/kurumsal/{slug}-original.jpg` |
| video_embed_url | string | ❌ | null | YouTube embed URL |
| durum | enum | ✅ | taslak | `taslak` / `yayinda` |
| ai_islendi | boolean | ✅ | false | AI işlemi tamamlandı mı |
| sira | smallInteger | ✅ | 0 | Aynı seviyedeki sayfalar arası sıralama |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

---

### Tablo: `kurumsal_sayfa_galerileri`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| sayfa_id | foreignId | ✅ | → `kurumsal_sayfalar` |
| sira | smallInteger | ✅ | Sürükle-bırak sıralama |
| orijinal_yol | string | ✅ | DO Spaces: `img26/ori/kurumsal/{slug}-galeri-001.jpg` |
| lg_yol | string | ✅ | DO Spaces: `img26/opt/kurumsal/{slug}-galeri-001-lg.webp` — 1280×720 |
| og_yol | string | ✅ | DO Spaces: `img26/opt/kurumsal/{slug}-galeri-001-og.webp` — 1200×675 |
| sm_yol | string | ✅ | DO Spaces: `img26/opt/kurumsal/{slug}-galeri-001-sm.webp` — 320×180 |
| alt_text | string(255) | ❌ | — |
| created_at | timestamp | ✅ | — |

---

### Tablo: `kurumsal_iletisim_lokasyonlari`

*(Sadece İletişim şablonlu sayfalar için)*

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| sayfa_id | foreignId | ✅ | → `kurumsal_sayfalar` |
| lokasyon_adi | string(255) | ✅ | Örn: Merkez Ofis, Şube |
| adres | text | ✅ | Değişken — her lokasyon için farklı |
| eposta | string(255) | ❌ | Değişken — her lokasyon için farklı |
| konum_lat | decimal(10,7) | ❌ | Google Maps enlem |
| konum_lng | decimal(10,7) | ❌ | Google Maps boylam |
| konum_place_id | string(255) | ❌ | Google Places ID |
| sira | smallInteger | ✅ | Lokasyon sıralama |
| created_at | timestamp | ✅ | — |
| updated_at | timestamp | ✅ | — |

> **Not:** Telefon numaraları `config/iletisim.php` üzerinden yönetilir — veritabanında tutulmaz.

---

## İş Kuralları

### Hiyerarşi Kuralları

```
Her sayfanın tek bir üst sayfası olabilir (ust_sayfa_id)
Maksimum 3 seviye derinlik:
  Seviye 1: ust_sayfa_id = null (üst sayfa)
  Seviye 2: ust_sayfa_id = seviye 1 sayfası
  Seviye 3: ust_sayfa_id = seviye 2 sayfası

Seviye 3 sayfasına alt sayfa eklenemez — sistem engeller
Üst sayfa silinmek istenirse uyarı: "Bu sayfanın X alt sayfası var"
  Alt sayfalar başka bir üst sayfaya taşınmadan silme yapılamaz
```

---

### AI Entegrasyonu — Google Cloud (Gemini Pro)

```
"AI İşlemlerini Başlat" butonu:
  ① Meta description üretilir (150-160 karakter)
  ② Özet (summary) üretilir (250-300 karakter, GEO optimize)
  ③ Slug önerisi üretilir (bağlaçsız, SEO odaklı)

Tetiklenme: Manuel buton — Kaydet aktif olmadan önce
AI işlemi tamamlanmadan Yayınla butonu aktif olmaz
```

---

### Kurum Sayfası Etiket İlişkisi

```
sablon = kurum ve kurum_id dolu ise:
  Frontend'de o kurumla ilişkili haberler otomatik listelenir
  (haber_kurumlar pivot → onay_durumu = onaylandi)
  Frontend'de o kurumla ilişkili etkinlikler otomatik listelenir
  (etkinlik_kurumlar pivot → onay_durumu = onaylandi)
```

---

### Görsel İşleme

```
Görsel yüklenince:
  intervention/image + smart crop ile 3 versiyon üretilir:
    lg: 1280×720
    og: 1200×675
    sm: 320×180

Banner için:
  Masaüstü: 1920×1080 (kırpma aracı)
  Mobil: 768×432 (kırpma aracı)
  Yüklenmezse masaüstü banner ikisi için kullanılır
```

---

### SEO / GEO Alanları

| Alan | Detay |
|---|---|
| `meta_description` | 150-160 karakter, AI üretir |
| `ozet` | 250-300 karakter, GEO optimize, AI üretir |
| `robots` | index / noindex / noindex_nofollow |
| `canonical_url` | Özel canonical. Boşsa otomatik `/{slug}` |
| `og_gorsel` | Open Graph image |
| Schema Markup | Şablona göre: `WebPage`, `Organization`, `ContactPage` |

---

### Silme Kuralı

Soft delete. Alt sayfaları olan sayfa silinemez — önce alt sayfalar taşınmalı veya silinmeli. Kurum ilişkisi varsa `kurumlar.kurumsal_sayfa_id` null'a çekilir.

---

## Backend Admin Panel

### Sayfa Listesi

- Hiyerarşik görünüm: alt sayfalar girintili listelenir
- Sürükle-bırak ile sıra değiştirilebilir (aynı seviye içinde)
- Kolonlar: Sayfa Adı, Slug, Şablon, Üst Sayfa, Durum, İşlemler
- İşlemler: Düzenle / Sil

### Sayfa Ekle / Düzenle

**Sol Kolon — Ana İçerik:**
- Sayfa Adı
- Sayfa İçeriği (Filament Rich Editor, HTML kabul)
- Sayfa Galerisi (çoklu yükleme, sürükle-bırak sıralama, 16:9)
- YouTube Video Embed URL

**Sağ Kolon — Ayarlar:**
- Şablon Seçimi (Standart / İletişim / Kurum)
- Üst Sayfa Seçimi (dropdown — max 2. seviye sayfalar listelenr, 3. seviyeye eklenemez)
- Kurum İlişkisi (sadece Kurum şablonunda)
- Yayın Durumu toggle
- Slug (otomatik, düzenlenebilir)
- Sıra numarası

**Görseller Kartı:**
- Sayfa Görseli (masaüstü 16:9, kırpma aracı)
- Banner Masaüstü (1920×1080, kırpma aracı)
- Banner Mobil (768×432, kırpma aracı)

**SEO Kartı:**
- "AI İşlemlerini Başlat" butonu
- Meta Description (karakter sayacı, 150-160)
- Özet/Summary (karakter sayacı, 250-300)
- Robots
- Canonical URL
- OG Görsel

**İletişim Lokasyonları Kartı** *(sadece İletişim şablonunda)*:
- Lokasyon ekle/düzenle/sil
- Her lokasyon: Lokasyon Adı, Adres, E-posta, Google Maps (Places Autocomplete)
- Sürükle-bırak sıralama
- Sabit telefon bilgisi: `config/iletisim.php`'den otomatik gösterilir, düzenlenemez

---

## Frontend

### Sayfalar

| Sayfa | URL | Açıklama |
|---|---|---|
| Üst sayfa | `/{slug}` | — |
| Alt sayfa | `/{ust-slug}/{slug}` | — |
| Alt-alt sayfa | `/{ust-slug}/{alt-slug}/{slug}` | — |

### Frontend Davranışı

- Sadece `durum = yayinda` sayfalar görünür
- Şablona göre render: Standart / İletişim / Kurum
- Kurum şablonunda: ilgili haberler + etkinlikler otomatik listelenir
- İletişim şablonunda: her lokasyon için ayrı Google Maps embed
- SEO: şablona göre schema markup (`WebPage` / `Organization` / `ContactPage`)
- OG tags, canonical, robots meta tag
- Menü ayrıca yönetilecek — frontend MD'sinde ele alınacak
