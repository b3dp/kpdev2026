#!/bin/bash

set -euo pipefail

PROJE_DIZINI="/var/www/vhosts/2026.kestanepazari.org.tr/httpdocs"
LOG_DOSYASI="$PROJE_DIZINI/storage/logs/haber_ai_son_6_ay.log"
DURUM_DOSYASI="$PROJE_DIZINI/storage/logs/haber_ai_son_6_ay.status"

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

printf '[%s] ASAMA 1 basladi: son 6 ayda eklenmis bos yayin tarihleri tamamlanacak\n' "$(date -Is)" >> "$LOG_DOSYASI"
calistir "haber:yayin-tarihi-duzelt --son-ay=6"
printf '[%s] ASAMA 1 tamamlandi\n' "$(date -Is)" >> "$LOG_DOSYASI"

printf '[%s] ASAMA 2 basladi: yayin_tarihi son 6 ay icinde olan haberlerde AI revizyon uretilecek\n' "$(date -Is)" >> "$LOG_DOSYASI"
calistir "haber:ai-toplu-isle --son-ay=6 --sadece-seo-ozet"
printf '[%s] ASAMA 2 tamamlandi\n' "$(date -Is)" >> "$LOG_DOSYASI"

printf '[%s] TAMAMLANDI\n' "$(date -Is)" >> "$LOG_DOSYASI"
echo "tamamlandi" > "$DURUM_DOSYASI"