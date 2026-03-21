# Modül: Üyeler

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Üyeler |
| Backend | Evet |
| Frontend | Evet (kayıt, giriş, profil — popup tabanlı) |
| Öncelik | Yüksek — Bağış, E-Kayıt, Mezunlar modüllerinden önce kurulmalı |
| Guard | `uye` (admin guard'ından tamamen ayrı) |
| Bağımlı Modüller | Bağış, Öğrenci E-Kayıt, Mezunlar, Kişiler, Pazarlama - SMS, Pazarlama - Eposta |

---

## Rozetler (Üye Tipleri)

Bir üye aynı anda birden fazla rozete sahip olabilir.

| Rozet | Açıklama | Nasıl Eklenir |
|---|---|---|
| Bağışçı | Bağış yapmış üye | Bağış tamamlandığında otomatik |
| Veli | E-Kayıt yaptırmış üye | E-Kayıt tamamlandığında otomatik |
| Mezun | Mezun olan üye | Manuel (admin) veya mezun formu ile |

---

## Roller ve Yetkiler

| Rol | Listele | Görüntüle | Düzenle | Pasife Al | Rozet Yönet |
|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ✅ |
| Diğerleri | ❌ | ❌ | ❌ | ❌ | ❌ |

> **Not:** Üyeler modülü frontend kullanıcılarına aittir. Yöneticiler sadece panel üzerinden üye kayıtlarını görüntüler ve yönetir. Üyeler kendi hesaplarını frontend'den yönetir.

---

## Veritabanı

### Tablo: `uyeler`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| ad_soyad | string(255) | ✅ | — | — |
| telefon | string(20) | ❌ | null | Kimlik doğrulama, unique |
| eposta | string(255) | ❌ | null | Kimlik doğrulama, unique |
| sifre | string | ❌ | null | Bcrypt hash, opsiyonel |
| sifre_degistir | boolean | ✅ | false | İlk girişte şifre değiştirme zorunluluğu |
| telefon_dogrulandi | boolean | ✅ | false | SMS OTP ile doğrulandı mı |
| eposta_dogrulandi | boolean | ✅ | false | E-posta linki ile doğrulandı mı |
| aktif | boolean | ✅ | true | Pasif yapılınca girişi engellenir |
| son_giris | timestamp | ❌ | null | Her başarılı girişte güncellenir |
| kisi_id | foreignId | ❌ | null | Kişiler tablosuyla eşleşme → `kisiler` |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

> **Not:** Telefon veya e-postadan en az biri zorunludur. İkisi de null olamaz. Bu kural uygulama katmanında kontrol edilir.

---

### Tablo: `uye_rozetler`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | — |
| uye_id | foreignId | ✅ | — | → `uyeler` |
| rozet | enum | ✅ | — | `bagisci` / `veli` / `mezun` |
| kaynak_tip | string | ❌ | null | `bagis` / `e_kayit` / `mezun_form` / `manuel` |
| kaynak_id | bigInteger | ❌ | null | İlgili kaydın ID'si (polymorphic) |
| eklenme_tarihi | timestamp | ✅ | — | — |

> **Not:** Aynı üyeye aynı rozet birden fazla eklenemez (unique: uye_id + rozet).

---

### Tablo: `uye_oturumlar` (Trusted Devices)

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| uye_id | foreignId | ✅ | → `uyeler` |
| token | string(64) | ✅ | Şifreli cihaz tanıma token'ı, unique |
| cihaz_bilgisi | string(500) | ❌ | User-agent özeti |
| ip_adresi | string(45) | ❌ | — |
| son_kullanim | timestamp | ✅ | Her kullanımda güncellenir |
| gecerlilik | timestamp | ✅ | Token geçerlilik süresi (72 saat) |
| created_at | timestamp | ✅ | — |

---

### Tablo: `uye_otp`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| uye_id | foreignId | ✅ | → `uyeler` |
| kanal | enum | ✅ | `sms` / `eposta` |
| kod | string(6) | ✅ | 6 haneli OTP kodu |
| kullanildi | boolean | ✅ | false |
| gecerlilik | timestamp | ✅ | 10 dakika geçerli |
| created_at | timestamp | ✅ | — |

---

## İş Kuralları

### Üye Tanıma Akışı (Tüm Modüller İçin Ortak)

```
Kullanıcı telefon veya e-posta girer
        │
        ▼
Sistemde kayıtlı mı?
        │
        ├── HAYIR → Bilgileri al, işlemi tamamla
        │           Arka planda üye kaydı oluştur
        │           İlgili rozet otomatik eklenir
        │
        └── EVET → Şifresi var mı?
                        │
                        ├── EVET → Popup: şifre iste
                        │          Doğrulandı → işlem o üyenin üzerine işlenir
                        │
                        └── HAYIR → Popup: OTP gönder (SMS veya e-posta)
                                    Doğrulandı → işlem o üyenin üzerine işlenir
                                    "Şifre oluşturmak ister misiniz?" seçeneği sun
```

### E-Kayıt Özel Akışı

```
Veli telefon/e-posta girer
        │
        └── Kayıtlı → Bildirim yok, OTP yok
                      Direkt o üyenin üzerine e-kayıt işlenir
                      (Sürtünmesiz akış — veli zaten tanınıyor)
```

### Mezun Geçiş Akışı — Yol B (Admin)

```
Admin E-Kayıt listesinde "Mezun Et" butonuna basar
        │
        ▼
Öğrencinin adı/telefonu/e-postası ile uyeler tablosunda arama
        │
        ├── Eşleşme var → Mevcut üyeye mezun rozeti eklenir
        │                 e_kayitlar.mezun_uye_id doldurulur
        │
        └── Eşleşme yok → Yeni üye kaydı oluşturulur
                          Mezun rozeti eklenir
                          Üyeye "Hesabınızı aktive edin" SMS/e-posta
```

### Mezun Geçiş Akışı — Yol C (Mezun Formu)

```
Mezun frontend formunu doldurur (ad, telefon/e-posta, mezuniyet yılı vb.)
        │
        ▼
Telefon/e-posta ile uyeler tablosunda arama
        │
        ├── Eşleşme var → Mevcut üyeye mezun rozeti eklenir
        │                 E-Kayıt geçmişi varsa otomatik bağlanır
        │
        └── Eşleşme yok → Yeni üye oluşturulur + mezun rozeti
```

### Kişiler Modülü ile Eşleşme

```
Yeni üye kaydı oluşturulduğunda
        │
        ▼
Kişiler tablosunda Levenshtein ile isim araması
        │
        ├── Benzer kayıt (skor ≥ 80) → Admin'e panel bildirimi:
        │   "Ahmet Yılmaz adında bir Kişi kaydı var. Eşleştirelim mi?"
        │   [Evet] → kisiler.kisi_id = uye.id
        │   [Hayır] → geç
        │
        └── Eşleşme yok → Bildirim gönderilmez
```

### Haberlerde Mezun Rozeti Gösterimi

```
Haberin altında kişi listesi oluşturulurken:
        │
        ▼
haber_kisiler pivot → kisiler tablosu → kisi_id dolu mu?
        │
        ├── Dolu → uyeler tablosuna bak
        │          uye_rozetler'de mezun rozeti var mı?
        │          EVET → Adın yanında "Mezun" rozeti göster
        │
        └── Boş → Sadece ad göster, rozet yok
```

### Şifre / Oturum Kuralları

- Şifre opsiyonel — üye şifresiz de işlem yapabilir (OTP yeterli)
- Şifre varsa: 72 saatlik trusted device token yazılır
- Şifre sistem tarafından oluşturulduysa `sifre_degistir = true` — ilk girişte değiştirmesi istenir
- OTP: 6 haneli, 10 dakika geçerli, tek kullanımlık

### Silme Kuralı

Üye silindiğinde soft delete. Bağlı bağış, e-kayıt, mezun kayıtları **korunur** — finansal ve idari kayıtlar silinmez. Silmek yerine `aktif = false` tercih edilir.

---

## Frontend (Popup Tabanlı)

Tüm üye işlemleri sayfa yönlendirmesi olmadan popup içinde tamamlanır.

| Tetikleyici | Popup İçeriği |
|---|---|
| Telefon/e-posta tanındı | "Hoş geldin [Ad]! Devam etmek için şifrenizi girin" |
| Şifre yok | "Telefonunuza/e-postanıza 6 haneli kod gönderdik" |
| OTP doğrulandı, şifre yok | "Bir şifre oluşturmak ister misiniz?" |
| `sifre_degistir = true` | "Güvenliğiniz için şifrenizi güncelleyin" |
| Mezun formunda eşleşme | "Bu bilgiler kayıtlı. Hesabınıza ekleyelim mi?" |

### Profil Sayfası `/profilim`

- Rozetler (Bağışçı / Veli / Mezun)
- Bağış geçmişi
- E-Kayıt kayıtları
- Şifre değiştirme

---

## Backend Admin Panel

### Liste Sayfası

- Tüm üyeler: ad soyad, telefon, e-posta, rozetler, kayıt tarihi, son giriş
- Filtre: rozet türü, aktif/pasif, tarih aralığı
- Arama: ad soyad, telefon, e-posta
- Kişiler modülüyle eşleşmemiş üyeler işaretlenir

### Üye Detay Sayfası

- Temel bilgiler düzenlenebilir
- Rozetler görüntülenir, manuel rozet eklenebilir/kaldırılabilir
- Bağış, e-kayıt, mezun bilgileri ayrı sekmeler
- Kişiler modülüyle eşleştirme butonu
- Aktif oturumlar (trusted devices) — admin sonlandırabilir

---

## İlişkiler

| Modül | İlişki Tipi | Açıklama |
|---|---|---|
| Kişiler | hasOne (opsiyonel) | `kisiler.kisi_id` üzerinden eşleşme |
| Bağış | hasMany | Üyenin yaptığı bağışlar |
| Öğrenci E-Kayıt | hasMany | Üyenin veli olduğu kayıtlar |
| Mezunlar | hasOne | Üyenin mezun bilgileri |
| Pazarlama - SMS | belongsToMany | SMS listelerine dahil |
| Pazarlama - Eposta | belongsToMany | E-posta listelerine dahil |

---

## Belirsizlikler / Açık Kararlar

- [ ] Profil sayfası (`/profilim`) bu aşamada yapılacak mı, sonraya mı bırakılacak?
- [ ] OTP kanalı: SMS mi, e-posta mı, ikisi birden mi? (İkisi birden önerilir — kullanıcı seçer)
- [ ] Bağış yaparken sessiz üye oluşturulduğunda "hesabını aktive et" mesajı otomatik gitsin mi, yoksa kullanıcı talep etmedikçe beklesin mi?
- [ ] Mezun rozeti admin tarafından kaldırılabilir mi?

---

## Loglanan İşlemler

Tüm üye yönetim işlemleri `Loglar` modülüne kaydedilir (bkz. loglar.md).

| İşlem | Log Mesajı Örneği |
|---|---|
| Üye pasife alma | Admin "Ahmet Yılmaz" Üyesini Pasife Aldı. |
| Rozet ekleme | Admin "Ahmet Yılmaz" Üyesine "Mezun" Rozeti Ekledi. |
| Rozet kaldırma | Admin "Ahmet Yılmaz" Üyesinden "Mezun" Rozeti Kaldırdı. |
| Kişilerle eşleştirme | Admin "Ahmet Yılmaz" Üyesini Kişiler Modülüyle Eşleştirdi. |
| Oturum sonlandırma | Admin "Ahmet Yılmaz" Üyesinin Oturumunu Sonlandırdı. |
| Abonelik değişikliği | "Ahmet Yılmaz" SMS Aboneliğinden Çıktı. |
