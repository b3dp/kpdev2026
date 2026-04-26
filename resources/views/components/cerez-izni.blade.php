<div
    data-cerez-root
    data-ga4-measurement-id="{{ config('services.ga4.measurement_id') }}"
    data-google-ads-tag-id="{{ config('services.google_ads.tag_id') }}"
>
    <div class="cerez-backdrop" data-cerez-backdrop></div>

    <section class="cerez-banner" data-cerez-banner aria-label="Çerez tercih bildirimi">
        <div class="cerez-banner__icerik">
            <div>
                <h2 class="cerez-banner__baslik">Çerez Tercihleri</h2>
                <p class="cerez-banner__metin">
                    Sitemizde zorunlu çerezler her zaman çalışır. Analitik çerezleri ziyaret akışını,
                    reklam/pazarlama çerezleri ise kampanya ve dönüşüm performansını ölçmek için yalnızca
                    izninizle kullanırız. Ayrıntılar için
                    <a href="{{ route('kurumsal.show', ['slug' => 'cerez-politikasi']) }}">Çerez Politikası</a>
                    sayfasını inceleyebilirsiniz.
                </p>
            </div>

            <div class="cerez-banner__aksiyonlar">
                <button type="button" class="cerez-btn cerez-btn--altin" data-cerez-accept-all>Kabul Et</button>
                <button type="button" class="cerez-btn cerez-btn--beyaz" data-cerez-reject-all>Reddet</button>
                <button type="button" class="cerez-btn cerez-btn--cizgili" data-cerez-open>Seçenekler</button>
            </div>
        </div>
    </section>

    <section class="cerez-panel" data-cerez-panel aria-label="Çerez tercih merkezi" aria-modal="true" role="dialog">
        <div class="cerez-panel__ust">
            <div>
                <h3 class="cerez-panel__baslik">Gizlilik Tercih Merkezi</h3>
                <p class="cerez-panel__aciklama">
                    Zorunlu çerezler giriş, e-kayıt, bağış ve güvenlik akışları için gereklidir. Analitik ve
                    reklam/pazarlama tercihlerinizi aşağıdan ayrı ayrı yönetebilirsiniz.
                </p>
            </div>

            <button type="button" class="cerez-panel__kapat" data-cerez-close aria-label="Kapat">×</button>
        </div>

        <div class="cerez-kategoriler">
            <div class="cerez-kategori">
                <div>
                    <p class="cerez-kategori__baslik">Zorunlu Çerezler</p>
                    <p class="cerez-kategori__metin">Oturum, güvenlik, form koruması ve bağış sürecinin çalışması için gereklidir.</p>
                </div>

                <label class="cerez-switch" aria-label="Zorunlu çerezler her zaman açık">
                    <input type="checkbox" checked disabled>
                    <span></span>
                </label>
            </div>

            <div class="cerez-kategori">
                <div>
                    <p class="cerez-kategori__baslik">Analitik Çerezler</p>
                    <p class="cerez-kategori__metin">Sayfa görüntüleme, bağış adımı ve içerik performansını ölçmek için kullanılır.</p>
                </div>

                <label class="cerez-switch" aria-label="Analitik çerez tercihi">
                    <input type="checkbox" data-cerez-analitik>
                    <span></span>
                </label>
            </div>

            <div class="cerez-kategori">
                <div>
                    <p class="cerez-kategori__baslik">Reklam / Pazarlama Çerezleri</p>
                    <p class="cerez-kategori__metin">Kampanya dönüşümlerini ve reklam performansını izin verdiğinizde takip eder.</p>
                </div>

                <label class="cerez-switch" aria-label="Reklam ve pazarlama çerez tercihi">
                    <input type="checkbox" data-cerez-pazarlama>
                    <span></span>
                </label>
            </div>
        </div>

        <div class="cerez-panel__alt">
            <button type="button" class="cerez-btn cerez-btn--beyaz" data-cerez-reject-all>Hepsini Reddet</button>
            <button type="button" class="cerez-btn cerez-btn--cizgili" data-cerez-save>Seçimlerimi Kaydet</button>
            <button type="button" class="cerez-btn cerez-btn--altin" data-cerez-accept-all>Hepsini Kabul Et</button>
        </div>
    </section>
</div>