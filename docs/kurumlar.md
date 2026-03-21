# Modül: Kurumlar

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Kurumlar |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Yüksek — 10 modül tarafından kullanılıyor |
| Bağımlı Modüller | Haberler, Etkinlikler, Kurumsal Sayfalar, Öğrenci E-Kayıt, Kişiler, Dergiler, İrşad, Loglar, Roller, Yöneticiler |

---

## Roller ve Yetkiler

| Rol | Yeni | Düzenle | Onayla | Reddet | Sil | Listele |
|---|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Yazar | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

> **Not:** Yazarlar kurum kaydı oluşturamaz. Kurumlar yalnızca AI tespiti veya yetkili kullanıcılar tarafından eklenir.

---

## Veritabanı

### Tablo: `kurumlar`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| ad | string(500) | ✅ | — | Kurumun tam adı |
| tur | enum | ✅ | — | `egitim` / `dini` / `sivil_toplum` / `kamu` / `diger` |
| e_kayit_id | foreignId | ❌ | null | Eşleştiği E-Kayıt kaydı → `e_kayitlar` tablosu |
| kurumsal_sayfa_id | foreignId | ❌ | null | Eşleştiği Kurumsal Sayfa → `kurumsal_sayfalar` tablosu |
| durum | enum | ✅ | beklemede | `beklemede` / `aktif` / `reddedildi` |
| kaynak | enum | ✅ | manuel | `manuel` / `ai_tespit` |
| onaylayan_id | foreignId | ❌ | null | Onaylayan yönetici → `yoneticiler` |
| onay_tarihi | timestamp | ❌ | null | — |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

---

### Tablo: `kurum_ai_teklifleri`

AI'ın metinden tespit ettiği her kurum adayı buraya düşer. Onaylanana kadar `kurumlar` tablosuna geçmez.

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | — |
| kaynak_tip | string | ✅ | — | `haber` / `etkinlik` vb. |
| kaynak_id | bigInteger | ✅ | — | İlgili modülün ID'si (polymorphic) |
| ham_metin | string(500) | ✅ | — | AI'ın metinden ayıkladığı ham ifade |
| tespit_ad | string(500) | ✅ | — | AI'ın tespit ettiği kurum adı |
| tespit_tur | string(100) | ❌ | null | AI'ın tespit ettiği kurum türü |
| benzer_kurum_id | foreignId | ❌ | null | Benzer mevcut kayıt varsa → `kurumlar` tablosu |
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
AI metinden kurum adını ve türünü ayıklar
(Kurum türleri: Kur'an Kursu → egitim, Cami → dini,
 Dernek/Vakıf → sivil_toplum, Belediye/Müftülük → kamu vb.)
        │
        ▼
Her tespit → kurum_ai_teklifleri tablosuna eklenir
        │
        ▼
Levenshtein mesafesi ile kurumlar tablosunda benzerlik kontrolü yapılır
        │
        ├─── Tam eşleşme (ad + tür aynı)
        │         → Yeni teklif oluşturulmaz, mevcut kayıt doğrudan bağlanır
        │
        ├─── Yüksek benzerlik (skor ≥ 80)
        │         → benzer_kurum_id doldurulur, durum: "beklemede"
        │         → Panel içi bildirim gönderilir
        │         → Editöre üç seçenek sunulur:
        │               A) Yeni Kayıt Oluştur
        │               B) Mevcut Kayıtla Eşleştir
        │               C) Kaale Alma
        │
        └─── Eşleşme yok veya düşük benzerlik (skor < 80)
                  → durum: "beklemede"
                  → Panel içi bildirim: "Yeni kurum onayı bekliyor"
                  → Editör onaylarsa kurumlar tablosuna aktarılır
```

### Benzerlik Senaryosu (Örnek)

```
Mevcut kayıt → Kestanepazarı Hacı Ahmet Dayhan 7-10 Yaş Kur'an Kursu | egitim
AI tespiti  → 7-10 Yaş Kur'an Kursu | egitim

Levenshtein skoru: 82 → "beklemede" düşer
Panel bildirimi:
  "7-10 Yaş Kur'an Kursu adında mevcut bir kayıt olabilir. Ne yapmak istersiniz?"
  [A] Yeni Kayıt Oluştur
  [B] Mevcut Kayıtla Eşleştir
  [C] Kaale Alma
```

### E-Kayıt Eşleştirme Kuralı

Bir kurum E-Kayıt sistemiyle eşleştirilmek istendiğinde `e_kayit_id` alanı doldurulur. Bu sayede E-Kayıt modülü ilgili kurumu otomatik tanır. Bir kurumun en fazla bir E-Kayıt kaydıyla eşleşebilir.

### Kurumsal Sayfa Eşleştirme Kuralı

Bir kurumun frontend'de kurumsal sayfası varsa `kurumsal_sayfa_id` alanı doldurulur. Bu sayede kurumun bilgileri kurumsal sayfa üzerinden yönetilir. Bir kurumun en fazla bir kurumsal sayfası olabilir.

### Silme Kuralı

Kurum silindiğinde `deleted_at` doldurulur (soft delete). Bağlı olduğu **tüm modüllerdeki ilişkili kayıtlar cascade delete ile silinir** — `kisi_kurum`, pivot tablolar dahil. `e_kayit_id` ve `kurumsal_sayfa_id` bağlantıları da temizlenir.

---

## AI Entegrasyonu — Google Cloud

| Özellik | Detay |
|---|---|
| Tetiklenme | Haber/Etkinlik kaydedildiğinde, Queue/Job ile asenkron (Kişiler tespiti ile aynı job içinde) |
| Görev | Metinden kurum adı ve türü ayıklama |
| Model | Gemini Pro |
| Çıktı Formatı | JSON: `[{ad, tur, ham_metin}]` |
| Tür Sözlüğü | Türkçe kurum türleri prompt'a özel olarak eklenir |
| Hata Durumu | AI başarısız olursa teklif oluşturulmaz, log tutulur, editör manuel ekleyebilir |

**Örnek AI prompt yapısı:**
```
Aşağıdaki metinden kurum isimlerini ve türlerini JSON formatında çıkar.
Sadece gerçek kurumları al, kişi isimlerini kurum olarak alma.
Kurum türlerini şu kategorilerden birine ata:
- egitim: Kur'an Kursu, Okul, Dershane vb.
- dini: Cami, Mescit, Müftülük vb.
- sivil_toplum: Dernek, Vakıf vb.
- kamu: Belediye, Kaymakamlık, Devlet kurumu vb.
- diger: Yukarıdakilere uymayan kurumlar
Çıktı formatı: [{ad, tur, ham_metin}]

Metin: "..."
```

---

## İlişkiler (Diğer Modüllerle)

| Modül | İlişki Tipi | Açıklama |
|---|---|---|
| Kişiler | hasMany | Kuruma bağlı kişiler |
| Haberler | belongsToMany (pivot) | Haberde geçen kurumlar, onay mekanizmalı |
| Etkinlikler | belongsToMany (pivot) | Etkinlikte geçen kurumlar |
| Öğrenci E-Kayıt | hasOne | Kuruma bağlı E-Kayıt sistemi (e_kayit_id) |
| Kurumsal Sayfalar | hasOne | Kuruma ait frontend kurumsal sayfası (kurumsal_sayfa_id) |
| İrşad | belongsToMany | İrşad yapılan kurumlar |
| Dergiler | belongsToMany | Dergiyle ilişkili kurumlar |

---

## Backend Admin Panel

### Liste Sayfası

- Tüm kurumlar listelenir
- Filtre: `durum` (beklemede / aktif / reddedildi), `kaynak` (manuel / ai_tespit), `tur`
- Arama: kurum adı
- E-Kayıt ve Kurumsal Sayfa eşleşmesi olan kurumlar rozet ile gösterilir
- Bekleyen AI teklifleri ayrı sekmede, sayısı badge ile gösterilir

### Detay / Düzenleme Sayfası

- Ad, tür düzenlenebilir
- E-Kayıt eşleştirme: dropdown ile mevcut E-Kayıt kayıtlarından seçilir
- Kurumsal Sayfa eşleştirme: dropdown ile mevcut Kurumsal Sayfalardan seçilir
- AI teklifi ise: ham metin ve benzer kayıt yan yana gösterilir
- Bağlı kişiler listesi görüntülenir
- Onay / Red / Eşleştir butonları

### AI Teklifleri Sayfası

- Bekleyen tüm teklifler listelenir
- Her teklif için: ham metin, tespit edilen bilgiler, benzer kayıt (varsa), Levenshtein skoru
- Toplu onay / red işlemi yapılabilir

---

## Belirsizlikler / Açık Kararlar

- [ ] Kurum türleri sabit mi kalacak yoksa admin tarafından genişletilebilir mi?
- [ ] Bir kurum hem E-Kayıt hem Kurumsal Sayfa ile aynı anda eşleşebilir mi? (Şu an evet varsayıldı)
- [ ] Kurumların birden fazla şubesi olacak mı? (Örn: farklı yaş grupları aynı Kur'an kursu)
