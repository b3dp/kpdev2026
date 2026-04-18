#!/bin/bash

set -euo pipefail

PROJE_DIZINI="/var/www/vhosts/2026.kestanepazari.org.tr/httpdocs"
LOG_DOSYASI="$PROJE_DIZINI/storage/logs/haber_ai_arka_plan.log"
DURUM_DOSYASI="$PROJE_DIZINI/storage/logs/haber_ai_arka_plan.status"

hata_yaz() {
    local cikis_kodu=$?
    printf '[%s] HATA cikis_kodu=%s\n' "$(date -Is)" "$cikis_kodu" >> "$LOG_DOSYASI"
    echo "hata" > "$DURUM_DOSYASI"
    exit "$cikis_kodu"
}

trap hata_yaz ERR

calistir() {
    local artisan_komutu="$1"

    printf '[%s] KOMUT %s\n' "$(date -Is)" "$artisan_komutu" >> "$LOG_DOSYASI"
    su -s /bin/bash b3dp2026 -c "cd /var/www/vhosts/2026.kestanepazari.org.tr/httpdocs && /opt/plesk/php/8.2/bin/php artisan $artisan_komutu" >> "$LOG_DOSYASI" 2>&1
}

mkdir -p "$PROJE_DIZINI/storage/logs"

echo "calisiyor" > "$DURUM_DOSYASI"
printf '[%s] BASLADI\n' "$(date -Is)" > "$LOG_DOSYASI"

printf '[%s] ASAMA 1 basladi: tum haberlerde kisi/kurum/kategori eslestirmesi\n' "$(date -Is)" >> "$LOG_DOSYASI"
calistir "haber:ai-toplu-isle --sadece-eslestirme"
printf '[%s] ASAMA 1 tamamlandi\n' "$(date -Is)" >> "$LOG_DOSYASI"

printf '[%s] ASAMA 2 basladi: 2026 haberlerinde sadece AI revizyon ozet/seo/meta\n' "$(date -Is)" >> "$LOG_DOSYASI"
calistir "haber:ai-toplu-isle --yil=2026 --sadece-seo-ozet"
printf '[%s] ASAMA 2 tamamlandi\n' "$(date -Is)" >> "$LOG_DOSYASI"

printf '[%s] TAMAMLANDI\n' "$(date -Is)" >> "$LOG_DOSYASI"
echo "tamamlandi" > "$DURUM_DOSYASI"