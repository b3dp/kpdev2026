# Genel Panel Notları

Bu dosya tüm modüller için geçerli olan ortak kuralları içerir.
Modül MD dosyalarında tekrar yazılmaz, buraya referans verilir.

---

## Arama ve Filtreleme

### Genel Kural

Her modülün liste sayfasında **gelişmiş arama ve filtreleme** zorunludur.
Arama yalnızca başlık veya ad alanıyla sınırlı kalmaz — modüle ait tüm anlamlı metin alanları aranabilir olmalıdır.

### Arama Motoru

**Laravel Scout + TNTSearch** kullanılacak.

| Özellik | Detay |
|---|---|
| Paket | `laravel/scout` + `teamtnt/laravel-scout-tntsearch-driver` |
| Sunucu | Ekstra servis gerekmez, sunucuda dosya tabanlı çalışır |
| Senkronizasyon | Model kaydedildiğinde/güncellendiğinde/silindiğinde otomatik index güncellenir |
| Türkçe Destek | `utf8mb4_turkish_ci` collation + TNTSearch Türkçe stemming desteği |
| Typo Tolerans | Kısmi typo toleransı mevcut |
| Ölçekleme | İleride Meilisearch'e geçmek gerekirse sadece Scout driver değişir, model kodu değişmez |

### Modül Bazlı Aranabilir Alanlar

| Modül | Aranabilir Alanlar |
|---|---|
| Haberler | baslik, icerik (tam metin), ozet, yazar adı |
| Etkinlikler | baslik, aciklama, yer, konuşmacı adı |
| Kurumsal Sayfalar | baslik, icerik (tam metin) |
| Kişiler | ad_soyad, unvan, kurum_aciklama |
| Kurumlar | ad |
| Üyeler | ad_soyad, telefon, eposta |
| Bağış | üye adı, kampanya adı |
| Öğrenci E-Kayıt | öğrenci adı, veli adı, kurum adı |
| Mezunlar | ad_soyad, mezuniyet yılı, kurum |
| Dergiler | baslik, aciklama |
| İrşad | cami adı, görevli adı, telefon |
| Kurban Yönetimi | üye adı, telefon |
| Pazarlama - SMS | gönderim adı, mesaj içeriği |
| Pazarlama - Eposta | konu, gönderim adı |

### Filtreleme

Her modülde arama kutusunun yanı sıra alan bazlı filtreler bulunur.
Filtreler modül MD dosyalarında ayrıca belirtilir.

**Ortak filtreler (tüm modüllerde):**
- Tarih aralığı (created_at)
- Durum (aktif / pasif / beklemede vb.)
- Oluşturan yönetici

**Filtreleme + Arama birlikte çalışır:** Önce filtre uygulanır, sonra arama o sonuçlar içinde yapılır.

### Sayfalama

Tüm liste sayfalarında sayfalama zorunludur. Varsayılan: sayfa başına 25 kayıt. Admin değiştirebilir: 25 / 50 / 100.

### Sıralama

Tüm sütun başlıklarına tıklanarak ASC/DESC sıralama yapılabilir.

---

## Bildirim Sistemi (Panel İçi)

Tüm modüllerdeki bekleyen onaylar, AI teklifleri ve sistem uyarıları panel içi bildirim olarak gösterilir.

| Bildirim Türü | Tetikleyici |
|---|---|
| Yeni kişi onay bekliyor | AI kişi tespiti |
| Yeni kurum onay bekliyor | AI kurum tespiti |
| Haber inceleme bekliyor | Yazar "Kaydet" dedi |
| Benzer kişi/kurum eşleştirme | Levenshtein eşleşmesi |
| Yeni üye kaydı | Frontend'den kayıt |
| Üye eşleştirme önerisi | Üye + Kişi Levenshtein eşleşmesi |
| Başarısız giriş (5+) | Güvenlik logu |
| Bağış/Kurban ödeme alındı | Ödeme servisi callback |

Bildirimler okundu/okunmadı olarak işaretlenir. Zil ikonu üzerinde okunmamış sayısı gösterilir.

---

## Soft Delete

Tüm modüllerde kayıtlar fiziksel olarak silinmez, `deleted_at` alanı doldurulur.
Silinen kayıtlar listede görünmez ancak admin "Silinmişleri Göster" filtresiyle erişebilir.
Geri yükleme (restore) tüm modüllerde desteklenir.

---

## Timestamps

Tüm tablolarda `created_at` ve `updated_at` zorunludur.
Listelerde varsayılan sıralama `created_at DESC`'tir.

---

## Dil

Panel dili Türkçedir. Tüm arayüz metinleri, hata mesajları, bildirimler Türkçe olacaktır.
Laravel dil dosyaları (`lang/tr`) eksiksiz doldurulacaktır.

---

## Responsive

Admin panel masaüstü öncelikli tasarlanır. Tablet desteği olur, mobil zorunlu değildir.

---

## Geliştirme Notları

- Filament PHP kullanılacak
- Her modül için ayrı Filament Resource oluşturulacak
- TNTSearch index ayarları her modülün Model dosyasında `toSearchableArray()` ile tanımlanacak
- Scout soft delete uyumlu çalıştırılacak: `SoftDeletes` + `ScoutBuilder`
