# Modül: Haberler

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Haberler |
| Backend | Evet |
| Frontend | Evet |
| Öncelik | Yüksek |
| Bağımlı Modüller | Kişiler, Kurumlar, Etkinlikler, Dergiler, Pazarlama - Eposta, Loglar, Roller, Yöneticiler |
| Arama | TNTSearch — bkz. genel-panel-notlari.md |

---

## Roller ve Yetkiler

| Rol | Yeni | Düzenle | AI Başlat | Kaydet | Yayınla | Zamanlı Yayın | Manşet | Sil |
|---|---|---|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Yazar | ✅ | ✅ (kendi) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

> **Not:** Yazar sadece kendi haberlerini düzenleyebilir. Kaydet/Yayınla butonu AI işlemi tamamlanmadan aktif olmaz.

---

## Veritabanı

### Tablo: `haberler`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| yonetici_id | foreignId | ✅ | — | Yazarın ID'si → `yoneticiler` |
| kategori_id | foreignId | ✅ | — | → `haber_kategorileri` |
| baslik | string(60) | ✅ | — | SEO başlığı ile aynı. Max 60 karakter |
| slug | string(100) | ✅ | — | Otomatik üretilir. Bağlaçlar temizlenir. 50-60 karakter ideal. Unique |
| icerik | longText | ✅ | — | Rich text / HTML (Filament rich editor) |
| summary | text | ❌ | null | 250-300 karakter veya max 3 maddelik liste. GEO için kritik. AI üretir, düzenlenebilir |
| meta_description | string(160) | ❌ | null | 150-160 karakter. AI otomatik oluşturur, düzenlenebilir |
| durum | enum | ✅ | taslak | `taslak` / `incelemede` / `yayinda` / `reddedildi` |
| mansет | boolean | ✅ | false | Ana sayfada slider veya manşet alanında gösterilsin mi |
| mansет_gorseli_masaustu | string | ❌ | null | DO Spaces yolu. Girilmezse ana görsel kullanılır |
| mansет_gorseli_mobil | string | ❌ | null | DO Spaces yolu. Girilmezse ana görsel kullanılır |
| yayin_tarihi | timestamp | ❌ | null | Zamanlı yayın için tarih/saat |
| yayinlayan_id | foreignId | ❌ | null | Yayınlayan editör → `yoneticiler` |
| yayinlanma_tarihi | timestamp | ❌ | null | Gerçek yayınlanma zamanı |
| editor_notu | text | ❌ | null | Sadece panel içinde görünür, frontend'e yansımaz |
| ai_islendi | boolean | ✅ | false | AI işlemi tamamlandı mı |
| ai_tamamlanma | timestamp | ❌ | null | AI işleminin tamamlandığı zaman |
| onay_token | string(64) | ❌ | null | E-posta onay linki için token |
| onay_token_bitis | timestamp | ❌ | null | Token geçerlilik süresi (1 saat) |
| dergi_id | foreignId | ❌ | null | İlişkili dergi → `dergiler` |
| dergi_sayfa | smallInteger | ❌ | null | Dergideki sayfa numarası |
| dergi_notu | text | ❌ | null | Dergi özel notu |
| etkinlik_id | foreignId | ❌ | null | İlişkili etkinlik → `etkinlikler` |
| etkinlikte_goster | boolean | ✅ | false | Etkinlik sayfasında gösterilsin mi |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

---

### Tablo: `haber_kategorileri`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| ad | string(100) | ✅ | Kategori adı |
| slug | string(100) | ✅ | URL için, unique |
| sira | tinyInteger | ✅ | Sıralama |
| aktif | boolean | ✅ | true |
| created_at | timestamp | ✅ | — |

---

### Tablo: `haber_medyalar`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | — |
| haber_id | foreignId | ✅ | — | → `haberler` |
| tip | enum | ✅ | — | `gorsel` / `video` |
| sira | smallInteger | ✅ | — | Sıra no. 1 = ana görsel |
| orijinal_yol | string | ❌ | null | DO Spaces: `img26/ori/{haber-id}/{haber-slug}-001.jpg` |
| lg_yol | string | ❌ | null | DO Spaces: `img26/opt/{haber-id}/{haber-slug}-001-lg.webp` — 1280×720 |
| og_yol | string | ❌ | null | DO Spaces: `img26/opt/{haber-id}/{haber-slug}-001-og.webp` — 1200×675 |
| sm_yol | string | ❌ | null | DO Spaces: `img26/opt/{haber-id}/{haber-slug}-001-sm.webp` — 320×180 |
| video_url | string | ❌ | null | DO Spaces video yolu |
| alt_text | string(255) | ❌ | null | SEO görsel açıklaması |
| optimize_edildi | boolean | ✅ | false | Arka plan optimizasyonu tamamlandı mı |
| created_at | timestamp | ✅ | — | — |

---

### Tablo: `haber_kisiler` (Pivot)

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| haber_id | foreignId | ✅ | → `haberler` |
| kisi_id | foreignId | ✅ | → `kisiler` |
| onay_durumu | enum | ✅ | `beklemede` / `onaylandi` / `reddedildi` |
| onaylayan_id | foreignId | ❌ | → `yoneticiler` |
| onay_tarihi | timestamp | ❌ | — |
| created_at | timestamp | ✅ | — |

---

### Tablo: `haber_kurumlar` (Pivot)

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| haber_id | foreignId | ✅ | → `haberler` |
| kurum_id | foreignId | ✅ | → `kurumlar` |
| onay_durumu | enum | ✅ | `beklemede` / `onaylandi` / `reddedildi` |
| onaylayan_id | foreignId | ❌ | → `yoneticiler` |
| onay_tarihi | timestamp | ❌ | — |
| created_at | timestamp | ✅ | — |

---

## İş Kuralları

### Haber Ekleme Akışı

```
Yazar haberi yazar, görselleri/videoları yükler
        │
        ▼
"Yapay Zeka İşlemlerini Başlat" butonuna basar
(Kaydet/Yayınla butonu bu adım tamamlanmadan aktif olmaz)
        │
        ▼
Panel'de adım adım progress göstergesi:
  ① İmla kontrol ediliyor...
  ② Özet oluşturuluyor...
  ③ Meta description oluşturuluyor...
  ④ Slug üretiliyor...
  ⑤ Kişiler taranıyor...
  ⑥ Kurumlar taranıyor...
        │
        ▼
AI tamamlandı → ai_islendi = true
Panel bildirimi: "Yapay zeka işlemleri tamamlandı"
Kaydet butonu aktif olur
        │
        ▼
Yazar "Kaydet" der → durum: "incelemede"
        │
        ▼
Arka planda görseller optimize edilir (Queue/Job)
Optimizasyon tamamlanınca:
        │
        ▼
Yazara e-posta gönderilir:
  - Haber başlığı
  - Haber metni
  - Optimize görseller (mail eki)
  - [Onayla] butonu → 1 saatlik token içeren URL
  - [Revize Et] butonu → düzenleme sayfası URL
  - "Bu link 1 saat geçerlidir. Süre dolduktan sonra
     panelden manuel onay vermeniz gerekmektedir."
        │
        ├── Yazar [Onayla] linkine basar (1 saat içinde)
        │         → Token doğrulanır
        │         → durum: "yayinda" veya yayin_tarihi varsa zamanlanır
        │         → Panel bildirimi: "Haber yayına alındı"
        │
        ├── Yazar [Revize Et] linkine basar
        │         → Düzenleme sayfasına yönlendirilir
        │         → Düzenlemeler yapılır, tekrar Kaydet
        │         → Süreç başa döner
        │
        └── 1 saat doldu, link expired
                  → Panel bildirimi: "Token süresi doldu, manuel onay gerekiyor"
                  → Editör/Admin panelden manuel yayına alır
```

### Görsel Optimizasyon ve Depolama

```
Görsel yüklendi
        │
        ▼
Ham görsel geçici olarak sunucuya alınır
        │
        ▼
Optimizasyon Job kuyruğa girer (Kaydet'ten sonra çalışır)
        │
        ▼
DO Spaces'e yazılır:
  Orijinal → img26/ori/{haber-id}/{haber-slug}-001.jpg
  LG       → img26/opt/{haber-id}/{haber-slug}-001-lg.webp    (1280×720 — haber detay, manşet slider)
  OG       → img26/opt/{haber-id}/{haber-slug}-001-og.webp    (1200×675 — sosyal medya paylaşım)
  SM       → img26/opt/{haber-id}/{haber-slug}-001-sm.webp    (320×180  — sidebar, ilgili haberler)

Çoklu görsellerde sıra numarası artar: -001, -002, -003...
        │
        ▼
optimize_edildi = true
Optimizasyon tamamlanınca e-posta gönderilir
```

### Slug Üretim Kuralları

```
Başlık girilince otomatik slug üretilir:
  - Türkçe karakterler dönüştürülür (ş→s, ğ→g, ü→u, ö→o, ç→c, ı→i)
  - Küçük harfe çevrilir
  - Bağlaçlar temizlenir: "ve", "ile", "de", "da", "bir", "bu", "için"
  - Boşluklar "-" olur
  - Özel karakterler temizlenir
  - 50-60 karakter idealdir, 100'de kesilir
  - Aynı slug varsa sonuna -2, -3 eklenir
  - Kullanıcı isterse manuel düzenleyebilir
```

### Manşet Kuralları

```
Aynı anda maksimum 10 haber manşet olarak aktif olabilir.
10 doluysa yeni manşet seçiminde uyarı gösterilir:
"Manşet limitine ulaşıldı. Önce mevcut bir manşeti kaldırın."

manset = true ise:
  - Ana sayfada slider'a alınır
  - Haber sayfasında veya kendi kategorisinde manşet alanında gösterilir

Manşet görseli:
  - Masaüstü ve mobil için ayrı görsel yüklenebilir
  - Yüklenmezse ikisi için de haber ana görseli (sira=1) kullanılır
  - Yüklenirse kırpma aracı (cropper) ile düzenlenir:
      Masaüstü: 1920×1080 (16:9)
      Mobil: 768×432 (16:9)
```

### Etkinlik İlişkilendirme Kuralları

```
Etkinlik seçimi:
  - Mevcut tarihten sonraki etkinlikler gösterilir
  - 30 gün öncesine kadar geçmiş etkinlikler gösterilir
  - Etkinlik seçilince "Etkinlik sayfasında göster" toggle'ı çıkar (varsayılan: açık)

etkinlikte_goster = true ise:
  - Etkinlik sayfasına haber linki eklenir
  - Haber medyaları etkinlik medyalarına iliştirilir
```

### Onay Token Kuralları

```
Token oluşturma:
  - 64 karakterlik rastgele token
  - Oluşturulduktan 1 saat sonra expire olur
  - Tek kullanımlık — kullanıldıktan sonra null'a çekilir

Token expire olduktan sonra:
  - Link tıklanırsa "Bu link geçersiz" sayfası gösterilir
  - Panel bildirimi: "Haber manuel onay bekliyor"
  - Editör/Admin panelden yayına alır
```

### Kişi/Kurum AI Tespiti (bkz. kisiler.md ve kurumlar.md)

```
AI tarama sonuçları panel formunda listelenir:
  Her kişi/kurum için üç ikon:
    ✅ Onayla  → haber_kisiler/haber_kurumlar tablosuna "onaylandi" olarak eklenir
    ✏️ Düzenle → bilgileri düzenleme modalı açılır
    ❌ İptal   → bu tespiti sil, ekleme

Onaylananlar frontend'de haberin altında gösterilir.
```

### Silme Kuralı

Haber silindiğinde `deleted_at` doldurulur (soft delete).
DO Spaces'teki görseller 30 gün sonra temizlenir (scheduled job).
Bağlı `haber_kisiler` ve `haber_kurumlar` kayıtları silinir.

---

## AI Entegrasyonu — Google Cloud (Gemini Pro)

| Görev | Açıklama |
|---|---|
| İmla düzeltme | Sadece yazım yanlışları düzeltilir. Kelime veya cümle anlamını değiştirecek müdahale yapılmaz |
| Özet | 250-300 karakter veya max 3 maddelik liste. GEO için optimize |
| Meta description | 150-160 karakter arası, SEO odaklı |
| Slug önerisi | Başlıktan bağlaçsız, anahtar kelime bazlı slug üretilir |
| Kişi/kurum tespiti | Metinden NER ile kişi ve kurum adları çıkarılır |
| Tetiklenme | "Yapay Zeka İşlemlerini Başlat" butonuyla manuel, senkron (progress göstergeli) |
| İmla Kısıtı | Sadece yazım hatası düzeltilir. Anlam, üslup veya cümle yapısına müdahale edilmez |

**AI Progress Adımları (sırasıyla):**
1. İmla kontrolü
2. Özet üretimi
3. Meta description üretimi
4. Slug üretimi
5. Kişi tespiti
6. Kurum tespiti

Her adım tamamlanınca panel'de ilgili adım yeşile döner.

---

## Frontend

> **Kritik Not:** Frontend SEO ve GEO uyumu %100 önceliklidir. Tüm meta etiketler, structured data ve schema markup'lar eksiksiz uygulanır. Detaylar frontend aşamasında ayrıca ele alınacak.

### Sayfalar

| Sayfa | URL | Açıklama |
|---|---|---|
| Liste | `/haberler` | Yayındaki haberler, kategori filtresi, sayfalama |
| Kategori | `/haberler/{kategori-slug}` | Kategoriye göre haber listesi |
| Detay | `/haberler/{slug}` | Haber detayı, galeri, ilgili kişi/kurumlar |

### Frontend Davranışı

- Sadece `durum = yayinda` ve `yayin_tarihi <= now()` haberler görünür
- Haber altında onaylanmış kişi/kurumlar listelenir
- Kişinin `uye_id` dolu ve mezun rozeti varsa adın yanında rozet gösterilir
- Manşet haberleri ana sayfada slider'a ve kategori sayfasında üst alana alınır
- SEO: `baslik` → `<title>`, `meta_description` → `<meta description>`, ana görsel → OG image
- **Schema Markup:** `NewsArticle` schema — başlık, yazar, yayın tarihi, görsel, özet
- **Article Meta:** `<meta property="article:author">`, `article:published_time`, `article:modified_time`, `article:section`
- **Open Graph:** `og:title`, `og:description`, `og:image`, `og:type: article`
- **Twitter Card:** `twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`
- **GEO:** `summary` alanı GEO (Generative Engine Optimization) için optimize — kısa, net, yapılandırılmış
- **Canonical URL:** Her haber için canonical tag
- **Sitemap:** Yayına giren haberler otomatik olarak sitemap.xml'e eklenir
- **robots.txt:** Taslak/incelemede haberler `noindex` ile işaretlenir
- Etkinlikle ilişkiliyse haber altında etkinlik kartı gösterilir

---

## Backend Admin Panel — Form Alanları

### Sol Kolon (Ana İçerik)
- **Haber Başlığı** — string, max 60 karakter, karakter sayacı, SEO başlığı ile senkron
- **Haber İçeriği** — Filament Rich Editor
  - Araç çubuğu: `bold`, `italic`, `underline`, `link`, `blockquote`, `alignStart`, `alignCenter`, `alignEnd`, `bulletList`, `orderedList`, `table`, `undo`, `redo`
- **Haber Medyaları** — Görsel ve video ayrı sekmelerde
  - Görsel: çoklu yükleme, sürükle-bırak sıralama, ana görsel belirleme (sira=1)
  - Video: DO Spaces'e doğrudan yükleme
- **Haberde Yer Alan Kişiler** — AI tespiti sonuçları + ✅ ✏️ ❌ ikonları
- **Haberde Yer Alan Kurumlar** — AI tespiti sonuçları + ✅ ✏️ ❌ ikonları

### Sağ Kolon (Meta & Ayarlar)
- **Kategori** — dropdown, admin tarafından yönetilebilir
- **Slug** — otomatik üretilir, düzenlenebilir, 50-60 karakter göstergesi
- **Summary (Haber Özeti)** — textarea, 250-300 karakter sayacı, AI doldurur
- **Meta Description** — textarea, 150-160 karakter sayacı, AI doldurur
- **İleri Tarihte Yayınla** — tarih + saat seçici
- **Manşet** — toggle
  - Açıksa: masaüstü/mobil görsel yükleme + kırpma aracı
- **Editör Notları** — textarea, sadece panelde görünür

### Dergi Kartı (Sağ Kolon — Ayrı Card)
- Dergi seçimi (dropdown — mevcut dergilerden)
- Haber ID (otomatik dolar, gösterim amaçlı)
- Dergi sayfası (sayı girişi)
- Dergi özel notu (textarea)

### Etkinlik Kartı (Sağ Kolon — Ayrı Card)
- Etkinlik seçimi (dropdown)
  - Bugünden itibaren gelecekteki etkinlikler
  - 30 gün öncesine kadar geçmiş etkinlikler
- "Etkinlik sayfasında göster" toggle

### AI Butonu
- Form üstünde belirgin "Yapay Zeka İşlemlerini Başlat" butonu
- Tıklanınca progress modal açılır (6 adım, her adım tamamlanınca yeşil)
- Tamamlanana kadar Kaydet butonu disabled + tooltip: "Önce AI işlemini tamamlayın"

---

## Belirsizlikler / Açık Kararlar

- [ ] Manşet aynı anda kaç haber için aktif olabilir? (Sınır var mı?)
