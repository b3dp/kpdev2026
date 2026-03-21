# Modül: Dergiler

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Dergiler |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Orta |
| Bağımlı Modüller | Haberler, Etkinlikler, Kişiler, Kurumlar, Pazarlama - Eposta, Mezunlar, Loglar, Roller, Yöneticiler |
| Arama | TNTSearch — bkz. genel-panel-notlari.md |

---

## Roller ve Yetkiler

| Rol | Yeni | Düzenle | İçerik Ekle | Kilitle | Sil | İndir |
|---|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Yazar | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |

> **Not:** Yazar sadece "Dergiye Ekle" butonu ile içerik ekleyebilir. Dergi oluşturma ve düzenleme yapamaz. Durum "Baskıya Hazır" olduktan sonra içerik kilitlenir, hiç kimse değişiklik yapamaz.

---

## Veritabanı

### Tablo: `dergiler`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| yonetici_id | foreignId | ✅ | — | Oluşturan → `yoneticiler` |
| ad | string(255) | ✅ | — | Dergi adı (örn: Kestanepazarı 2026 Ramazan Dergisi) |
| yil | smallInteger | ✅ | — | Yayın yılı |
| donem | string(100) | ❌ | null | Dönem açıklaması (örn: Ramazan 2026) |
| toplam_sayfa | smallInteger | ❌ | null | Hedef toplam sayfa sayısı (doluluk hesabı için) |
| durum | enum | ✅ | taslak | `taslak` / `duzenleniyor` / `baskiya_hazir` |
| kapak_gorseli | string | ❌ | null | DO Spaces: `img26/opt/{dergi-slug}-kapak.webp` |
| kapak_gorseli_orijinal | string | ❌ | null | DO Spaces: `img26/ori/{dergi-slug}-kapak-original.jpg` |
| pdf_web | string | ❌ | null | DO Spaces: `img26/pdf26/dergiler/{yil}/{dergi-slug}-web.pdf` — Web versiyonu |
| pdf_baski | string | ❌ | null | DO Spaces: `img26/pdf26/dergiler/{yil}/{dergi-slug}-baski.pdf` — Baskı versiyonu |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

---

### Tablo: `dergi_sayfalar`

Her sayfa bir satır. Sayfa numarası ve içerdiği toplam sayfa adedi tutulur.

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | — |
| dergi_id | foreignId | ✅ | — | → `dergiler` |
| sayfa_no | smallInteger | ✅ | — | Dergideki başlangıç sayfa numarası |
| icerik_tip | enum | ✅ | — | `haber` / `etkinlik` |
| icerik_id | bigInteger | ✅ | — | İlgili haber veya etkinlik ID'si (polymorphic) |
| sayfa_adedi | tinyInteger | ✅ | 1 | Bu içeriğin kaç sayfa kapladığı |
| sira | smallInteger | ✅ | — | Aynı sayfadaki sıralama (birden fazla içerik varsa) |
| dergi_baslik | string(255) | ❌ | null | Dergiye özel başlık. Boşsa orijinal başlık kullanılır |
| dergi_metni | longText | ❌ | null | Dergiye özel düzenlenmiş metin. Boşsa orijinal metin kullanılır |
| orijinale_link | boolean | ✅ | true | Orijinal habere/etkinliğe link gösterilsin mi |
| editor1_id | foreignId | ❌ | null | 1. onayı veren editör → `yoneticiler` |
| editor1_onay | enum | ✅ | beklemede | `beklemede` / `onaylandi` / `reddedildi` |
| editor1_onay_tarihi | timestamp | ❌ | null | — |
| editor2_id | foreignId | ❌ | null | 2. onayı veren editör → `yoneticiler` |
| editor2_onay | enum | ✅ | beklemede | `beklemede` / `onaylandi` / `reddedildi` |
| editor2_onay_tarihi | timestamp | ❌ | null | — |
| yonetici_id_onay | foreignId | ❌ | null | Dergi yöneticisi onayı → `yoneticiler` |
| yonetici_onay | enum | ✅ | beklemede | `beklemede` / `onaylandi` / `reddedildi` |
| yonetici_onay_tarihi | timestamp | ❌ | null | — |
| not | text | ❌ | null | Sayfa/içerik özel notu |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |

---

## İş Kuralları

### Dergi Oluşturma Akışı

```
Editör/Admin yeni dergi oluşturur
  Ad, yıl, dönem, hedef sayfa sayısı girilir
  Durum: "taslak"
        │
        ▼
İçerik toplama aşaması başlar
  Haberler/Etkinlikler listesinde "Dergiye Ekle" butonu aktif
  İçerikler dergiye eklenir (sayfa/sıra sonra belirlenir)
  Durum: "duzenleniyor"
        │
        ▼
Dergi Düzenleme ekranında (Kanban) sayfa düzeni yapılır
  Sayfalar oluşturulur, içerikler sürükle-bırak ile yerleştirilir
  Her içerik için sayfa adedi girilir
  Sistem toplam sayfa sayısını hesaplar
        │
        ▼
Editör "Baskıya Hazır" yapar
  Durum: "baskiya_hazir"
  İçerik kilitlenir — artık değişiklik yapılamaz
  Opsiyonel: PDF yüklenir
```

### "Dergiye Ekle" Butonu

```
Haberler veya Etkinlikler listesinde her kayıtta buton görünür
İçerik zaten bir dergiye eklenmişse buton "Dergide: [Dergi Adı]"
olarak görünür ve tıklanamaz.
        │
        ▼
Tıklanınca dropdown: aktif dergiler listelenir
        │
        ▼
Dergi seçilir → dergi_sayfalar tablosuna eklenir
Unique kural: bir içerik yalnızca tek dergiye eklenebilir
  sayfa_no ve sira bu aşamada boş bırakılabilir
  Kanban ekranında sonradan düzenlenir
        │
        ▼
"Bu içerik dergiye eklendi" panel bildirimi
Haberler/Etkinlikler listesinde ilgili kayıt rozet ile işaretlenir
```

### Kanban Düzenleme Ekranı

```
Her sütun = bir sayfa
Her kart = bir haber veya etkinlik

Kart üzerindeki bilgiler:
  - İçerik başlığı
  - İçerik tipi (Haber / Etkinlik) rozeti
  - Sayfa adedi input (kaç sayfa kaplıyor)
  - Sıra numarası (sütun içindeki sıra)
  - Özel not alanı
  - InDesign tag alanları

Sütun (Sayfa) bilgileri:
  - Sayfa numarası
  - O sayfadaki toplam içerik sayısı
  - "Sayfa Sil" butonu (içerik başka sayfaya taşınırsa)

Uyarı sistemi:
  - Sayfa çakışması: iki içerik aynı sayfa_no'ya denk gelirse uyarı
  - Doluluk: "48/64 sayfa dolu (%75)" progress bar
  - Boş sayfa uyarısı: sayfa_no atlanmışsa uyarı
```

### Baskı Önizleme (Mockup)

```
Kanban'ın yanında "Baskı Önizleme" modu:
  Her sayfa A4 oranında dikdörtgen
  İçinde:
    - Sayfa numarası (örn: "34. Sayfa" veya "34-38. Sayfalar")
    - Haber/etkinlik başlığı (dergi_baslik varsa o, yoksa orijinal)
    - Ana görsel (sm versiyonu — 320×180)

PDF olarak indirilebilir:
  "Dergi İçerik Planı - 2026.pdf"
  → img26/pdf26/dergiler/{yil}/{slug}-icerik-plani.pdf
```

### Metin Onay Akışı

```
İçerik dergiye eklendi (editor1_onay: beklemede)
        │
        ▼
1. Editör gelir → metni inceler/düzenler → Onay verir
  editor1_onay: onaylandi
  editor1_id: editörün ID'si
  editor1_onay_tarihi: now()
        │
        ▼
2. Editör gelir → metni inceler/düzenler → Onay verir
  editor2_onay: onaylandi
  (1. editörün onayı olmadan 2. editör onay veremez)
        │
        ▼
Dergi Yöneticisi gelir → son kontrolü yapar → Onay verir
  yonetici_onay: onaylandi
  (İki editör onayı olmadan yönetici onayı veremez)
        │
        ▼
İçerik tamamen onaylanmış — Kanban'da yeşil rozet
```

**Reddetme Durumu:**
```
Herhangi bir aşamada red verilirse:
  → İlgili onay: reddedildi
  → Önceki onaylar sıfırlanmaz ama süreç başa döner
  → Düzenleyen editöre panel bildirimi: "Metin reddedildi, revize gerekiyor"
  → Düzenleme yapılıp tekrar onaya sunulur
  → Red veren kişinin onayı sıfırlanır, tekrar onaylaması gerekir
```

**Onay Durumu Göstergeleri (Kanban Kartında):**

| Durum | Görünüm |
|---|---|
| Tüm onaylar tamam | 🟢 Yeşil rozet — "Onaylandı" |
| Kısmen onaylandı | 🟡 Sarı rozet — "X/3 Onay" |
| Herhangi biri reddetti | 🔴 Kırmızı rozet — "Reddedildi" |
| Henüz başlanmadı | ⚪ Gri rozet — "Beklemede" |

Liste görünümünde her içerik için 3 onay ikonu yan yana:
```
[E1 ✅] [E2 ⏳] [YÖN ⏳]   → 1. editör onayladı, diğerleri bekliyor
[E1 ✅] [E2 ✅] [YÖN ✅]   → Tüm onaylar tamam
[E1 ✅] [E2 ❌] [YÖN ⏳]   → 2. editör reddetti
```

### Dergi Metni Düzenleme

Her içerik kartında "Metni Düzenle" butonu bulunur.

```
Metni Düzenle butonuna basılır
        │
        ▼
Modal/Drawer açılır — iki sütun:
  Sol: Orijinal haber metni (salt okunur, referans)
  Sağ: Dergi metni (düzenlenebilir rich editor)
        │
        ├── İlk açılışta sağ taraf orijinal metni kopyalar
        │   Kullanıcı geçmiş zaman düzeltmelerini yapar
        │
        └── Kaydet → dergi_metni alanına yazılır
                    Orijinal haberle bağlantı korunur
```

**Kopya Durumu Göstergesi:**
- Kart üzerinde: "Orijinal Metin" (düzenleme yapılmamış) veya "Dergi Metni" (düzenlenmiş) rozeti
- Düzenlenmiş metinler turuncu rozet ile işaretlenir

### InDesign Metin Kopyalama

Her içerik kartında iki ayrı buton:

**Başlık Kopyala:**
```
dergi_baslik doluysa → dergi_baslik kopyalanır
dergi_baslik boşsa  → orijinal baslik kopyalanır
```

**İçerik Kopyala:**
```
dergi_metni doluysa → dergi_metni kopyalanır
dergi_metni boşsa  → orijinal icerik kopyalanır
```

Düz metin olarak kopyalanır — HTML tag'leri temizlenir, paragraf yapısı korunur.

### Görsel İndirme — ZIP

**Tek içerik indirme:**
```
Sayfa01-Haber-Adi.zip
  └── sayfa01-haber-adi-001.jpg   (orijinal)
  └── sayfa01-haber-adi-002.jpg
  └── sayfa01-haber-adi-003.jpg
```

**Tüm derginin ZIP'i:**
```
Dergi-2026-Ramazan.zip
  ├── Sayfa01-Haber-Adi/
  │     └── sayfa01-haber-adi-001.jpg
  ├── Sayfa02-Diger-Haber/
  │     └── sayfa02-diger-haber-001.jpg
  │     └── sayfa02-diger-haber-002.jpg
  └── ...
```

Dosya adı formatı: `SayfaXX-haber-slug.zip` (XX = iki haneli sayfa numarası, örn: 01, 02, 12)
ZIP oluşturma işlemi Queue/Job ile asenkron çalışır — büyük dergiler için uzun sürebilir.
Hazır olunca panel bildirimi gönderilir.
ZIP dosyası oluşturulduktan 1 gün sonra DO Spaces'ten otomatik silinir (scheduled job).

### PDF Yükleme

Durum "Baskıya Hazır" olmadan PDF yüklenemez.
İki ayrı versiyon yüklenebilir (ikisi de opsiyonel):

| Versiyon | Alan | DO Spaces Yolu |
|---|---|---|
| Web Versiyonu | `pdf_web` | `img26/pdf26/dergiler/{yil}/{dergi-slug}-web.pdf` |
| Baskı Versiyonu | `pdf_baski` | `img26/pdf26/dergiler/{yil}/{dergi-slug}-baski.pdf` |

Web versiyonu: düşük çözünürlük, ekran okuma için optimize.
Baskı versiyonu: yüksek çözünürlük, matbaa için.

### Kapak Görseli

Dergi kapak görseli ayrıca yüklenebilir.
```
Orijinal → img26/ori/{dergi-slug}-kapak-original.jpg
Optimize → img26/opt/{dergi-slug}-kapak.webp
```
Kırpma aracı ile düzenlenebilir (16:9 veya A4 oranı).

### Kilitleme Kuralı

Durum "Baskıya Hazır" yapılabilmesi için tüm içeriklerin
3 onayı (E1 + E2 + Yönetici) tamamlanmış olmalıdır.
Onaylanmamış içerik varsa sistem uyarı verir:
"X içeriğin onayı tamamlanmamış. Baskıya almak için onaylayın veya dergiden çıkarın."

```
Durum "baskiya_hazir" yapıldığında:
  - dergi_sayfalar tablosundaki tüm kayıtlar kilitlenir
  - Kanban'da sürükle-bırak devre dışı kalır
  - Kart düzenleme devre dışı kalır
  - "Kilidi Aç" sadece Admin yapabilir → durum "duzenleniyor"'a döner
```

### Silme Kuralı

Soft delete. Silindiğinde bağlı `dergi_sayfalar` kayıtları da silinir.
Haberler tablosundaki `dergi_id`, `dergi_sayfa`, `dergi_notu` alanları null'a çekilir.

---

## Backend Admin Panel

### Dergi Listesi

- Tüm dergiler listelenir: ad, yıl, dönem, durum, doluluk oranı, içerik sayısı
- Filtre: yıl, durum
- Her dergide: Düzenle, Önizle, ZIP İndir, PDF Web Yükle, PDF Baskı Yükle butonları
- Onay durumu özeti: "XX/YY içerik onaylandı" göstergesi
- "Baskıya Hazır" dergiler rozet ile işaretlenir, düzenleme butonları gizlenir

### Dergi Detay / Düzenleme Ekranı

**Üst alan:**
- Dergi adı, yıl, dönem, hedef sayfa sayısı
- Doluluk progress bar: `XX/YY sayfa dolu (%ZZ)`
- Durum değiştirme butonu
- "Tüm ZIP İndir" + "Baskı Önizleme PDF" butonları
- "PDF Web Yükle" + "PDF Baskı Yükle" butonları (ayrı ayrı)
- Kapak Görseli yükleme alanı
- Onay özeti: "XX/YY içerik onaylandı" progress bar

**Kanban alanı:**
- Her sütun bir sayfa — yatay scroll
- Sütun başlığında sayfa numarası ve içerik sayısı
- Kartlar sürükle-bırak ile taşınır
- "Sayfa Ekle" butonu ile yeni sütun açılır
- Her kartta: başlık, tip rozeti, sayfa adedi, sıra, not, InDesign butonları, ZIP İndir

**Sağ panel — Eklenmeyen İçerikler:**
- Bu dergiye henüz eklenmemiş haberler/etkinlikler listesi
- Tarih aralığı filtresi
- Sürükle-bırak ile kanban'a eklenebilir

---

## Belirsizlikler / Açık Kararlar

