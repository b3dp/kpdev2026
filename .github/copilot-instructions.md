# Kestanepazari Web 2026 - Copilot Instructions

Bu talimatlar bu repository icindeki tum gorevlerde gecerlidir.

## Proje Genel Bilgi

| Alan | Deger |
|---|---|
| Framework | Laravel 12 + Filament 3.3 + Tailwind CSS v4 |
| PHP | /opt/plesk/php/8.2/bin/php |
| Sunucu | 2026.kestanepazari.org.tr / 213.238.190.185 |
| Web Root | /var/www/vhosts/2026.kestanepazari.org.tr/httpdocs |
| DB | MariaDB 10.6, charset: utf8mb4_turkish_ci |
| CDN | https://cdn.kestanepazari.org.tr (DO Spaces / ams3) |
| E-posta | ZeptoMail (bildirim@n.kestanepazari.org.tr) |
| SMS | Hermes / Iletisim Makinesi |
| AI | Gemini (gemini-2.5-flash) |

## Terminal - Zorunlu Format

Sunucuda tum artisan komutlarini asagidaki tam format ile calistir:

```bash
su -s /bin/bash b3dp2026 -c "cd /var/www/vhosts/2026.kestanepazari.org.tr/httpdocs && /opt/plesk/php/8.2/bin/php artisan <KOMUT>"
```

Kisaltma kullanma. Her artisan komutunu bu tam format ile yaz.

## Yasak Komutlar - Asla Calistirma

Aşağıdaki komutlari asla calistirma:

- migrate:fresh
- migrate:reset
- migrate:rollback
- db:wipe
- db:seed (tek basina, parametresiz)

Izinli:

- migrate (sadece yeni migration'lar icin)
- db:seed --class=XxxSeeder (belirli seeder ile)

## Genel Davranis Kurallari

- Tum islemleri otomatik yap, her adim icin onay sorma.
- Hata olusursa dur, hatayi acikla, cozum oner.
- Dosyayi duzenlemeden once mutlaka oku.
- Iliski adlarini, model adlarini ve kolon adlarini dosyalari okuyarak dogrula, tahmin etme.
- Controller ve Action katmanlarinda app(ServisAdi::class) kullanimi tercih edilir.
- Service katmaninda constructor inject kullanilabilir.
- DB transaction kullanma, her adim bagimsiz basarisiz olabilsin.
- Her servis metodu try/catch ile sarili olsun, hata Log::error ile yazilsin.

## Push Kurali

Her gorev sonunda commit yap:

```bash
git add . && git commit -m "<type>: <aciklama>"
```

Commit type'lari: feat, fix, refactor, chore, docs

Commit sonrasi mutlaka sor:

PUSH HAZIR - onayliyor musun?

Onay gelmeden push yapma.

## Kod Yazim Kurallari

### Genel

- Turkce degisken/metot adlari kullaniliyor (projeye uygun).
- snake_case: degiskenler ve DB kolonlari.
- camelCase: PHP metot adlari.
- PascalCase: sinif adlari.
- camelCase: Blade ve JS degiskenleri.

### Laravel

- Model iliskilerini kullanmadan once modeli oku.
- Nullable alanlar icin null kontrolu yap.
- Tarih alanlarini Carbon helper zinciri yerine Filament formatStateUsing ile isle:

```php
->formatStateUsing(fn ($state) => $state
    ? \Carbon\Carbon::parse($state)->format('d.m.Y H:i') : '—')
```

- dateTime() veya date() Filament metodlarini tarih gosteriminde kullanma.

### Filament

- Resource olusturmadan once mevcut resource'lari incele ve pattern'e uy.
- Action'larda islem sirasini koru: once mevcut logic, en sona servis cagrilari.
- Bildirimlerde Filament\Notifications\Notification::make() kullan.

### Migration

- Yeni migration olusturmadan once mevcut son migration'i oku.
- Kolon eklerken ilgili modele de $fillable veya $casts ekle.
- Index eklemek icin $table->index('kolon_adi') kullan.
- Foreign key icin ->constrained('tablo')->nullOnDelete() formatini tercih et.

## Servisler

| Servis | Dosya | Amac |
|---|---|---|
| ZeptomailService | app/Services/ZeptomailService.php | E-posta gonderimi |
| HermesService | app/Services/HermesService.php | SMS gonderimi |
| OtpService | app/Services/OtpService.php | OTP islemleri |
| GeminiService | app/Services/GeminiService.php | AI islemleri |
| KisiEslestirmeService | app/Services/KisiEslestirmeService.php | Kimlik birlestirme |

## Onemli Dosya Yollari

- app/Services/
- app/Jobs/MakbuzOlusturJob.php
- app/Jobs/HermesAktarimJob.php
- app/Jobs/EkayitSmsJob.php
- app/Jobs/OnayEpostasiGonderJob.php
- app/Jobs/GorselOptimizeJob.php
- app/Http/Controllers/HaberOnayController.php
- app/Http/Controllers/KayitController.php
- app/Models/MezunProfil.php
- app/Models/Haber.php
- app/Models/Bagis.php
- app/Models/Kisi.php
- app/Models/Uye.php
- app/Filament/Resources/HaberResource.php
- app/Filament/Resources/HaberResource/Pages/EditHaber.php
- app/Filament/Resources/MezunProfilResource.php
- app/Filament/Resources/SmsKisiResource.php
- app/Filament/Resources/SmsListeResource.php
- app/Filament/Resources/SmsGonderimResource.php
- app/Filament/Pages/HizliSmsSayfasi.php
- app/Filament/Pages/TopluSmsSayfasi.php
- app/Console/Commands/SmsGonderimDurumGuncelle.php
- resources/views/emails/_layout.blade.php
- routes/console.php
- routes/web.php
- docs/

## E-posta Sablonlari

Ana layout: resources/views/emails/_layout.blade.php

Eski layout layouts/base.blade.php kullanilmiyor. Yeni sablonlarda _layout.blade.php extend edilmeli.

Mevcut sablonlar:

- bagis_makbuz.blade.php - Schema.org Order
- ekayit_onay.blade.php - Schema.org EventReservation eklenecek
- haber_onay.blade.php
- mezun_onaylandi.blade.php
- mezun_reddedildi.blade.php
- otp_giris.blade.php
- otp_kayit.blade.php
- sifre_sifirlama.blade.php
- uye_kayit_onay.blade.php
- yonetici_alert.blade.php

## Roller

| Rol | Yetkiler |
|---|---|
| Admin | Her sey |
| Editor | Tum haberler, yayina al, AI islemleri |
| Yazar | Kendi haberleri, taslak, incelemeye gonder, AI islemleri |
| E-Kayit | E-kayit modulu |
| Pazarlama | SMS modulu |
| Muhasebe | Bagis raporlari |
| Kurban | Kurban modulu |
| Kurs Yoneticisi | Kurs yonetimi |

## SMS Scheduler (routes/console.php)

- Her dakika: Queue worker (--stop-when-empty --max-time=55)
- Her 10 dakika: SMS gonderim durumu guncelle
- Her dakika: Haber onay SMS hatirlatma (HABER_ONAY_SMS_DAKIKA env)
- Her dakika: Planli haberler yayina al

## Haber Akisi

Yazar -> Taslak -> Incelemeye Gonder

- Editor'e e-posta (token ile Yayina Al linki)
- HABER_ONAY_SMS_DAKIKA gecerse SMS hatirlatma
- Editor duzenler (log) ve yayina alir

## Kimlik Birlestirme Kurali

Telefon veya e-posta ile kisiler tablosunda ara:

- Bulunduysa kisi_id eslestir, sadece bos alanlari guncelle (mevcut veriyi silme).
- Bulunamazsa yeni kisi olustur ve kisi_id eslestir.

Tetikleyiciler:

- Bagis tamamlandi -> bagisEslestir() -> Bagisci rozeti
- E-Kayit onaylandi -> ekayitEslestir() -> Veli rozeti
- Mezun onaylandi -> mezunEslestir() -> Mezun rozeti
- Uye kaydoldu -> uyeEslestir()

## Kritik ENV Degiskenleri

```env
HABER_ONAY_SMS_DAKIKA=60
HABER_ONAY_EDITOR_ID=1
DO_SPACES_REGION=ams3
DO_SPACES_BUCKET=kestanepazari
DO_SPACES_ENDPOINT=https://ams3.digitaloceanspaces.com
DO_SPACES_CDN_URL=https://cdn.kestanepazari.org.tr
ZEPTOMAIL_FROM_ADDRESS=bildirim@n.kestanepazari.org.tr
ZEPTOMAIL_FROM_NAME=Kestanepazari
GEMINI_MODEL=gemini-2.5-flash
```

Not: HABER_ONAY_SMS_DAKIKA degeri test ortami disinda production icin 60 olmalidir.

## Faz Referansi

Tamamlananlar:

- Faz 1-4: Temel altyapi, auth, roller, kisiler, kurumlar, Levenshtein
- Faz 5A-5B: Haberler (CRUD, Gemini AI, gorseller, kisi/kurum tespiti)
- Faz 6: Etkinlikler
- Faz 7A-7B: Bagis modulu temel
- Faz 8A-8B: E-Kayit (cekirdek, Excel, Drive, e-posta, WhatsApp, bildirim)
- Faz 9: Mezunlar backend paneli, rozet entegrasyonu
- Faz 10A: SMS Modulu (Hermes, pazarlama, OTP, bildirim)
- Kimlik Birlestirme: KisiEslestirmeService

Sıradaki Fazlar:

- Faz 10B: Pazarlama E-posta
- Faz 11: Frontend - tum public sayfalar (oncelikli)
- Faz 7C: Odeme entegrasyonu (Albaraka + Paytr)
- Faz 12: Test + canli gecis

## Faz Dokumantasyonu

Her faza ait detayli MD dosyalari docs/ klasorundedir.

Yeni bir faza baslamadan once:

1. docs/ klasorunu listele.
2. Ilgili faz dosyasini bul.
3. Icerigini okuyarak ise basla.
4. Dosya yoksa iste.
