# Kestanepazarı Web Projesi — Proje Özeti ve Devam Dosyası

Bu dosya her yeni Claude konuşmasında context'e eklenir.
Son güncelleme: 29 Mart 2026

---

## Proje Genel Bilgisi

| Alan | Değer |
|---|---|
| Proje | Kestanepazarı Öğrenci Yetiştirme Derneği Web Sistemi |
| Teknoloji | Laravel 12 + Filament 3.3 + Tailwind CSS v4 |
| Sunucu | 2026.kestanepazari.org.tr / 213.238.190.185 |
| Web Root | /var/www/vhosts/2026.kestanepazari.org.tr/httpdocs |
| PHP | /opt/plesk/php/8.2/bin/php |
| Composer | /usr/local/bin/composer |
| DB | livekp_2026dev |
| Queue | database driver |
| Git | https://github.com/b3dp/kpdev2026.git |
| Panel URL | https://2026.kestanepazari.org.tr/yonetim |
| Admin | baris@b3dp.com / .Kest35,, |
| Sunucu | Plesk Obsidian, Ubuntu 22.04 |

---

## Kodlama Standartları

- Tüm isimler Türkçe (tablo, model, method, değişken)
- Tablo: snake_case çoğul, Model: PascalCase tekil
- Foreign key: {tekil_tablo}_id
- Her tabloda: timestamps + softDeletes zorunlu
- Boolean: default(false) zorunlu
- Para: decimal(10,2)
- Enum: PHP 8.1 native string backed, migration'da string
- Filament: light mode, blue-600, sortable(), defaultSort('created_at','desc')
- Widget'larda canView() zorunlu
- Blade'de {!! !!} yasak
- .env asla commit edilmez

## Çalışma Kuralları

YASAK KOMUTLAR:
- php artisan migrate:fresh
- php artisan migrate:reset
- php artisan migrate:rollback
- php artisan db:wipe
- php artisan db:seed (tek başına)

IZINLI:
- php artisan migrate (sadece yeni migration'lar)
- php artisan db:seed --class=BelirliSeeder

## Kritik Teknik Notlar

- Terminal: su -s /bin/bash b3dp2026 -c "..." kullan (root değil)
- Spatie: hasAnyPermission() -- hasAnyPermissionTo() YOKTUR
- Tarih alanları: dateTime() değil formatStateUsing() kullan
  -> fn($state) => $state ? Carbon::parse($state)->format('d.m.Y H:i') : 'Yok'
- spaces disk config'de root YOK (kaldırıldı)
- AdminSeeder VSCode tarafindan bosaltiliyor -- tinker ile olustur

---

## Google API

| Servis | Deger |
|---|---|
| Gemini API Key | AIzaSyCqd0B7J7so7KGKThNCWtoO0aOLKe9Uers |
| Maps Frontend | AIzaSyA0d9Ts-WHaAkiQfzB17nKZE1a3L2oDxbM |
| Maps Backend | AIzaSyCnN2JcTWvKwtNvHPFq7wOpBnjWL3ZDd8o |
| reCAPTCHA Site | 6Leud5csAAAAABLdfJ7rbcWj3sy2FIqx3d_3Km4n |
| reCAPTCHA Secret | 6Leud5csAAAAAOrkXMbntVxFfD-4w4qZH4SDppdN |
| Drive Bagis Klasor | 1N7tizcXL8MieOXt85wCgNgPaORFZOzrc |
| Drive EKayit Klasor | 1ne-mlqyjdzbLeQyVlrYYhXrAvs7mBlj- |
| Service Account JSON | storage/app/private/google-service-account.json |
| Cloud Project ID | 593314496951 |
| GA4 | Henuz olusturulmadi -- sifirdan kurulacak |

## ZeptoMail

| Alan | Deger |
|---|---|
| Endpoint | https://api.zeptomail.com/v1.1/email |
| Authorization | Zoho-enczapikey wSsVR60irxb0DvspnTOqdrw7mV1TBA/3Ex963wfy7nL5T/vLpcc6w0ScUFfxSqMXETI9FzAT8el9nRcI1jIKjo57z1AHXCiF9mqRe1U4J3x17qnvhDzMWG9alRSJL4wPwA1smWNgFskl+g== |
| Gonderen | bildirim@n.kestanepazari.org.tr |

## Kurulu Paketler

- spatie/laravel-permission, spatie/laravel-activitylog, spatie/laravel-sluggable
- z3d0x/filament-logger
- intervention/image (ImageMagick driver)
- league/flysystem-aws-s3-v3
- laravel/scout + teamtnt/laravel-scout-tntsearch-driver
- barryvdh/laravel-dompdf
- openspout/openspout (Excel -- maatwebsite YASAK)
- khalid-alsaqqa/laravel-hijri
- google/apiclient:^2.x

---

## Tamamlanan Fazlar

| Faz | Icerik |
|---|---|
| Faz 1 | Laravel, Filament, Spatie, Queue, TNTSearch, DO Spaces altyapi |
| Faz 2A | Roller, Yoneticiler, Guard, Seeder |
| Faz 2B | Loglar |
| Faz 3 | Kisiler, Kurumlar, Levenshtein servisi |
| Faz 4A | Uyeler, OTP, Trusted Device, Rozet |
| Faz 4B | Frontend Auth, Abonelik cikis |
| Faz 5A | Haberler cekirdeği |
| Faz 5B | Haberler medya ve AI (gemini-2.5-flash) |
| Faz 5C | Etkinlikler |
| Faz 6 | Kurumsal Sayfalar + Global Arama |
| Google API | Gemini, Maps, reCAPTCHA, Drive konfig |
| Transactional E-posta | ZeptoMail + blade sablonlari |
| Faz 7A | Bagis cekirdeği, sepet, panel, Hicri takvim, widgets |
| Faz 7B | Raporlama: Excel, Drive, makbuz PDF, scheduler |
| Faz 8A | E-Kayit cekirdeği: donem, sinif, kayit, hazir mesaj, evrak sablonu |
| Faz 8A+ | E-Kayit Excel + Drive + e-posta ile gonderme |

---

## Devam Eden -- Faz 8B

Acil duzeltmeler (Copilot'a ver):
1. Carbon InvalidFormatException -- tarih alanlari formatStateUsing() kullanmali
2. Yeni kayit olusturma -- afterCreate() alt tablo kayit sorunu
3. KurumsalSayfa -> kurumlar.kurumsal_sayfa_id otomatik guncelleme (saved() event)

Kalan isler:
- Kayit detay sayfasi (4 widget + 5 card -- docs/ekayit.md)
- WhatsApp URL bildirimi (wa.me)
- Hazir mesaj entegrasyonu
- Evrak PDF uretimi (dompdf, resources/views/pdf/ekayit/)
- Evrak ZIP + Drive'a yukleme

---

## Kalan Fazlar

| Faz | Icerik | Not |
|---|---|---|
| Faz 8B | E-Kayit detay, WhatsApp, PDF, ZIP | Devam ediyor |
| Faz 8C | E-Kayit Drive raporlama | 8B sonrasi |
| Faz 7C | Odeme entegrasyonu | Albaraka + Paytr -- ileriye atildi |
| Faz 9 | Mezunlar + Irsad | -- |
| Faz 10A | Pazarlama SMS (Hermes) | Etkinlik hatirlatma dahil |
| Faz 10B | Pazarlama E-posta | Brevo/SES -- servis secilmedi |
| Faz 5D | Dergiler | Sonraya birakildi |
| Faz 11 | Frontend | Tum public sayfalar |
| Faz 12 | Test + Canli | -- |

---

## Bekleyen Notlar

- Etkinlikler SMS/E-posta hatirlatma -> Faz 10A
- reCAPTCHA SMS Defense -> Faz 10A
- reCAPTCHA Fraud Prevention -> Faz 7C
- GA4 + GTM + Google Ads sifirdan kurulum -> Ayri konusmada
- GA4 server-side Measurement Protocol -> Faz 7C
- Pazarlama e-posta servis secimi -> Faz 10B oncesi
- E-Kayit sinif gorselleri DO Spaces upload -> Simdilik URL
- Google Places API (New) -> Faz 5C duzeltme

---

## Mimari Kararlar

Auth: 2 guard (admin + uye), Filament /yonetim guard admin, uye OTP ile giris
E-posta: ZeptoMail transactional, pazarlama icin ayri servis (henuz yok)
Odeme: Albaraka birincil, Paytr yedek, Bagis No: KP-YIL-AY-GUN-0001
Analytics: GA4 sifirdan, server-side MP + GTM frontend, transaction_id tekillesme
Gorsel: ImageMagick, WebP optimize, JPEG orijinal, CDN cdn.kestanepazari.org.tr
Excel: openspout/openspout (maatwebsite YASAK)
WhatsApp E-Kayit: wa.me URL, API degil
Evrak PDF: Blade resources/views/pdf/ekayit/, dompdf, DO Spaces, ZIP

---

## DO Spaces Yol Yapisi

img26/ori/haberler/{id}/{slug}-ana-orijinal.jpeg
img26/opt/haberler/{id}/{slug}-ana-lg.webp
img26/opt/haberler/{id}/galeri/{slug}-{sira}-lg.webp
img26/ori/etkinlikler/{id}/...
img26/ori/kurumsal/{id}/...
img26/ori/bagis/{slug}-original.jpg
img26/opt/bagis/{slug}-1x1.webp
img26/pdf26/bagis/{yil}/{bagis-no}-makbuz.pdf
img26/xlsx26/bagis/{yil}/{periyot}-{tarih}.xlsx
img26/ori/ekayit/{sinif_id}/{slug}-original.jpg
img26/opt/ekayit/{sinif_id}/{slug}-1x1.webp
img26/pdf26/ekayit/{ogretim-yili}/{kayit-no}/{kayit-no}-{dosya-adi}.pdf

NOT: img26/ prefix sadece bir kez -- cift olmasin

---

## Filament Panel Yapisi

Genel Bakis (Dashboard)
Sistem: Loglar
E-Kayit: Genel Bakis, Kayitlar, Siniflar, Donemler, Hazir Mesajlar, Evrak Sablonlari
Icerik Yonetimi: Haberler, Haber Kategorileri, Etkinlikler, Kurumsal Sayfalar, E-posta Sablonlari, E-posta Gecmisi
CRM: Kisiler, Kurumlar
Uye Yonetimi: Uyeler
Bagis Yonetimi: Bagislar, Bagis Turleri, Bagis Otomatik Raporlar
Yonetim: Yoneticiler, Roller

---

## Gelistirme Araclari

VS Code + GitHub Copilot (Remote SSH) -- ana ortam
Google Antigravity (antigravity.google, Gmail ile ucretsiz) -- alternatif
Claude (claude.ai) -- mimari, prompt, istisare

---

## Yeni Claude Konusmasi Baslat

1. claude.ai'da yeni konusma ac
2. PROJE-OZET.md ekle
3. Ilgili faz MD'sini ekle (docs/ekayit.md gibi)
4. Su mesaji yaz:

---
Bu dosyayi oku ve projeyi anla.
Kestanepazari web projesine devam ediyoruz.
Su an Faz 8B uzerinde calisiyoruz.

Gelistirme ortamimiz:
- VS Code + GitHub Copilot (Remote SSH)
- Alternatif: Google Antigravity
- Sen (Claude): Mimari kararlar, prompt hazirlama

Bana Faz 8B icin Copilot'a verecegim promptu hazirla.

YASAK: migrate:fresh, db:wipe, migrate:reset, migrate:rollback, db:seed (tek basina)
IZINLI: migrate (yeni), db:seed --class=BelirliSeeder
---

Copilot'a prompt verirken:
- Yeni konusma baslat (token tasarrufu)
- Sadece ilgili docs'lari ekle
- "Tum islemleri otomatik yap, onay sorma" ekle
- "Push oncesi sor" ekle
- Kod verme -- ne yapilacagini tarif et, nasil yapilacagini Copilot kararlastirsin
