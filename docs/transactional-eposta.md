# Modül: Transactional E-posta

## Genel Bilgi

| Alan | Değer |
|---|---|
| Modül Adı | Transactional E-posta |
| Servis | Zoho ZeptoMail — bkz. servisler.md |
| Kullanım | Sadece tetikleyici bazlı, kişiye özel e-postalar |
| Pazarlama | ❌ ZeptoMail ile kesinlikle yapılmaz |
| Bağımlı Modüller | Üyeler, Bağış, Kurban, E-Kayıt, Yöneticiler |

> **Kritik:** ZeptoMail sadece transactional e-posta için kullanılır.
> Toplu/pazarlama e-postaları için ayrı servis kullanılacak (ileride belirlenecek).

---

## Gönderilen E-posta Tipleri

| Tip | Tetikleyici | Alıcı |
|---|---|---|
| OTP Kodu | Üye girişi / kayıt | Üye |
| Şifre Sıfırlama | Şifre sıfırlama talebi | Üye |
| Üye Kayıt Onayı | Kayıt tamamlandı | Üye |
| Bağış Makbuzu | Ödeme onaylandı | Bağışçı |
| Bağış Hatası | Ödeme başarısız | Bağışçı + Yöneticiler |
| Kurban Kesildi | Kurban durumu güncellendi | Sahip/Hissedarlar |
| Haber Onay | Haber incelemeye alındı | Editör |
| Mezun Onay | Mezun kaydı onaylandı/reddedildi | Üye |
| E-Kayıt Onayı | E-kayıt tamamlandı | Veli |
| E-Kayıt Evrakı | Evrak hazır | Veli |
| Yönetici Bildirimi | Kritik sistem olayı | Yöneticiler |

---

## Veritabanı

### Tablo: `eposta_sablonlar`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| kod | string(100) | ✅ | Unique. Örn: otp_giris, bagis_makbuz |
| ad | string(255) | ✅ | İç kullanım adı |
| konu | string(255) | ✅ | Mail konusu. Değişkenler: {AD_SOYAD} vb. |
| icerik | longText | ✅ | HTML mail içeriği |
| tip | enum | ✅ | `otp` / `bildirim` / `makbuz` / `onay` / `sistem` |
| aktif | boolean | ✅ | true |
| created_at | timestamp | ✅ | — |
| updated_at | timestamp | ✅ | — |

### Tablo: `eposta_gonderimleri`

| Alan | Tip | Zorunlu | Açıklama |
|---|---|---|---|
| id | bigIncrements | ✅ | — |
| sablon_kodu | string(100) | ✅ | → `eposta_sablonlar.kod` |
| alici_eposta | string(255) | ✅ | — |
| alici_ad | string(255) | ❌ | — |
| konu | string(255) | ✅ | Değişkenler doldurulmuş hali |
| durum | enum | ✅ | `beklemede` / `gonderildi` / `basarisiz` |
| zeptomail_message_id | string(255) | ❌ | ZeptoMail referans ID |
| hata_mesaji | string(500) | ❌ | Başarısız ise hata detayı |
| ilgili_tip | string(100) | ❌ | bagis, uye, kurban vb. |
| ilgili_id | bigInteger | ❌ | İlgili kaydın ID'si |
| created_at | timestamp | ✅ | — |

---

## Servis Yapısı

### ZeptomailService

`app/Services/ZeptomailService.php` — mevcut servis güncellenir.

```php
class ZeptomailService
{
    // Temel gönderim
    public function gonder(
        string $aliciEposta,
        string $aliciAd,
        string $sablonKodu,
        array $degiskenler = [],
        ?string $ilgiliTip = null,
        ?int $ilgiliId = null
    ): bool

    // Özel metodlar
    public function otpGonder(string $eposta, string $ad, string $kod): bool
    public function makbuzGonder(string $eposta, string $ad, string $makbuzUrl, string $bagisNo): bool
    public function kurbanBildirimGonder(string $eposta, string $ad, string $kurbanNo): bool
    public function haberOnayGonder(string $eposta, string $ad, string $haberBaslik, string $onayUrl, string $redUrl): bool
    public function mezunOnayGonder(string $eposta, string $ad, bool $onaylandi, ?string $redNotu = null): bool
    public function ekayitOnayGonder(string $eposta, string $ad, string $kayitNo, string $evrakUrl): bool
    public function yoneticiAlertGonder(array $alicilar, string $konu, string $mesaj): bool
}
```

---

## E-posta Şablonları

Her şablon `eposta_sablonlar` tablosunda saklanır.
HTML şablonları kullanıcı tarafından sağlanacak.
Değişkenler `{AD_SOYAD}`, `{KOD}`, `{TUTAR}` formatında.

### Şablon Kodları

| Kod | Açıklama | Değişkenler |
|---|---|---|
| `otp_giris` | Giriş OTP kodu | {AD_SOYAD}, {KOD}, {GECERLILIK} |
| `otp_kayit` | Kayıt OTP kodu | {AD_SOYAD}, {KOD}, {GECERLILIK} |
| `sifre_sifirlama` | Şifre sıfırlama | {AD_SOYAD}, {LINK}, {GECERLILIK} |
| `uye_kayit_onay` | Üye kayıt onayı | {AD_SOYAD}, {GIRIS_LINK} |
| `bagis_makbuz` | Bağış makbuzu | {AD_SOYAD}, {BAGIS_NO}, {TUTAR}, {MAKBUZ_URL} |
| `bagis_hatasi` | Ödeme hatası | {AD_SOYAD}, {BAGIS_NO}, {HATA_MESAJI} |
| `kurban_kesildi` | Kurban bildirimi | {AD_SOYAD}, {KURBAN_NO}, {KESIM_TARIHI} |
| `haber_onay` | Haber onay talebi | {HABER_BASLIK}, {ONAY_URL}, {RED_URL} |
| `mezun_onaylandi` | Mezun onayı | {AD_SOYAD} |
| `mezun_reddedildi` | Mezun reddi | {AD_SOYAD}, {RED_NOTU} |
| `ekayit_onay` | E-kayıt onayı | {AD_SOYAD}, {KAYIT_NO}, {EVRAK_URL} |
| `yonetici_alert` | Sistem uyarısı | {KONU}, {MESAJ} |

---

## Job Yapısı

### EpostaGonderJob (mevcut — güncellenir)

```
Queue: high (OTP, makbuz)
       default (diğerleri)
Timeout: 30sn
Retry: 3
Backoff: [60, 120, 300]
```

---

## Panel — Şablon Yönetimi

### Filament Resource: E-posta Şablonları

- Liste: Kod, Ad, Tip, Aktif
- Form: Kod, Ad, Konu, HTML İçerik (rich editor), Tip, Aktif
- Önizleme: Örnek değişkenlerle mail önizleme (modal)
- canView(): Admin

### Gönderim Geçmişi (Salt Okunur)

- Liste: Şablon, Alıcı, Konu, Durum, Tarih
- Filtreler: durum, tip, tarih aralığı
- canView(): Admin, Editör

---

## ZeptoMail ENV

```
ZEPTOMAIL_API_KEY=
ZEPTOMAIL_FROM_ADDRESS=bildirim@n.kestanepazari.org.tr
ZEPTOMAIL_FROM_NAME="Kestanepazarı Öğrenci Yetiştirme Derneği"
ZEPTOMAIL_BOUNCE_ADDRESS=bounce@kestanepazari.org.tr
```

---

## Önemli Kurallar

1. ZeptoMail ile **hiçbir zaman** toplu/pazarlama e-postası gönderilmez
2. Her e-posta mutlaka bir tetikleyici olaya bağlıdır
3. Aynı kişiye aynı tip e-posta 1 dakika içinde 2 kez gönderilemez (rate limit)
4. Tüm gönderimleri `eposta_gonderimleri` tablosuna logla
5. Başarısız gönderimler 3 kez retry — hâlâ başarısız ise logla ve devam et
6. OTP e-postaları için `high` queue kullan — gecikme kabul edilemez
