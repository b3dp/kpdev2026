<div x-data="haberAiIslem({{ (int) $haberId }})" class="space-y-4">
    <div class="text-sm text-gray-600" x-text="adim"></div>

    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
        <div class="bg-blue-600 h-3 transition-all duration-500" :style="`width: ${yuzde}%`"></div>
    </div>

    <div class="text-xs text-gray-500" x-text="`${yuzde}%`"></div>

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

<script>
    function haberAiIslem(haberId) {
        return {
            yuzde: 0,
            adim: 'Hazır',
            tamamlandi: false,
            islemDevamEdiyor: false,
            pollTimer: null,

            async baslat() {
                this.islemDevamEdiyor = true;
                this.adim = 'AI işlemi başlatılıyor...';

                this.baslatPolling();

                await fetch(`/yonetim/haberler/${haberId}/ai-baslat`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });

                await this.durumKontrolEt();

                this.islemDevamEdiyor = false;
            },

            async durumKontrolEt() {
                const response = await fetch(`/yonetim/haberler/${haberId}/ai-durum`, {
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                this.yuzde = data.yuzde ?? 0;
                this.adim = data.adim ?? 'Hazır';
                this.tamamlandi = Boolean(data.tamamlandi);

                if (this.tamamlandi) {
                    this.pollBitir();
                    window.setTimeout(() => window.location.reload(), 1200);
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
        }
    }
</script>
