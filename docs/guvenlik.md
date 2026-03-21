# Güvenlik Önlemleri

Bu dosya tüm modüller için geçerli güvenlik kurallarını içerir.
Her form, her API endpoint ve her panel sayfası için uygulanacak önlemler burada tanımlanmıştır.
Modül MD dosyalarında tekrar yazılmaz, buraya referans verilir.

---

## 1. Input Validasyon

Tüm formlarda sunucu taraflı validasyon zorunludur. Frontend validasyon tek başına yeterli değildir.

### Genel Kurallar

| Alan Tipi | Validasyon Kuralı | Açıklama |
|---|---|---|
| Ad Soyad | `regex:/^[\p{L}\s]+$/u` | Sadece harf ve boşluk. HTML, link, özel karakter geçemez |
| Telefon | `regex:/^[0-9]{10,11}$/` | Sadece rakam, 10-11 hane |
| TC Kimlik | `digits:11` | Sadece 11 rakam |
| E-posta | `email:rfc,dns` | RFC + DNS doğrulaması |
| Metin alanları | `strip_tags()` + `htmlspecialchars()` | HTML inject engeli |
| Tutar/Sayı | `numeric` + `min:0` | Negatif değer engeli |
| Slug | `regex:/^[a-z0-9\-]+$/` | Sadece küçük harf, rakam, tire |
| Dosya yükleme | `mimes:jpg,jpeg,png,webp,pdf` + `max:10240` | Tip ve boyut kontrolü |

### Laravel Örnek

```php
$request->validate([
    'ad_soyad'  => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s]+$/u'],
    'telefon'   => ['required', 'regex:/^[0-9]{10,11}$/'],
    'tc_kimlik' => ['nullable', 'digits:11'],
    'eposta'    => ['nullable', 'email:rfc,dns'],
    'tutar'     => ['required', 'numeric', 'min:1'],
]);
```

### XSS Koruması

Tüm kullanıcı girdileri veritabanına kaydedilmeden önce temizlenir:
- `strip_tags()` ile HTML tagları temizlenir
- Blade şablonlarında `{{ }}` kullanılır, `{!! !!}` asla kullanılmaz
- Rich editor çıktıları için `HTMLPurifier` veya `Tiptap sanitize` uygulanır

---

## 2. Honeypot

Tüm frontend formlarına uygulanır. Kullanıcı görmez, bot doldurur, sistem reddeder.

**Paket:** `spatie/laravel-honeypot`

### Kurulum

```bash
composer require spatie/laravel-honeypot
php artisan vendor:publish --provider="Spatie\Honeypot\HoneypotServiceProvider"
```

### Kullanım

```php
// Middleware olarak route'a ekle
Route::post('/ekayit', [EkayitController::class, 'store'])
    ->middleware(ProtectAgainstSpam::class);
```

```blade
{{-- Blade formuna ekle --}}
<x-honeypot />
```

### Kapsam

Honeypot uygulanacak formlar:
- Öğrenci E-Kayıt formu
- Bağış formu
- Mezun kayıt formu
- Üye giriş / kayıt formu
- İletişim formları

---

## 3. Google reCAPTCHA v3

Görünmez — kullanıcıyı yormaz. Arka planda bot skoru hesaplar.
Skor 0.5'in altına düşerse istek reddedilir.

**Paket:** `google/recaptcha` veya `biscolab/laravel-recaptcha`

### ENV Değişkenleri

```
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=
RECAPTCHA_SCORE_THRESHOLD=0.5
```

### Kullanım (Backend)

```php
$response = Http::post('https://www.google.com/recaptcha/api/siteverify', [
    'secret'   => config('recaptcha.secret_key'),
    'response' => $request->input('g-recaptcha-response'),
    'remoteip' => $request->ip(),
]);

if ($response->json('score') < config('recaptcha.threshold')) {
    abort(422, 'Bot aktivitesi tespit edildi.');
}
```

### Kullanım (Frontend)

```html
<script src="https://www.google.com/recaptcha/api.js?render={SITE_KEY}"></script>
<script>
grecaptcha.ready(function() {
    grecaptcha.execute('{SITE_KEY}', {action: 'submit'}).then(function(token) {
        document.getElementById('recaptcha-token').value = token;
    });
});
</script>
```

### Kapsam

reCAPTCHA uygulanacak formlar:
- Öğrenci E-Kayıt formu
- Bağış formu
- Mezun kayıt formu
- Üye giriş / kayıt formu

---

## 4. Rate Limiting

Aynı IP'den kısa sürede çok fazla istek gelirse otomatik engellenir.

### Laravel Konfigürasyonu

```php
// App\Providers\RouteServiceProvider
RateLimiter::for('form_submit', function (Request $request) {
    return Limit::perMinute(3)
        ->by($request->ip())
        ->response(function () {
            return response()->json([
                'message' => 'Çok fazla istek gönderildi. Lütfen bekleyin.'
            ], 429);
        });
});

RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

### Limitler

| Form / Endpoint | Limit | Süre |
|---|---|---|
| E-Kayıt formu | 3 istek | Dakikada |
| Bağış formu | 5 istek | Dakikada |
| Üye giriş | 5 istek | Dakikada |
| Mezun kayıt | 3 istek | Dakikada |
| Admin giriş | 5 istek | Dakikada |
| OTP gönderme | 3 istek | 10 dakikada |

---

## 5. IP Kara Liste

Saldırı yapan IP'ler manuel veya otomatik olarak engellenir.

### Tablo: `ip_kara_listesi`

| Alan | Tip | Açıklama |
|---|---|---|
| id | bigIncrements | — |
| ip_adresi | string(45) | IPv4 veya IPv6 |
| sebep | string(255) | Engelleme sebebi |
| otomatik | boolean | Sistem mi koydu, admin mi? |
| aktif | boolean | true |
| bitis_tarihi | timestamp | null ise kalıcı |
| created_at | timestamp | — |

### Middleware

```php
// App\Http\Middleware\CheckIpBlacklist
public function handle(Request $request, Closure $next)
{
    $blocked = IpKaraListesi::where('ip_adresi', $request->ip())
        ->where('aktif', true)
        ->where(function($q) {
            $q->whereNull('bitis_tarihi')
              ->orWhere('bitis_tarihi', '>', now());
        })->exists();

    if ($blocked) {
        abort(403, 'Erişim engellendi.');
    }

    return $next($request);
}
```

### Otomatik Kara Listeye Alma

```
Aynı IP'den 10 dakikada 20+ başarısız istek → otomatik kara listeye al (24 saat)
Admin panelinde IP kara listesi yönetimi:
  - Liste görüntüleme
  - Manuel IP ekleme/kaldırma
  - Otomatik eklenenler rozet ile işaretlenir
```

---

## 6. Admin Panel Güvenliği

### CSRF Koruması
Tüm POST/PUT/DELETE isteklerinde Laravel CSRF token zorunludur. Blade formlarında `@csrf` direktifi kullanılır.

### SQL Injection
Tüm sorgular Eloquent ORM veya Laravel Query Builder ile yazılır. Ham SQL (`DB::statement`) asla kullanıcı girdisiyle birleştirilmez.

### Dosya Yükleme Güvenliği
- Yüklenen dosyalar web root dışına kaydedilir veya DO Spaces'e gönderilir
- Dosya adları UUID ile yeniden adlandırılır — orijinal isim saklanmaz
- PHP uzantılı dosya yüklenemez
- Mime type kontrolü hem extension hem de gerçek dosya içeriğine göre yapılır (`finfo`)

### Session Güvenliği

```php
// config/session.php
'secure'    => true,   // Sadece HTTPS
'http_only' => true,   // JS erişimi engeli
'same_site' => 'lax',  // CSRF koruması
'lifetime'  => 4320,   // 72 saat (admin için)
```

### İki Faktörlü Kimlik Doğrulama (2FA) — Opsiyonel
Admin hesapları için TOTP tabanlı 2FA eklenebilir.
Paket: `pragmarx/google2fa-laravel`
Zorunlu değil — admin tercihine bırakılır.

---

## 7. HTTPS ve Header Güvenliği

### Zorunlu HTTPS
Tüm HTTP istekleri HTTPS'e yönlendirilir:

```php
// AppServiceProvider
if (config('app.env') === 'production') {
    URL::forceScheme('https');
}
```

### Güvenlik Headerları (Nginx)

```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com; img-src 'self' data: https://cdn.kestanepazari.org.tr;";
```

---

## 8. Loglama ve İzleme

Tüm güvenlik olayları `Loglar` modülüne yazılır (bkz. loglar.md):

| Olay | Log Kaydı |
|---|---|
| Başarısız giriş (5+) | IP + denenen e-posta + tarih |
| Bot tespiti (reCAPTCHA) | IP + form adı + skor |
| Rate limit aşımı | IP + endpoint + tarih |
| IP kara listesine ekleme | IP + sebep + otomatik/manuel |
| Honeypot tetiklenmesi | IP + form adı + tarih |
| Şüpheli input (XSS/inject) | IP + alan + temizlenen içerik |

---

## Uygulama Önceliği

| Önlem | Öncelik | Ne Zaman |
|---|---|---|
| Input validasyon | 🔴 Kritik | Her form yazılırken |
| CSRF | 🔴 Kritik | Laravel varsayılan — her zaman açık |
| Honeypot | 🟠 Yüksek | Frontend formlar tamamlanınca |
| reCAPTCHA v3 | 🟠 Yüksek | Frontend formlar tamamlanınca |
| Rate limiting | 🟠 Yüksek | Route tanımlanırken |
| IP kara liste | 🟡 Orta | Panel hazır olduktan sonra |
| Güvenlik headerları | 🟡 Orta | Sunucu yapılandırmasında |
| 2FA | 🟢 Düşük | İsteğe bağlı |
