# Modül: Kimlik Birleştirme (Identity Resolution)

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Kimlik Birleştirme |
| Backend | Evet |
| Frontend | Hayır |
| Öncelik | Orta |
| Bağımlı Modüller | Kişiler, Üyeler, Bağış, E-Kayıt, Mezunlar |

---

## Amaç

Sistemdeki tüm kişi kayıtlarını tek bir merkeze bağlamak. Aynı telefon veya e-posta adresi farklı modüllerde (bağış, e-kayıt, mezun, üye) tekrar tekrar kaydedilmek yerine `kisiler` tablosuna bağlanır.

---

## Temel Kural

```
Telefon veya e-posta → kisiler tablosunda ara
    Bulundu → kisi_id eşleştir, bilgileri güncelle
    Bulunamadı → yeni kisi kaydı oluştur, kisi_id eşleştir
```

---

## Senaryolar

### Senaryo 1 — Bağış

```
Bağış yapıldı
    ↓
Telefon/e-posta ile kisiler tablosunda ara
    Bulundu → bagis.kisi_id = kisi.id
              kisi.ad_soyad güncelle (boşsa)
              uye varsa uye.kisi_id eşleştir
              uyeye bağışçı rozeti ekle
    Bulunamadı → yeni kisi oluştur
                 bagis.kisi_id = yeni kisi.id
                 uye varsa uye.kisi_id eşleştir
                 uyeye bağışçı rozeti ekle
```

### Senaryo 2 — E-Kayıt (Veli)

```
E-Kayıt başvurusu onaylandı
    ↓
Veli telefon/e-postası ile kisiler tablosunda ara
    Bulundu → uye.kisi_id = kisi.id
              kisi bilgileri güncelle
              uyeye veli rozeti ekle
    Bulunamadı → yeni kisi oluştur
                 uye.kisi_id = yeni kisi.id
                 uyeye veli rozeti ekle
```

### Senaryo 3 — Mezun

```
Mezun kaydı onaylandı
    ↓
Üye telefon/e-postası ile kisiler tablosunda ara
    Bulundu → uye.kisi_id = kisi.id
              kisi bilgileri güncelle
              uyeye mezun rozeti ekle
    Bulunamadı → yeni kisi oluştur
                 uye.kisi_id = yeni kisi.id
                 uyeye mezun rozeti ekle
```

---

## Güncelleme Kuralı

Kişi bilgileri güncellenirken **mevcut bilgi korunur, sadece boş alanlar doldurulur:**

```
kisi.ad_soyad → sadece boşsa güncelle
kisi.telefon  → sadece boşsa güncelle
kisi.eposta   → sadece boşsa güncelle
```

Kullanıcının önceki verisi silinmez — sadece eksik alanlar tamamlanır.

---

## KisiEslestirmeService

Tüm eşleştirme işlemleri tek bir servis üzerinden yapılır:

```
app/Services/KisiEslestirmeService.php

Metodlar:
- eslestir(telefon, eposta, adSoyad): Kisi
  → Bul veya oluştur, bilgileri güncelle, Kisi döndür

- uyeEslestir(Uye $uye): void
  → Üyeyi kisiler tablosuna bağla

- rozetEkle(Uye $uye, RozetTipi $tip, string $kaynakTip, int $kaynakId): void
  → Rozet yoksa ekle
```

---

## Tetikleyici Noktalar

| Olay | Tetikleyici | Rozet |
|---|---|---|
| Bağış tamamlandı | BagisSmsJob veya ödeme callback | Bağışçı |
| E-Kayıt onaylandı | ViewEkayitKayit onay action | Veli |
| Mezun onaylandı | MezunProfilResource onayla action | Mezun |
| Üye kaydoldu | KayitController | — |

---

## Bağımlılıklar

- `kisiler` tablosunda `telefon` ve `eposta` alanları indexli olmalı
- `uyeler.kisi_id` foreign key mevcut ✅
- `bagislar.kisi_id` foreign key var mı kontrol edilecek
- `mezun_profiller` → üye üzerinden dolaylı bağlı, direkt kisi_id gerekmez

---

## Açık Kararlar

- [ ] `bagislar` tablosunda `kisi_id` alanı var mı?
- [ ] Üye olmadan bağış yapılabilir mi? (misafir bağış)
- [ ] Kişi birleştirme admin panelinden manuel yapılabilecek mi?
- [ ] Çakışan veriler için öncelik sırası: hangi modülün verisi öncelikli?
