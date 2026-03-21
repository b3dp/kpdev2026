# Modül: Kurban Yönetimi

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Kurban Yönetimi |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Orta — Bağış modülünden sonra |
| Bağımlı Modüller | Bağış, Kişiler, Üyeler, Pazarlama - SMS, Pazarlama - Eposta, Loglar, Roller, Yöneticiler |

---

## Roller ve Yetkiler

| Rol | Listele | Görüntüle | Durum Güncelle | Bildirim Gönder | Not Ekle | Sil |
|---|---|---|---|---|---|---|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editör | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Kurban | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Muhasebe | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## Veritabanı

### Tablo: `kurban_kayitlar`

| Alan | Tip | Zorunlu | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | bigIncrements | ✅ | — | Primary key |
| kurban_no | string(20) | ✅ | — | Örn: KRB-2026-07-12-0001 |
| bagis_id | foreignId | ✅ | — | → `bagislar` |
| bagis_kalem_id | foreignId | ✅ | — | → `bagis_kalemleri` |
| tur | enum | ✅ | — | `vacip_kucukbas` / `vacip_buyukbas` / `adak` / `akika` / `sukur` |
| durum | enum | ✅ | bekliyor | `bekliyor` / `kesildi` |
| kesim_tarihi | timestamp | ❌ | null | "Kesildi" butonuna basılınca otomatik dolar |
| kesim_yeri | string(500) | ❌ | null | Kesim yeri — sadece panelde görünür |
| kesim_gorevlisi | string(255) | ❌ | null | Kesimi yapan kişi — sadece panelde görünür |
| hisse_sayisi | tinyInteger | ❌ | null | Büyükbaş için 1-7 |
| bildirim_durumu | enum | ✅ | gonderilmedi | `gonderilmedi` / `kismi` / `tamamlandi` |
| not | text | ❌ | null | Sadece panelde görünür |
| created_at | timestamp | ✅ | — | — |
| updated_at | timestamp | ✅ | — | — |
| deleted_at | timestamp | ❌ | null | Soft delete |

---

### Tablo: `kurban_kisiler`

Bağış modülündeki `bagis_kisiler` verisinin kurban modülüne kopyası. Ödeme bilgileri hariç.

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kurban_id | foreignId | ✅ | → `kurban_kayitlar` |
| bagis_kisi_id | foreignId | ✅ | → `bagis_kisiler` (kaynak) |
| tip | json | ✅ | `["sahip"]` / `["hissedar"]` / `["vekalet_sahibi"]` |
| ad_soyad | string(255) | ✅ | — |
| tc_kimlik | string(11) | ❌ | — |
| telefon | string(20) | ❌ | — |
| eposta | string(255) | ❌ | — |
| hisse_no | tinyInteger | ❌ | Büyükbaş hisse sırası |
| vekalet_ad_soyad | string(255) | ❌ | — |
| vekalet_tc | string(11) | ❌ | — |
| vekalet_telefon | string(20) | ❌ | — |

---

### Tablo: `kurban_bildirimler`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kurban_id | foreignId | ✅ | → `kurban_kayitlar` |
| kurban_kisi_id | foreignId | ✅ | → `kurban_kisiler` |
| kanal | enum | ✅ | `sms` / `eposta` |
| alici_ad | string(255) | ✅ | — |
| alici_iletisim | string(255) | ✅ | Telefon veya e-posta |
| durum | enum | ✅ | `gonderildi` / `basarisiz` |
| hata_mesaji | string(500) | ❌ | Başarısız ise hata detayı |
| gonderim_tarihi | timestamp | ✅ | — |
| created_at | timestamp | ✅ | — |

---

## İş Kuralları

### Bağıştan Kurban Modülüne Aktarım

```
Bağış ödendi → KurbanAktarimJob kuyruğa girer
        │
        ▼
bagis_kalemleri'nde kurban_modulu = true olan kalemler işlenir
        │
        ▼
Her kalem için kurban_kayitlar tablosuna kayıt oluşturulur:
  - Tür bağış özelliğinden belirlenir
  - Hisse sayısı kopyalanır
  - durum: bekliyor
  - bildirim_durumu: gonderilmedi
        │
        ▼
bagis_kisiler'deki sahip/hissedar/vekalet bilgileri
kurban_kisiler tablosuna kopyalanır (ödeme bilgileri hariç)
        │
        ▼
bagislar.kurban_aktarildi = true
bagis_kalemleri.kurban_id doldurulur
Panel bildirimi: "X adet kurban kaydı oluşturuldu"
```

---

### Kesildi Akışı

```
Yönetici "Kesildi" butonuna basar
        │
        ▼
durum: kesildi
kesim_tarihi: now() (otomatik)
        │
        ▼
Bildirim süreci başlar (otomatik, Queue/Job):

Her kurban_kisiler kaydı için:
  Telefon varsa → SMS gönder + WhatsApp wa.me linki oluştur
  E-posta varsa → E-posta gönder
  İkisi de varsa → İkisi de gönderilir
        │
        ▼
Her gönderim kurban_bildirimler tablosuna kaydedilir
        │
        ▼
bildirim_durumu güncellenir:
  Tümü başarılı → tamamlandi
  Kısmen başarılı → kismi
  Tümü başarısız → gonderilmedi (tekrar deneme butonu çıkar)
        │
        ▼
Panel bildirimi listesi:
  "Ahmet Yılmaz'a SMS gönderildi"
  "Mehmet Kaya'ya e-posta gönderildi"
  "Fatma Demir'e SMS ve e-posta gönderildi"
  (Her kişi ayrı satır)
```

### Büyükbaş Hissedar Bildirimi

```
7 hisseli kurban → "Kesildi" butonuna basılır
        │
        ▼
7 hissedar için aynı anda bildirim gönderilir (Queue/Job)
Panel bildirim özeti: "7/7 kişi bilgilendirildi"
Kısmi başarıda: "5/7 kişi bilgilendirildi, 2 kişi başarısız"
```

### Bildirim Kanal Seçim Mantığı

| Durum | Yapılan İşlem |
|---|---|
| Sadece telefon var | SMS gönderilir |
| Sadece e-posta var | E-posta gönderilir |
| Her ikisi de var | SMS + E-posta gönderilir |
| Hiçbiri yok | Olmaz — bağış yapabilmek için iletişim zorunlu |

---

## Backend Admin Panel

### Kurban Listesi

**Üst — İnfografik Kartlar:**

| Kart | İçerik |
|---|---|
| Toplam Kurban | Tüm zamanlar |
| Bekleyen | durum: bekliyor |
| Kesildi | durum: kesildi |
| Bildirim Bekleyen | bildirim_durumu: gonderilmedi veya kismi |

**Filtreler:**
- Tür (çoklu): Vacip Küçükbaş / Vacip Büyükbaş / Adak / Akika / Şükür
- Durum: Bekliyor / Kesildi
- Bildirim Durumu: Gönderildi / Gönderilmedi / Kısmi
- Tarih aralığı

**Liste Kolonları:**

| Kolon | Açıklama |
|---|---|
| Kurban No | KRB-2026-07-12-0001 |
| Tür | Renkli rozet |
| Sahip / Hissedarlar | İsim(ler) |
| Hisse Sayısı | Büyükbaş için |
| Durum | Bekliyor / Kesildi |
| Bildirim | Tamamlandı / Kısmi / Gönderilmedi rozeti |
| Bağış No | İlişkili bağış linki |
| İşlemler | Detaya Git |

---

### Kurban Detay Sayfası

#### Card 1 — Kurban Özeti
- Kurban No
- Tür
- Durum (Bekliyor / Kesildi)
- Kesim Tarihi (kesildi ise)
- Hisse Sayısı (büyükbaş ise)
- İlişkili Bağış No (link)
- **"Kesildi" Butonu** (durum bekliyor iken görünür, basılınca onay modalı)

#### Card 2 — Sahip / Hissedar Bilgileri

Küçükbaş (tek sahip):
- Ad Soyad, TC Kimlik, Telefon, E-posta
- **İletişim Butonları:** SMS | WhatsApp (`wa.me`) | Ara | E-posta

Büyükbaş (birden fazla hissedar):
Her hisse ayrı satır:
- Hisse No, Ad Soyad, TC Kimlik, Telefon, E-posta
- **İletişim Butonları:** SMS | WhatsApp | Ara | E-posta

#### Card 3 — Vekalet Bilgileri
*(Vekalet varsa görünür)*
- Vekalet Veren Ad Soyad
- Vekalet Veren TC
- Vekalet Veren Telefon

#### Card 4 — Bildirim Durumu
- Bildirim genel durumu (Tamamlandı / Kısmi / Gönderilmedi)
- Her kişi için mini liste:
  ```
  Ahmet Yılmaz    → SMS ✅  E-posta ✅
  Mehmet Kaya     → SMS ✅  E-posta ❌ (hata mesajı)
  Fatma Demir     → SMS ❌  E-posta ✅
  ```
- Başarısız olanlar için **"Tekrar Dene"** butonu

#### Card 5 — Kesim Bilgileri *(Sadece Panel)*
- Kesim Yeri
- Kesim Görevlisi
- **Düzenle** butonu (admin/editör)

#### Card 6 — Not *(Sadece Panel)*
- Serbest metin alanı
- Kaydet butonu

---

## Belirsizlikler / Açık Kararlar

- [x] Bildirim içeriği: Hazır şablon kullanılacak
- [x] "Kesildi" butonuna basılınca onay modalı çıkacak
- [x] Kurban No formatı: `KRB-YIL-AY-GUN-0001` onaylı
- [x] Kesim yeri/görevlisi: opsiyonel
