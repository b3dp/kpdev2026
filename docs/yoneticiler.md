# Modül: Yöneticiler

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Yöneticiler |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Kritik — Roller modülünden hemen sonra kurulmalı |
| Guard | `admin` (frontend Mezunlar guard'ından ayrı) |

---

## Roller ve Yetkiler

| Rol | Yeni | Düzenle | Sil | Listele | Rol Ata |
|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Diğerleri | ❌ | ❌ | ❌ | ❌ | ❌ |

> **Not:** Yönetici işlemleri yalnızca Admin tarafından yapılır. Hiçbir yönetici kendi hesabını silemez.

---

## Veritabanı

### Tablo: `yoneticiler`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| ad_soyad | string(255) | ✅ | — | — |
| eposta | string(255) | ✅ | — | Giriş için kullanılır, unique |
| sifre | string | ✅ | — | Bcrypt hash |
| telefon | string(20) | ❌ | null | — |
| aktif | boolean | ✅ | true | Pasif yapılınca girişi engellenir |
| son_giris | timestamp | ❌ | null | Her başarılı girişte güncellenir |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

> **Not:** Roller Spatie'nin `model_has_roles` tablosu üzerinden atanır. `yoneticiler` tablosuna rol kolonu eklenmez.

---

## İş Kuralları

### Giriş (Auth)

- Guard: `admin`
- Giriş: e-posta + şifre
- `aktif = false` olan yöneticiler giriş yapamaz
- Başarısız giriş denemeleri loglanır
- Şifre sıfırlama: e-posta ile token gönderilir

### Rol Atama

Bir yöneticiye birden fazla rol atanabilir. Roller Yöneticiler modülünden atanır. İzinler Roller modülünden yönetilir.

```
Yönetici oluştur → Rol(ler) ata → Kaydet
```

### Pasif Yapma / Silme

Yönetici silinmez, `aktif = false` yapılır. Soft delete yalnızca log bütünlüğü için kullanılır — silinen yöneticinin geçmiş log kayıtlarında adı görünmeye devam eder.

### Kendi Hesabını Koruma

Hiçbir yönetici kendi hesabını pasife alamaz, silemez veya rolünü kaldıramaz. Sistemde her zaman en az bir aktif Admin bulunmak zorundadır.

---

## Backend Admin Panel

### Liste Sayfası
- Tüm yöneticiler listelenir: ad soyad, e-posta, roller, son giriş, durum
- Filtre: aktif / pasif, role göre

### Yeni Yönetici / Düzenleme
- Ad soyad, e-posta, telefon, şifre (yeni eklemede zorunlu)
- Rol atama: mevcut rollerden çoklu seçim
- Aktif / Pasif toggle

### Yönetici Detay
- Atanmış roller
- Son giriş tarihi
- Son işlemleri (Loglar modülünden çekilerek gösterilir)
