#!/bin/bash

set -euo pipefail

PROJE_DIZINI="/var/www/vhosts/2026.kestanepazari.org.tr/httpdocs"
LOG_DOSYASI="$PROJE_DIZINI/storage/logs/haber_ai_2026_createdat.log"
DURUM_DOSYASI="$PROJE_DIZINI/storage/logs/haber_ai_2026_createdat.status"

hata_yaz() {
    local cikis_kodu=$?
    printf '[%s] HATA cikis_kodu=%s\n' "$(date -Is)" "$cikis_kodu" >> "$LOG_DOSYASI"
    echo "hata" > "$DURUM_DOSYASI"
    exit "$cikis_kodu"
}

trap hata_yaz ERR

mkdir -p "$PROJE_DIZINI/storage/logs"

echo "calisiyor" > "$DURUM_DOSYASI"
printf '[%s] BASLADI\n' "$(date -Is)" > "$LOG_DOSYASI"

HABER_IDLERI=$(mysql -N -u livekp_2026dev -p3p@L5k0j5 livekp_2026dev -e "SET SESSION group_concat_max_len = 1000000; SELECT GROUP_CONCAT(id ORDER BY id SEPARATOR ',') FROM haberler WHERE YEAR(yayin_tarihi)=2026 AND created_at = yayin_tarihi")
HABER_SAYISI=$(mysql -N -u livekp_2026dev -p3p@L5k0j5 livekp_2026dev -e "SELECT COUNT(*) FROM haberler WHERE YEAR(yayin_tarihi)=2026 AND created_at = yayin_tarihi")

printf '[%s] HEDEF_SAYI %s\n' "$(date -Is)" "$HABER_SAYISI" >> "$LOG_DOSYASI"

if [[ -z "$HABER_IDLERI" ]]; then
    printf '[%s] ISLENECEK_HABER_YOK\n' "$(date -Is)" >> "$LOG_DOSYASI"
    echo "tamamlandi" > "$DURUM_DOSYASI"
    exit 0
fi

printf '[%s] KOMUT haber:ai-toplu-isle --haber-idleri=<%s id>\n' "$(date -Is)" "$HABER_SAYISI" >> "$LOG_DOSYASI"
su -s /bin/bash b3dp2026 -c "cd /var/www/vhosts/2026.kestanepazari.org.tr/httpdocs && /opt/plesk/php/8.2/bin/php artisan haber:ai-toplu-isle --haber-idleri='$HABER_IDLERI'" >> "$LOG_DOSYASI" 2>&1

printf '[%s] TAMAMLANDI\n' "$(date -Is)" >> "$LOG_DOSYASI"
echo "tamamlandi" > "$DURUM_DOSYASI"