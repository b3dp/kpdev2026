# CLAUDE.md

## Sunucu
- Path: /var/www/vhosts/2026.kestanepazari.org.tr/httpdocs
- PHP: /opt/plesk/php/8.2/bin/php
- Composer: /usr/local/bin/composer
- Node: nvm use 20
- DB: livekp_2026dev (şifre .env'de)
- Queue: database
- Git: https://github.com/b3dp/kpdev2026.git

## Temel Kurallar
- Tüm isimler Türkçe (tablo, model, method, değişken)
- Tablo: snake_case çoğul → Model: PascalCase tekil
- Foreign key: {tekil_tablo}_id
- Her tabloda: timestamps + softDeletes zorunlu
- Boolean default(false) zorunlu
- Para: decimal(10,2)
- Enum: PHP 8.1 native string backed, migration'da string
- Filament: light mode, blue-600, sortable(), defaultSort('created_at','desc')
- Widget'larda canView() zorunlu
- Blade'de {!! !!} yasak
- .env asla commit edilmez

## Çalışma Kuralları
- Tüm işlemleri otomatik yap, onay sorma
- Hata olursa dur ve açıkla
- Her task sonrası php artisan test çalıştır
- Testler geçince git add . && git commit -m "feat: ..." && git push yap
- Git push öncesi dur ve "PUSH HAZIR - onaylıyor musun?" diye sor

## Detaylar
- Teknik kurallar: docs/teknik-kurallar.md
- Paketler: docs/paketler.md
- Güvenlik: docs/guvenlik.md
- Her modül: docs/{modul}.md
