# Modül: İrşad

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | İrşad |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Düşük |
| Bağımlı Modüller | Kurumlar, Kişiler, Pazarlama - SMS, Pazarlama - Eposta, Loglar, Roller, Yöneticiler |
| Arama | TNTSearch — bkz. genel-panel-notlari.md |

> **Not:** Bu modül şu an temel haliyle kurulacak. İleride hafız takibi, raporlama ve ek veri alanları eklenebilir.

---

## Roller ve Yetkiler

| Rol | Yeni | Düzenle | Listele | Sil |
|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ❌ |
| Diğerleri | ❌ | ❌ | ❌ | ❌ |

---

## Veritabanı

### Tablo: `irsad_camiler`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| kurum_id | foreignId | ❌ | null | Kurumlar tablosuyla opsiyonel eşleşme → `kurumlar` |
| cami_adi | string(255) | ✅ | — | Serbest metin — kurum eşleşmesi zorunlu değil |
| il | string(100) | ✅ | — | Sabit dropdown |
| ilce | string(100) | ✅ | — | Sabit dropdown (ile göre filtrelenir) |
| adres | text | ❌ | null | Tam adres |
| konum_lat | decimal(10,7) | ❌ | null | Google Maps enlem |
| konum_lng | decimal(10,7) | ❌ | null | Google Maps boylam |
| konum_place_id | string(255) | ❌ | null | Google Places ID |
| aktif | boolean | ✅ | true | — |
| not | text | ❌ | null | Sadece panelde görünür |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

---

### Tablo: `irsad_gorevliler`

Her camiye N adet görevli eklenebilir. Şu an genellikle 2 kişi (imam + müezzin).

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | — |
| cami_id | foreignId | ✅ | — | → `irsad_camiler` |
| kisi_id | foreignId | ❌ | null | Kişiler tablosuyla opsiyonel eşleşme → `kisiler` |
| ad_soyad | string(255) | ✅ | — | — |
| gorev | string(100) | ❌ | null | İmam / Müezzin / Diğer |
| telefon | string(20) | ❌ | null | — |
| eposta | string(255) | ❌ | null | — |
| aktif | boolean | ✅ | true | — |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |

---

### Tablo: `irsad_bagislar`

Her cami için yıl bazlı bağış kaydı.

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| cami_id | foreignId | ✅ | → `irsad_camiler` |
| yil | smallInteger | ✅ | Bağış yılı (örn: 2026) |
| tutar | decimal(10,2) | ✅ | Toplanan bağış tutarı |
| not | text | ❌ | Ek açıklama |
| kaydeden_id | foreignId | ✅ | → `yoneticiler` |
| created_at | timestamp | ✅ | — |
| updated_at | timestamp | ✅ | — |

> **Not:** Aynı cami için aynı yılda birden fazla bağış kaydı girilebilir. Yıl bazlı toplam raporda `SUM` ile hesaplanır.

---

## İş Kuralları

### Konum Belirleme

```
Cami adı alanına yazılmaya başlanır
        │
        ▼
Google Places Autocomplete devreye girer
Seçilirse: konum_lat, konum_lng, konum_place_id, adres otomatik dolar
        │
        ▼
VEYA "Konumumu Kullan" butonu:
  Tarayıcı geolocation API ile mevcut konum alınır
  Harita üzerinde pin düşer, sürüklenebilir
```

### Kurumlar Eşleşmesi

```
Cami kaydedilirken kurum_id opsiyonel seçilebilir
  Eşleşirse: Kurumlar modülündeki bilgilerle bağlantı kurulur
  Eşleşmezse: Serbest metin olarak saklanır
```

### Silme Kuralı

Soft delete. Camiye bağlı görevliler ve bağış kayıtları korunur — sadece listede görünmez.

---

## Backend Admin Panel

### Cami Listesi

**Filtreler:**
- İl / İlçe
- Aktif / Pasif
- Bağış kaydı olan / olmayan yıla göre

**Liste Kolonları:**

| Kolon | Açıklama |
|---|---|
| Cami Adı | — |
| İl / İlçe | — |
| Görevli Sayısı | — |
| Son Bağış Yılı | En son bağış kaydının yılı |
| Son Bağış Tutarı | — |
| İşlemler | Düzenle / Sil / Detay |

**Arama:** Cami adı, il, ilçe, görevli adı, telefon

---

### Cami Ekle / Düzenle

**Sol Kolon:**
- Cami Adı
- Kurum Eşleşmesi (dropdown — opsiyonel)
- Adres
- İl (dropdown)
- İlçe (dropdown — ile göre filtrelenir)
- Konum (Google Places Autocomplete + harita + "Konumumu Kullan" butonu)
- Not

**Görevliler Kartı:**
- Görevli listesi (dinamik — "Görevli Ekle" butonu)
- Her görevli: Ad Soyad, Görev (İmam/Müezzin/Diğer), Telefon, E-posta
- Kişiler modülüyle opsiyonel eşleşme
- **İletişim Butonları:** WhatsApp (`wa.me`) | Ara | E-posta

**Bağış Kayıtları Kartı:**
- Yıl bazlı liste: yıl, tutar, not, kaydeden
- "Bağış Ekle" butonu: yıl seçimi + tutar + not
- Yıl bazlı toplam görünür
- Tüm yılların genel toplamı

---

### Cami Detay

- Cami bilgileri
- Google Maps embed (konum varsa)
- Görevliler listesi + iletişim butonları
- Bağış geçmişi (yıl yıl)
- Loglar (bu camide yapılan işlemler)

---

## Gelecekte Eklenebilecekler

Şu an kapsam dışı — ileride geliştirilebilir:

- Gönderilen hafız kaydı (hangi yıl hangi hafız gitti)
- Ramazan öncesi otomatik hatırlatma (SMS/e-posta kampanyası tetiklemesi)
- Cami bazlı yıllık karşılaştırmalı bağış raporu
- Bölge bazlı harita görünümü
- Ziyaret geçmişi ve notlar
