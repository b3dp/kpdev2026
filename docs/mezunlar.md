# Modül: Mezunlar

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Mezunlar |
| Backend | Evet |
| Frontend | Evet (mezun kayıt formu + kişisel profil — login gerekli) |
| Öncelik | Orta — Üyeler modülünden sonra |
| Bağımlı Modüller | Üyeler, Kurumlar, Öğrenci E-Kayıt, Pazarlama - SMS, Pazarlama - Eposta, Loglar, Roller, Yöneticiler |
| Guard | `uye` — bkz. uyeler.md |
| Güvenlik | bkz. guvenlik.md |

---

## Roller ve Yetkiler

| Rol | Listele | Görüntüle | Düzenle | Onayla | Reddet | Sil |
|---|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Yazar | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Veritabanı

### Tablo: `mezun_profiller`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| uye_id | foreignId | ✅ | — | → `uyeler` |
| kurum_id | foreignId | ❌ | null | Mezun olunan kurum → `kurumlar` |
| kurum_manuel | string(255) | ❌ | null | Kurum listede yoksa serbest metin |
| mezuniyet_yili | smallInteger | ✅ | — | Örn: 2019 |
| sinif_id | foreignId | ❌ | null | E-Kayıt sınıf kaydıyla eşleşme → `ekayit_siniflar` (opsiyonel) |
| hafiz | boolean | ✅ | false | Hafızlık durumu |
| meslek | string(255) | ❌ | null | Mevcut görevi/mesleği |
| gorev_il | string(100) | ❌ | null | Görev yeri ili |
| gorev_ilce | string(100) | ❌ | null | Görev yeri ilçesi |
| ikamet_il | string(100) | ❌ | null | İkamet ili — segment/bildirim için kritik |
| ikamet_ilce | string(100) | ❌ | null | İkamet ilçesi |
| linkedin | string(255) | ❌ | null | LinkedIn profil URL |
| instagram | string(255) | ❌ | null | Instagram kullanıcı adı veya URL |
| twitter | string(255) | ❌ | null | Twitter/X kullanıcı adı veya URL |
| durum | enum | ✅ | beklemede | `beklemede` / `aktif` / `reddedildi` |
| onaylayan_id | foreignId | ❌ | null | Onaylayan yönetici → `yoneticiler` |
| onay_tarihi | timestamp | ❌ | null | — |
| red_notu | text | ❌ | null | Reddedilme sebebi — üyeye gösterilir |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

> **Not:** Bir üyenin yalnızca tek mezun profili olabilir (unique: uye_id).

---

## İş Kuralları

### Kayıt Akışı (Frontend)

```
Mezun kayıt formunu doldurur
  Ad Soyad, Telefon veya E-posta (Üyeler modülünden)
  Mezuniyet bilgileri, ikamet/görev bilgileri, sosyal medya
        │
        ▼
Üyeler modülü — telefon/e-posta eşleşme kontrolü:
  Eşleşme var → mevcut üyeye bağlanır
  Eşleşme yok → yeni üye oluşturulur
        │
        ▼
mezun_profiller tablosuna kaydedilir (durum: beklemede)
Üyeye mezun rozeti eklenmez — onay beklenir
        │
        ▼
Admin/Editöre panel bildirimi: "Yeni mezun kaydı onay bekliyor"
        │
        ▼
Editör onaylarsa:
  durum: aktif
  Üyeye mezun rozeti eklenir (bkz. uyeler.md)
  Üyeye bildirim: "Mezun kaydınız onaylandı"

Editör reddederse:
  durum: reddedildi
  red_notu doldurulur
  Üyeye bildirim: "Mezun kaydınız reddedildi: {red_notu}"
```

### E-Kayıt Geçişi (Yol B — Admin)

```
Admin E-Kayıt listesinde "Mezun Et" butonu
  → Öğrenci bilgileriyle uyeler tablosunda eşleşme aranır
  → Eşleşme var → mevcut üyeye mezun_profiller kaydı oluşturulur
                   durum: aktif (admin onayladı sayılır)
  → Eşleşme yok → yeni üye oluşturulur + mezun_profiller kaydı
  → sinif_id otomatik doldurulur
  → bkz. uyeler.md Mezun Geçiş Akışı
```

### Pazarlama Entegrasyonu

```
SMS/E-posta gönderirken mezun segmentleri:
  İkamet iline göre → "İzmir'de yaşayan mezunlar"
  İlçeye göre → "Karşıyaka'da yaşayan mezunlar"
  Mezuniyet yılına göre → "2015-2020 arası mezunlar"
  Kuruma göre → "Hacı Ahmet Dayhan Kur'an Kursu mezunları"
  Hafızlık durumuna göre → "Hafız mezunlar"
```

### Silme Kuralı

Soft delete. Silindiğinde üyedeki mezun rozeti de kaldırılır.

---

## Backend Admin Panel

### Mezun Listesi

**Üst — İnfografik Kartlar:**

| Kart | İçerik |
|---|---|
| Toplam Mezun | Aktif mezun sayısı |
| Bekleyen Onay | durum: beklemede |
| Hafız | hafiz: true |

**Filtreler:**
- Durum (çoklu): Beklemede / Aktif / Reddedildi
- Mezuniyet yılı aralığı
- Mezun olunan kurum
- İkamet ili/ilçesi
- Görev ili/ilçesi
- Hafızlık durumu

**Liste Kolonları:**

| Kolon | Açıklama |
|---|---|
| Ad Soyad | Üyeden çekilir |
| Mezuniyet Yılı | — |
| Mezun Olunan Kurum | kurum_id varsa kurum adı, yoksa kurum_manuel |
| Hafız | ✅ / — |
| İkamet İli | — |
| Durum | Renkli rozet |
| Kayıt Tarihi | — |
| İşlemler | Detaya Git / Onayla / Reddet |

**Excel İndirme:**
Filtrelenmiş mezun listesi Excel olarak indirilebilir.
Kolonlar: Ad Soyad, Telefon, E-posta, Kurum, Mezuniyet Yılı, Hafız, Meslek, Görev İl/İlçe, İkamet İl/İlçe, LinkedIn, Instagram, Twitter, Kayıt Tarihi, Durum

---

### Mezun Detay Sayfası

#### Card 1 — Mezuniyet Bilgileri
- Mezun Olunan Kurum (veya manuel giriş)
- Mezuniyet Yılı
- Hafızlık Durumu
- E-Kayıt kaydıyla eşleşme (varsa link)

#### Card 2 — Mevcut Durum
- Meslek / Görev
- Görev İli / İlçesi
- İkamet İli / İlçesi

#### Card 3 — İletişim ve Sosyal Medya
- Telefon (Üyeden)
- E-posta (Üyeden)
- LinkedIn, Instagram, Twitter linkleri
- **İletişim Butonları:** SMS | WhatsApp (`wa.me`) | Ara | E-posta

#### Card 4 — Onay Durumu
- Durum rozeti
- Onaylayan yönetici + tarih
- Red notu (reddedildiyse)
- **Onayla / Reddet** butonları (beklemedeyse)
- Red modalında: red notu yazma alanı

---

## Frontend

### Sayfalar

| Sayfa | URL | Açıklama |
|---|---|---|
| Mezun Kayıt | `/mezunlar/kayit` | Herkese açık kayıt formu |
| Mezun Girişi | `/mezunlar/giris` | Üye guard girişi |
| Profilim | `/profilim` | Giriş yapan üyenin profili — bkz. uyeler.md |

### Mezun Kayıt Formu Alanları

**Kişisel Bilgiler** (Üyeler modülünden):
- Ad Soyad
- Telefon
- E-posta

**Mezuniyet Bilgileri:**
- Mezun olunan kurum (dropdown — kurumsal sayfaları olan kurumlar)
- Kurum listede yok → "Manuel gir" seçeneği açılır
- Mezuniyet yılı
- Hafız mısınız? (Evet / Hayır)

**Mevcut Durum:**
- Meslek / Görev (opsiyonel)
- Görev İli / İlçesi (opsiyonel)
- İkamet İli / İlçesi (opsiyonel)

**Sosyal Medya** (opsiyonel):
- LinkedIn
- Instagram
- Twitter/X

### Güvenlik
- Honeypot + reCAPTCHA v3 + rate limiting — bkz. guvenlik.md

---

## Belirsizlikler / Açık Kararlar

- [x] İl/İlçe: sabit dropdown (Türkiye il/ilçe listesi)
- [x] Reddedilen mezun tekrar başvurabilir
- [ ] Frontend detayı sonra ele alınacak
