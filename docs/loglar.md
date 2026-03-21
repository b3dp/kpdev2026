# Modül: Loglar

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Loglar |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Kritik — Yöneticiler kurulduktan sonra, diğer modüllerden önce aktif olmalı |
| Paket | `spatie/laravel-activitylog` |

---

## Roller ve Yetkiler

| Rol | Listele | Görüntüle | Sil |
|---|---|---|---|
| Admin | ✅ | ✅ | ✅ |
| Diğerleri | ❌ | ❌ | ❌ |

> **Not:** Loglar yalnızca Admin tarafından görüntülenebilir. Hiçbir log düzenlenemez.

---

## Veritabanı

Spatie Activity Log paketi kendi tablosunu oluşturur.

### Tablo: `activity_log`

| Alan | Tip | Açıklama |
|---|---|---|
| id | bigIncrements | — |
| log_name | string | Hangi modül: `haberler`, `etkinlikler`, `kisiler` vb. |
| description | string | İşlem açıklaması: `ekledi`, `duzenled`, `yayina_aldi`, `sildi` vb. |
| subject_type | string | İşlem yapılan model: `App\Models\Haber` |
| subject_id | bigInteger | İşlem yapılan kaydın ID'si |
| causer_type | string | İşlemi yapan model: `App\Models\Yonetici` |
| causer_id | bigInteger | İşlemi yapan yöneticinin ID'si |
| properties | json | Ek veriler: eski/yeni değerler, ekstra bilgiler |
| created_at | timestamp | İşlem tarihi/saati |

---

## Loglanan İşlemler ve Mesaj Formatları

Her modülde yapılan işlem şu formatta loglanır:

```
{Yönetici Ad Soyad} {Kayıt Başlığı/Adı} {İşlem Fiili}. {Tarih} - {Saat}
```

### Haberler

| İşlem | Log Mesajı Örneği |
|---|---|
| Ekleme | Barış Yılmaz "Kestanepazarı'nda Konferans" Haberini Ekledi. 19.03.2026 - 14:32 |
| Düzenleme | Mehmet Keskin "Kestanepazarı'nda Konferans" Haberini Düzenledi. 19.03.2026 - 15:10 |
| Yayına Alma | Zübeyir Aktaş "Kestanepazarı'nda Konferans" Haberini Yayına Aldı. 19.03.2026 - 16:00 |
| Zamanlı Yayın | Zübeyir Aktaş "Kestanepazarı'nda Konferans" Haberini 20.03.2026 09:00 İçin Zamanladı. |
| Silme | Barış Yılmaz "Kestanepazarı'nda Konferans" Haberini Sildi. 19.03.2026 - 17:45 |

### Etkinlikler

| İşlem | Log Mesajı Örneği |
|---|---|
| Ekleme | Barış Yılmaz "Ramazan Programı" Etkinliğini Ekledi. |
| Düzenleme | Mehmet Keskin "Ramazan Programı" Etkinliğini Düzenledi. |
| Yayına Alma | Zübeyir Aktaş "Ramazan Programı" Etkinliğini Yayına Aldı. |
| Silme | Barış Yılmaz "Ramazan Programı" Etkinliğini Sildi. |

### Kişiler

| İşlem | Log Mesajı Örneği |
|---|---|
| Ekleme | Mehmet Keskin "Tuğba Gül Cansever" Kişisini Ekledi. |
| Onaylama | Zübeyir Aktaş "Tuğba Gül Cansever" Kişisini Onayladı. |
| Reddetme | Zübeyir Aktaş "Tuğba Gül Cansever" Kişisini Reddetti. |
| Eşleştirme | Zübeyir Aktaş "Tuğba Gül Cansever" Kişisini Mevcut Kayıtla Eşleştirdi. |
| Silme | Mehmet Keskin "Tuğba Gül Cansever" Kişisini Sildi. |

### Kurumlar

| İşlem | Log Mesajı Örneği |
|---|---|
| Ekleme | Mehmet Keskin "Hacı Ahmet Dayhan Kur'an Kursu" Kurumunu Ekledi. |
| Onaylama | Zübeyir Aktaş "Hacı Ahmet Dayhan Kur'an Kursu" Kurumunu Onayladı. |
| Eşleştirme | Zübeyir Aktaş "Hacı Ahmet Dayhan Kur'an Kursu" Kurumunu E-Kayıt ile Eşleştirdi. |

### Bağış

| İşlem | Log Mesajı Örneği |
|---|---|
| Ekleme | Barış Yılmaz "Fitre Bağışı" Kampanyasını Ekledi. |
| Onaylama | Metin Zenginer 1.500₺ Tutarındaki Bağışı Onayladı. |

### Öğrenci E-Kayıt

| İşlem | Log Mesajı Örneği |
|---|---|
| Düzenleme | Mehmet Keskin "Ali Veli" E-Kayıt İşlemini Düzenledi. |
| Silme | Mehmet Keskin "Ali Veli" E-Kayıt Kaydını Sildi. |

### Pazarlama - SMS

| İşlem | Log Mesajı Örneği |
|---|---|
| Gönderme | Metin Zenginer 19.03.2026 Tarihinde 342 Kişiye SMS Gönderdi. |
| Taslak | Metin Zenginer "Ramazan Tebriği" SMS Taslağını Kaydetti. |

### Pazarlama - E-posta

| İşlem | Log Mesajı Örneği |
|---|---|
| Gönderme | Metin Zenginer 19.03.2026 Tarihinde 1.240 Kişiye E-posta Gönderdi. |

### Kurban Yönetimi

| İşlem | Log Mesajı Örneği |
|---|---|
| Ekleme | Barış Yılmaz "Ali Veli" İçin Kurban Kaydı Oluşturdu. |
| Onaylama | Metin Zenginer "Ali Veli" Kurban Ödemesini Onayladı. |

### Mezunlar

| İşlem | Log Mesajı Örneği |
|---|---|
| Ekleme | Mehmet Keskin "Ahmet Yılmaz" Mezun Kaydını Ekledi. |
| Düzenleme | Mehmet Keskin "Ahmet Yılmaz" Mezun Kaydını Düzenledi. |

### Yöneticiler

| İşlem | Log Mesajı Örneği |
|---|---|
| Ekleme | Admin "Barış Yılmaz" Yöneticisini Ekledi. |
| Pasife Alma | Admin "Barış Yılmaz" Yöneticisini Pasife Aldı. |
| Rol Atama | Admin "Barış Yılmaz" Yöneticisine "Editör" Rolü Atadı. |

### Giriş / Güvenlik

| İşlem | Log Mesajı Örneği |
|---|---|
| Başarılı Giriş | Barış Yılmaz Panele Giriş Yaptı. 19.03.2026 - 09:15 |
| Başarısız Giriş | barisy@example.com Adresiyle Başarısız Giriş Denemesi. 19.03.2026 - 09:14 |
| Çıkış | Barış Yılmaz Panelden Çıkış Yaptı. 19.03.2026 - 18:30 |

---

## İş Kuralları

- Hiçbir log kaydı düzenlenemez.
- Log kayıtları yalnızca Admin tarafından silinebilir (toplu temizlik: X günden eski kayıtlar).
- `properties` JSON alanında değişen alanların eski ve yeni değerleri saklanır (Spatie'nin `old` / `attributes` yapısı).
- Giriş/çıkış ve başarısız giriş denemeleri `log_name: guvenlik` altında tutulur.
- AI işlemleri (kişi/kurum tespiti) ayrı `log_name: ai_islem` altında loglanır.

---

## Backend Admin Panel

### Liste Sayfası

Tüm loglar ters kronolojik sırayla listelenir.

**Filtreler:**

| Filtre | Tip | Açıklama |
|---|---|---|
| Modül | Çoklu seçim dropdown | Haberler, Etkinlikler, Kişiler, Kurumlar, Bağış, E-Kayıt, Kurban, Mezunlar, İrşad, Dergiler, Pazarlama-SMS, Pazarlama-Eposta, Yöneticiler, Güvenlik, AI İşlemleri |
| Yönetici | Çoklu seçim dropdown | Tüm yöneticiler listesi — birden fazla seçilebilir |
| İşlem Türü | Çoklu seçim dropdown | Ekledi, Düzenledi, Sildi, Yayına Aldı, Zamanladı, Onayladı, Reddetti, Gönderdi, Giriş Yaptı, Çıkış Yaptı |
| Tarih Aralığı | Tarih aralığı seçici | Başlangıç — Bitiş tarihi. Hazır seçenekler: Bugün, Bu Hafta, Bu Ay, Son 30 Gün, Son 90 Gün, Özel Aralık |
| Kaynak Tip | Tekli seçim | manuel / ai_tespit (Kişiler ve Kurumlar için) |

**Arama:**
- Kayıt adı / başlığı (TNTSearch)
- Yönetici adı

**Liste Kolonları:**

| Kolon | Açıklama |
|---|---|
| Yönetici | Ad soyad |
| İşlem | Eylem metni (örn: "Haberini Yayına Aldı") |
| Kayıt | İşlem yapılan kayıt adı/başlığı |
| Modül | İlgili modül |
| Tarih - Saat | `19.03.2026 - 14:32` formatında |
| Detay | Tıklanınca `properties` JSON gösterilir (eski/yeni değerler) |

**Dışa Aktarma:**
- Filtrelenmiş sonuçlar Excel (xlsx26/raporlar/) veya PDF (pdf26/raporlar/) olarak indirilebilir
- Tarih aralığı zorunlu (max 90 günlük export)

### Güvenlik Logları Sekmesi

- Başarısız giriş denemeleri ayrı sekmede gösterilir
- Kolonlar: IP adresi, denenen e-posta, tarih-saat, deneme sayısı
- Aynı IP'den 5+ başarısız deneme → Admin'e panel bildirimi
- IP bazlı filtreleme ve tarih aralığı mevcut

### AI İşlem Logları Sekmesi

- Tüm Gemini AI işlemleri ayrı sekmede
- Kolonlar: haber başlığı, işlem tipi (imla/özet/meta/kişi/kurum), durum (başarılı/hatalı), süre (ms), tarih
- Hatalı AI işlemleri kırmızı ile işaretlenir
