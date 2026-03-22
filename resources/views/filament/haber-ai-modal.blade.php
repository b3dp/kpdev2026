<div
    x-data="{
        yuzde: 0,
        adim: 'Hazır',
        tamamlandi: false,
        islemDevamEdiyor: false,
        hata: '',
        hataDetay: '',
        pollTimer: null,
        csrfToken() {
            return document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content')
                ?? document.querySelector('[name=\'_token\']')?.value
                ?? '';
        },
        async baslat() {
            this.hata = '';
            this.hataDetay = '';
            this.islemDevamEdiyor = true;
            this.adim = 'AI işlemi başlatılıyor...';

            this.baslatPolling();

            try {
                const response = await fetch('/yonetim/haberler/{{ (int) $haberId }}/ai-baslat', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Accept': 'application/json',
                    },
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (_) {
                    data = { message: 'Sunucudan JSON cevap alınamadı.' };
                }

                if (!response.ok) {
                    this.hata = data?.message ?? 'AI işlemi başlatılırken hata oluştu.';
                    this.hataDetay = data?.detay_rapor ?? '';
                    this.pollBitir();
                    return;
                }

                await this.durumKontrolEt();
            } catch (error) {
                this.hata = 'İstek gönderilirken ağ hatası oluştu.';
                this.hataDetay = error?.message ?? '';
                this.pollBitir();
            } finally {
                this.islemDevamEdiyor = false;
            }
        },
        async durumKontrolEt() {
            try {
                const response = await fetch('/yonetim/haberler/{{ (int) $haberId }}/ai-durum', {
                    headers: { 'Accept': 'application/json' },
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (_) {
                    return;
                }

                if (!response.ok) {
                    this.hata = data?.message ?? 'Durum sorgulanırken hata oluştu.';
                    this.hataDetay = data?.detay_rapor ?? '';
                    return;
                }

                this.yuzde = data.yuzde ?? 0;
                this.adim = data.adim ?? 'Hazır';
                this.tamamlandi = Boolean(data.tamamlandi);

                if (this.tamamlandi) {
                    this.pollBitir();
                    window.setTimeout(() => window.location.reload(), 1200);
                }
            } catch (error) {
                this.hata = 'Durum kontrolünde ağ hatası oluştu.';
                this.hataDetay = error?.message ?? '';
            }
        },
        baslatPolling() {
            this.pollBitir();
            this.pollTimer = window.setInterval(() => this.durumKontrolEt(), 1200);
        },
        pollBitir() {
            if (this.pollTimer) {
                window.clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },
    }"
    x-init="durumKontrolEt()"
    class="space-y-4"
>
    <div class="text-sm text-gray-600" x-text="adim"></div>

    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
        <div class="bg-blue-600 h-3 transition-all duration-500" :style="`width: ${yuzde}%`"></div>
    </div>

    <div class="text-xs text-gray-500" x-text="`${yuzde}%`"></div>

    <div class="text-sm text-red-600" x-show="hata" x-text="hata"></div>
    <pre class="text-xs bg-red-50 border border-red-200 text-red-700 p-3 rounded-md overflow-auto" x-show="hataDetay" x-text="hataDetay"></pre>

    <div class="flex gap-2">
        <button
            type="button"
            class="px-3 py-2 bg-blue-600 text-white rounded-md text-sm"
            :disabled="islemDevamEdiyor || tamamlandi"
            @click="baslat()"
        >
            AI İşlemlerini Başlat
        </button>

        <button
            type="button"
            class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md text-sm"
            @click="durumKontrolEt()"
        >
            Durumu Yenile
        </button>
    </div>

    <div class="text-sm text-green-600" x-show="tamamlandi">
        AI tamamlandı, içerik kaydedildi ve onaya gönderildi.
    </div>
</div>
