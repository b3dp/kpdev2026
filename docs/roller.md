# Modül: Roller

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Roller |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Kritik — tüm modüllerden önce kurulmalı |
| Paket | `spatie/laravel-permission` |

---

## Roller ve Yetkiler

| Rol | Yeni | Düzenle | Sil | Listele | İzin Ata |
|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Diğerleri | ❌ | ❌ | ❌ | ❌ | ❌ |

> **Not:** Roller ve izinler yalnızca Admin tarafından yönetilir.

---

## Yapı

Spatie Laravel Permission paketi iki temel kavram üzerine kuruludur: **Rol** ve **İzin (Permission)**.

Her modül için 5 ayrı izin tanımlanır:

```
{modul}.listele
{modul}.goruntule
{modul}.duzenle
{modul}.sil
{modul}.kaydet
```

Modüle özel ek izinler:

```
haberler.yayinla
haberler.zamanli_yayinla
bagis.onayla
kurban.onayla
kisiler.onayla
kurumlar.onayla
pazarlama_sms.gonder
pazarlama_eposta.gonder
```

---

## Veritabanı

Spatie paketi kendi tablolarını oluşturur. Ek tablo gerekmez.

| Tablo | Açıklama |
|---|---|
| `roles` | Rol tanımları (Admin, Editör, Yazar vb.) |
| `permissions` | İzin tanımları (`haberler.listele` vb.) |
| `role_has_permissions` | Rol → İzin pivot |
| `model_has_roles` | Yönetici → Rol pivot |
| `model_has_permissions` | Yöneticiye direkt atanan özel izinler |

---

## Varsayılan Roller ve İzinleri

### Admin
Tüm modüllerde tüm izinler. Kısıtlanamaz.

### Editör
| Modül | listele | goruntule | duzenle | kaydet | sil | yayinla |
|---|---|---|---|---|---|---|
| Haberler | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Etkinlikler | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Kurumsal Sayfalar | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Kişiler | ✅ | ✅ | ✅ | ✅ | ❌ | — |
| Kurumlar | ✅ | ✅ | ✅ | ✅ | ❌ | — |
| Dergiler | ✅ | ✅ | ✅ | ✅ | ✅ | — |

### Yazar
| Modül | listele | goruntule | duzenle | kaydet | sil | yayinla |
|---|---|---|---|---|---|---|
| Haberler | ✅ (kendi) | ✅ (kendi) | ✅ (kendi) | ✅ | ❌ | ❌ |
| Etkinlikler | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

### Halkla İlişkiler
| Modül | listele | goruntule | duzenle | kaydet | sil | yayinla |
|---|---|---|---|---|---|---|
| Mezunlar | ✅ | ✅ | ✅ | — | ✅ | — |
| Haberler | ✅ (kendi) | ✅ (kendi) | ✅ (kendi) | ✅ | ❌ | ❌ |
| Pazarlama SMS | ✅ | ✅ | ✅ | ✅ | ✅ | — |

### Muhasebe
| Modül | listele | goruntule | duzenle | kaydet | sil |
|---|---|---|---|---|---|
| Bağış | ✅ | ✅ | ❌ | ❌ | ❌ |
| Kurban Yönetimi | ✅ | ✅ | ❌ | ❌ | ❌ |

### E-Kayıt
| Modül | listele | goruntule | duzenle | kaydet | sil |
|---|---|---|---|---|---|
| Öğrenci E-Kayıt | ✅ | ✅ | ✅ | ✅ | ❌ |
| Kurumlar | ✅ | ✅ | ❌ | ❌ | ❌ |

### Kurban
| Modül | listele | goruntule | duzenle | kaydet | sil | onayla |
|---|---|---|---|---|---|---|
| Kurban Yönetimi | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Bağış | ✅ | ✅ | ❌ | ❌ | ❌ | — |

---

## İş Kuralları

### Özel Rol Oluşturma

Admin istediği isimde yeni bir rol oluşturabilir (örn: "Etkinlik Yöneticisi"). Rol oluşturulduktan sonra her modül için izinler tek tek atanır. Varsayılan roller silinemez, özel roller silinebilir.

### İzin Atama Mantığı

```
Rol oluştur → Her modül için izinleri seç → Kaydet → Yöneticiye ata
```

Bir yöneticiye birden fazla rol atanabilir. Yöneticiye rol üzerinden gelen izinlere ek olarak doğrudan izin de atanabilir (Spatie'nin direkt permission özelliği).

### Kendi Kaydını Koruma

Admin kendi rolünü ve iznini düzenleyemez/silemez. Sistemde her zaman en az bir aktif Admin bulunmak zorundadır.

---

## Backend Admin Panel

### Roller Listesi
- Tüm roller listelenir
- Her rolde kaç yönetici olduğu gösterilir
- Varsayılan roller rozet ile işaretlenir, silinemez

### Rol Detay / Düzenleme
- Rol adı düzenlenebilir (varsayılan roller hariç)
- Modül bazlı izin matrisi: satırlar modüller, sütunlar izinler
- Checkbox ile izin atama/kaldırma
- Kaydet butonuyla değişiklikler anında uygulanır

### İzin Matrisi Görünümü (Örnek)

```
Modül               | listele | goruntule | duzenle | kaydet | sil | yayinla | onayla | gonder
--------------------|---------|-----------|---------|--------|-----|---------|--------|-------
Haberler            |   ✅    |    ✅     |   ✅    |   ✅   | ✅  |   ✅    |   —    |   —
Etkinlikler         |   ✅    |    ✅     |   ✅    |   ✅   | ✅  |   ✅    |   —    |   —
Kişiler             |   ✅    |    ✅     |   ✅    |   ✅   | ❌  |   —     |   ✅   |   —
Pazarlama - SMS     |   ✅    |    ✅     |   ❌    |   ❌   | ❌  |   —     |   —    |   ✅
...
```

---

## Loglanan İşlemler

Tüm rol ve izin değişiklikleri `Loglar` modülüne kaydedilir (bkz. loglar.md).

| İşlem | Log Mesajı Örneği |
|---|---|
| Rol oluşturma | Admin "Etkinlik Yöneticisi" Rolünü Oluşturdu. |
| Rol düzenleme | Admin "Editör" Rolünü Düzenledi. |
| Rol silme | Admin "Eski Rol" Rolünü Sildi. |
| İzin atama | Admin "Editör" Rolüne "haberler.yayinla" İznini Atadı. |
| İzin kaldırma | Admin "Editör" Rolünden "haberler.sil" İznini Kaldırdı. |
| Yöneticiye rol atama | Admin "Barış Yılmaz" Yöneticisine "Editör" Rolü Atadı. |
| Yöneticiden rol kaldırma | Admin "Barış Yılmaz" Yöneticisinden "Editör" Rolü Kaldırdı. |
