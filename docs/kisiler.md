# Modül: Kişiler

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Kişiler |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Çok Yüksek — en fazla bağımlılık alan modül (13 modül) |
| Bağımlı Modüller | Haberler, Etkinlikler, Kurumsal Sayfalar, Bağış, Öğrenci E-Kayıt, Kurumlar, Pazarlama - SMS, Pazarlama - Eposta, Dergiler, Mezunlar, İrşad, Kurban Yönetimi, Loglar, Roller, Yöneticiler |

---

## Roller ve Yetkiler

| Rol | Yeni | Düzenle | Onayla | Reddet | Sil | Listele |
|---|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Yazar | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

> **Not:** Yazarlar kişi kaydı oluşturamaz. Kişiler yalnızca AI tespiti veya yetkili kullanıcılar tarafından eklenir.

---

## Veritabanı

### Tablo: `kisiler`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| ad_soyad | string(255) | ✅ | — | Kişinin tam adı |
| unvan | string(255) | ❌ | null | Görevi veya unvanı (örn: Kurs Yöneticisi, İmam, Hafız, Müftü) |
| kurum_id | foreignId | ❌ | null | Bağlı olduğu kurum → `kurumlar` tablosu |
| kurum_aciklama | string(500) | ❌ | null | Kurumlar tablosunda eşleşme yoksa serbest metin olarak saklanır |
| telefon | string(20) | ❌ | null | — |
| eposta | string(255) | ❌ | null | — |
| durum | enum | ✅ | beklemede | `beklemede` / `aktif` / `reddedildi` |
| kaynak | enum | ✅ | manuel | `manuel` / `ai_tespit` |
| onaylayan_id | foreignId | ❌ | null | Onaylayan yönetici → `yoneticiler` |
| onay_tarihi | timestamp | ❌ | null | — |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

---

### Tablo: `kisi_ai_teklifleri`

AI'ın metinden tespit ettiği her kişi adayı buraya düşer. Onaylanana kadar `kisiler` tablosuna geçmez.

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | — |
| kaynak_tip | string | ✅ | — | `haber` / `etkinlik` vb. |
| kaynak_id | bigInteger | ✅ | — | İlgili modülün ID'si (polymorphic) |
| ham_metin | string(500) | ✅ | — | AI'ın metinden ayıkladığı ham ifade |
| tespit_ad_soyad | string(255) | ✅ | — | AI'ın tespit ettiği ad soyad |
| tespit_unvan | string(255) | ❌ | null | AI'ın tespit ettiği unvan/görev |
| tespit_kurum | string(500) | ❌ | null | AI'ın tespit ettiği kurum adı (serbest metin) |
| benzer_kisi_id | foreignId | ❌ | null | Benzer mevcut kayıt varsa → `kisiler` tablosu |
| benzerlik_skoru | decimal(5,2) | ❌ | null | Levenshtein ile hesaplanan 0-100 arası benzerlik yüzdesi |
| durum | enum | ✅ | beklemede | `beklemede` / `yeni_kayit` / `eslesti` / `kaale_alindi` |
| islemi_yapan_id | foreignId | ❌ | null | Kararı veren yönetici → `yoneticiler` |
| islem_tarihi | timestamp | ❌ | null | — |
| created_at | timestamp | ✅ | — | — |

---

## İş Kuralları

### AI Tespit Akışı

```
Haber/Etkinlik metni AI'a gönderilir
        │
        ▼
AI metinden kişi adı, unvan, kurum ayıklar
(Türkçe dini/idari unvanlar özel olarak işlenir: İmam, Hafız, Müftü,
 Hoca, Vaiz, Müezzin, Kayyum, Kurs Yöneticisi vb.)
        │
        ▼
Her tespit → kisi_ai_teklifleri tablosuna eklenir
        │
        ▼
Levenshtein mesafesi ile kisiler tablosunda benzerlik kontrolü yapılır
        │
        ├─── Tam eşleşme (ad + unvan + kurum hepsi aynı)
        │         → Yeni teklif oluşturulmaz, mevcut kayıt doğrudan bağlanır
        │
        ├─── Yüksek benzerlik (skor ≥ 80)
        │         → benzer_kisi_id doldurulur, durum: "beklemede"
        │         → Panel içi bildirim gönderilir
        │         → Editöre üç seçenek sunulur:
        │               A) Yeni Kayıt Oluştur
        │               B) Mevcut Kayıtla Eşleştir
        │               C) Kaale Alma
        │
        └─── Eşleşme yok veya düşük benzerlik (skor < 80)
                  → durum: "beklemede"
                  → Panel içi bildirim: "Yeni kişi onayı bekliyor"
                  → Editör onaylarsa kisiler tablosuna aktarılır
```

### Benzerlik Senaryosu (Örnek)

```
Mevcut kayıt → Barış Yılmaz | Müdür | Kestanepazarı Öğrenci Yetiştirme Derneği
AI tespiti  → Barış Yılmaz | Basın Müdürü | Kestanepazarı

Levenshtein skoru: 85 → "beklemede" düşer
Panel bildirimi:
  "Barış Yılmaz adında mevcut bir kayıt var. Ne yapmak istersiniz?"
  [A] Yeni Kayıt Oluştur
  [B] Mevcut Kayıtla Eşleştir
  [C] Kaale Alma
```

### Silme Kuralı

Kişi silindiğinde `deleted_at` doldurulur (soft delete). Bağlı olduğu **tüm modüllerdeki ilişkili kayıtlar da cascade delete ile silinir** — pivot tablolar dahil. Hiçbir modülde artık görünmez.

### Kurum Eşleştirme Kuralı

AI bir kurum adı tespit ettiğinde `kurumlar` tablosunda Levenshtein ile benzerlik kontrolü yapılır. Eşleşirse `kurum_id` doldurulur. Eşleşmezse `kurum_aciklama` alanına serbest metin kaydedilir ve Kurumlar modülüne ayrıca onay teklifi gönderilir.

---

## AI Entegrasyonu — Google Cloud

| Özellik | Detay |
|---|---|
| Tetiklenme | Haber/Etkinlik kaydedildiğinde, Queue/Job ile asenkron |
| Görev | Metinden kişi adı, unvan, kurum ayıklama |
| Model | Gemini Pro |
| Çıktı Formatı | JSON: `[{ad_soyad, unvan, kurum, ham_metin}]` |
| Unvan Sözlüğü | Türkçe dini ve idari unvanlar prompt'a özel olarak eklenir |
| Hata Durumu | AI başarısız olursa teklif oluşturulmaz, log tutulur, editör manuel ekleyebilir |

**Örnek AI prompt yapısı:**
```
Aşağıdaki metinden kişi isimlerini, unvanlarını ve kurumlarını JSON formatında çıkar.
Sadece gerçek kişileri al, kurum veya yer isimlerini kişi olarak alma.
Türkçe dini ve idari unvanlara özellikle dikkat et:
İmam, Hafız, Müftü, Hoca, Vaiz, Müezzin, Kayyum, Kurs Yöneticisi,
Müdür, Başkan, Yönetici, Sekreter vb.
Çıktı formatı: [{ad_soyad, unvan, kurum, ham_metin}]

Metin: "..."
```

---

## İlişkiler (Diğer Modüllerle)

| Modül | İlişki Tipi | Açıklama |
|---|---|---|
| Kurumlar | belongsTo | Bir kişi tek bir kuruma bağlıdır |
| Haberler | belongsToMany (pivot) | Haberde geçen kişiler, onay mekanizmalı |
| Etkinlikler | belongsToMany (pivot) | Etkinlikte geçen kişiler |
| Mezunlar | hasOne | Kişi aynı zamanda mezun olabilir |
| İrşad | belongsToMany | İrşad kayıtlarındaki görevliler |
| Bağış | hasMany | Bağış yapan kişiler |
| Kurban Yönetimi | hasMany | Kurban kaydı olan kişiler |
| Pazarlama - SMS | belongsToMany | SMS listelerine dahil edilebilir |
| Pazarlama - Eposta | belongsToMany | E-posta listelerine dahil edilebilir |

---

## Backend Admin Panel

### Liste Sayfası

- Tüm kişiler listelenir
- Filtre: `durum` (beklemede / aktif / reddedildi), `kaynak` (manuel / ai_tespit), kurum
- Arama: ad soyad, unvan, kurum adı
- Bekleyen AI teklifleri ayrı sekmede, sayısı badge ile gösterilir

### Detay / Düzenleme Sayfası

- Ad soyad, unvan, kurum, telefon, e-posta düzenlenebilir
- AI teklifi ise: ham metin ve benzer kayıt yan yana gösterilir
- Onay / Red / Eşleştir butonları

### AI Teklifleri Sayfası

- Bekleyen tüm teklifler listelenir
- Her teklif için: ham metin, tespit edilen bilgiler, benzer kayıt (varsa), Levenshtein skoru
- Toplu onay / red işlemi yapılabilir
