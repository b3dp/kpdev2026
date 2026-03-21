# Modül: Etkinlikler

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Etkinlikler |
| Backend | Evet |
| Frontend | Evet |
| Öncelik | Orta — Haberler modülünden sonra |
| Bağımlı Modüller | Haberler, Kişiler, Kurumlar, Pazarlama - SMS, Pazarlama - Eposta, Loglar, Roller, Yöneticiler |
| Arama | TNTSearch — bkz. genel-panel-notlari.md |

---

## Roller ve Yetkiler

| Rol | Yeni | Düzenle | Kaydet | Yayınla | Sil |
|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ✅ |
| Yazar | ✅ | ✅ (kendi) | ✅ | ❌ | ❌ |

---

## Veritabanı

### Tablo: `etkinlikler`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| yonetici_id | foreignId | ✅ | — | Ekleyen yönetici → `yoneticiler` |
| ad | string(255) | ✅ | — | Etkinlik adı |
| slug | string(255) | ✅ | — | Otomatik üretilir, unique |
| aciklama | longText | ❌ | null | Etkinlik açıklaması (rich text) |
| tarih | datetime | ✅ | — | Etkinlik tarihi ve saati |
| durum | enum | ✅ | taslak | `taslak` / `yayinda` / `iptal` |
| konum_ad | string(500) | ❌ | null | Konum adı (örn: Kestanepazarı Kültür Merkezi) |
| konum_adres | string(500) | ❌ | null | Tam adres metni |
| konum_lat | decimal(10,7) | ❌ | null | Google Maps enlem |
| konum_lng | decimal(10,7) | ❌ | null | Google Maps boylam |
| konum_place_id | string(255) | ❌ | null | Google Places API place_id |
| gorsel_masaustu_lg | string | ❌ | null | DO Spaces: `img26/opt/{etkinlik-id}/{slug}-lg.webp` — 1280×720 |
| gorsel_masaustu_og | string | ❌ | null | DO Spaces: `img26/opt/{etkinlik-id}/{slug}-og.webp` — 1200×675 |
| gorsel_masaustu_sm | string | ❌ | null | DO Spaces: `img26/opt/{etkinlik-id}/{slug}-sm.webp` — 320×180 |
| gorsel_mobil_lg | string | ❌ | null | DO Spaces: `img26/opt/{etkinlik-id}/{slug}-mobil-lg.webp` — 768×432 |
| gorsel_orijinal | string | ❌ | null | DO Spaces: `img26/ori/{etkinlik-id}/{slug}-original.jpg` |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

---

## İş Kuralları

### Görsel Kuralları

Servisler.md'deki 16:9 standartlarına uygun:

```
Yüklenen orijinal görsel → img26/ori/{etkinlik-id}/{slug}-original.jpg

Otomatik üretilen versiyonlar (intervention/image + smart crop):
  Masaüstü LG  → img26/opt/{etkinlik-id}/{slug}-lg.webp      1280×720
  Masaüstü OG  → img26/opt/{etkinlik-id}/{slug}-og.webp      1200×675
  Masaüstü SM  → img26/opt/{etkinlik-id}/{slug}-sm.webp      320×180
  Mobil        → img26/opt/{etkinlik-id}/{slug}-mobil-lg.webp 768×432
```

Masaüstü ve mobil için ayrı görsel yüklenebilir.
Yüklenmezse masaüstü görseli her ikisi için kullanılır.

### Konum — Google Maps / Places API

```
Yönetici konum alanına yazmaya başlar
        │
        ▼
Google Places Autocomplete API devreye girer
Öneriler dropdown olarak listelenir
        │
        ▼
Seçim yapılır:
  konum_ad       → Places API'den dönen isim
  konum_adres    → Tam adres
  konum_lat      → Enlem
  konum_lng      → Boylam
  konum_place_id → Google Places ID (ileride detay çekmeye yarar)
        │
        ▼
Form altında Google Maps mini harita gösterilir
Seçilen konum pin ile işaretlenir
İstenirse pin manuel sürüklenerek hassas konum belirlenebilir
```

### Galeri Kuralları

Etkinlik galerisini haberler oluşturur.
`haberler.etkinlik_id` üzerinden ilişkilendirilen haberler etkinliğin galerisini besler.
Etkinlik sayfasında `etkinlikte_goster = true` olan haberlerin medyaları galeri olarak listelenir.
Etkinlik modülünde ayrıca görsel yükleme alanı yoktur.

### İlişkili Haberler

Haberlerin `etkinlik_id` alanı üzerinden bağlantı kurulur.
Etkinlik formunda sadece ilişkilendirilmiş haberler listelenir — düzenleme yapılmaz.
Haber ekleme/kaldırma işlemi Haberler modülünden yapılır.

### Silme Kuralı

Soft delete. Silindiğinde bağlı haberlerin `etkinlik_id` alanı null'a çekilir.

### Takvime Ekleme

Frontend'de yapılacak. Backend'de ek bir alan gerekmez.
İCS dosyası oluşturma endpoint'i: `GET /etkinlikler/{slug}/takvim.ics`

---

## Google Maps / Places API

| Özellik | Detay |
|---|---|
| Places Autocomplete | Konum arama ve otomatik tamamlama |
| Maps Embed | Form içinde mini harita gösterimi |
| Frontend | Etkinlik detay sayfasında interaktif harita |

**ENV Değişkenleri:**
```
GOOGLE_MAPS_API_KEY=
GOOGLE_MAPS_API_KEY_PUBLIC=   (frontend için ayrı, domain kısıtlı key)
```

> **Not:** Backend key ve frontend key ayrı tutulur. Frontend key sadece belirlenen domain'den istek kabul edecek şekilde Google Console'dan kısıtlanır.

---

## Backend Admin Panel — Form Alanları

### Sol Kolon
- **Etkinlik Adı** — string, max 255 karakter
- **Etkinlik Açıklaması** — Filament Rich Editor (haberler ile aynı araç çubuğu)
- **Etkinlik Tarihi ve Saati** — datetime picker
- **Konum** — Google Places Autocomplete arama kutusu
  - Seçim sonrası: adres, koordinat, place_id otomatik dolar
  - Mini harita: seçilen konumu gösterir, pin sürüklenebilir

### Sağ Kolon
- **Durum** — dropdown: Taslak / Yayında / İptal
- **Slug** — otomatik üretilir, düzenlenebilir
- **Etkinlik Görseli**
  - Masaüstü: görsel yükleme + kırpma aracı (16:9)
  - Mobil: görsel yükleme + kırpma aracı (16:9)
  - Yüklenmezse masaüstü görseli kullanılır

### İlişkili Haberler (Alt Bölüm — Salt Okunur)
- Bu etkinliğe bağlı haberler listelenir
- Her haber: başlık, yayın tarihi, "Habere Git" linki
- Düzenleme yapılamaz — Haberler modülünden yönetilir

---

## Frontend

> **Not:** Takvime ekleme özelliği frontend aşamasında yapılacak.

### Sayfalar

| Sayfa | URL | Açıklama |
|---|---|---|
| Liste | `/etkinlikler` | Yaklaşan + geçmiş etkinlikler |
| Detay | `/etkinlikler/{slug}` | Etkinlik detayı, galeri, konum haritası, ilgili haberler |
| Takvim ICS | `/etkinlikler/{slug}/takvim.ics` | Takvime ekleme dosyası |

### Frontend Davranışı

- Yaklaşan etkinlikler tarihe göre ASC, geçmiş etkinlikler DESC sıralanır
- Konum: interaktif Google Maps embed
- Galeri: ilişkili haberlerin `etkinlikte_goster = true` medyaları
- İlişkili haberler: etkinlik altında haber kartları
- SEO: `NewsEvent` schema markup, OG tags, canonical URL
- **Takvime Ekle:** Google Calendar, Apple Calendar, Outlook seçenekleri (ICS)

---

## Belirsizlikler / Açık Kararlar

- [ ] Geçmiş etkinlikler frontend'de gösterilecek mi, yoksa sadece yaklaşanlar mı?
- [ ] Etkinlik iptali durumunda kayıtlı kişilere SMS/e-posta bildirimi gidecek mi?
- [ ] Birden fazla etkinlik tarihi/saati olabilir mi? (Örn: 2 günlük etkinlik)
