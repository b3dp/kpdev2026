document.addEventListener('DOMContentLoaded', () => {
    const dataEl = document.getElementById('iletisim-lokasyonlar');

    if (!dataEl) {
        return;
    }

    let lokasyonlar = [];

    try {
        lokasyonlar = JSON.parse(dataEl.textContent || '[]');
    } catch {
        lokasyonlar = [];
    }

    if (!Array.isArray(lokasyonlar) || lokasyonlar.length === 0) {
        return;
    }

    const sayfa = document.getElementById('iletisim-sayfa');
    const tabs = Array.from(document.querySelectorAll('[data-lokasyon-index]'));
    const labelEl = document.getElementById('lok-label');
    const titleEl = document.getElementById('lok-title');
    const adresEl = document.getElementById('lok-adres');
    const epostaEl = document.getElementById('lok-eposta');
    const directionsEl = document.getElementById('lok-directions');
    const mapContainer = document.getElementById('map-container');
    const lokasyonSelect = document.getElementById('form-lokasyon');

    const normalize = (value) => (value || '').toString().trim().toLocaleLowerCase('tr-TR');

    const haritaHtml = (lokasyon) => {
        if (lokasyon.harita_url) {
            return `<iframe src="${lokasyon.harita_url}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>`;
        }

        const baslik = lokasyon.baslik || lokasyon.ad || 'İletişim Noktası';

        return `
            <div class="map-placeholder">
                <div class="map-grid"></div>
                <div class="map-pin">
                    <svg width="36" height="44" viewBox="0 0 36 44" fill="none">
                        <path d="M18 0C8.059 0 0 8.059 0 18c0 13.5 18 26 18 26S36 31.5 36 18C36 8.059 27.941 0 18 0z" fill="#162E4B"/>
                        <circle cx="18" cy="18" r="7" fill="#B27829"/>
                    </svg>
                </div>
                <div class="map-pin-shadow"></div>
                <p class="map-name-label">${baslik}</p>
            </div>`;
    };

    const renderLokasyon = (index) => {
        const lokasyon = lokasyonlar[index];

        if (!lokasyon) {
            return;
        }

        tabs.forEach((tab) => {
            tab.classList.toggle('active', Number(tab.dataset.lokasyonIndex) === index);
        });

        if (labelEl) {
            labelEl.textContent = `${lokasyon.kod} — ${lokasyon.ad}`;
        }

        if (titleEl) {
            titleEl.textContent = lokasyon.baslik || lokasyon.ad;
        }

        if (adresEl) {
            adresEl.textContent = lokasyon.adres || '';
        }

        if (epostaEl) {
            const eposta = lokasyon.eposta || '';
            epostaEl.textContent = eposta;
            epostaEl.setAttribute('href', eposta ? `mailto:${eposta}` : '#');
        }

        if (directionsEl) {
            directionsEl.setAttribute('href', lokasyon.yon_tarifi_url || '#');
        }

        if (mapContainer) {
            mapContainer.innerHTML = haritaHtml(lokasyon);
        }

        if (lokasyonSelect && !lokasyonSelect.value) {
            lokasyonSelect.value = lokasyon.ad || '';
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            renderLokasyon(Number(tab.dataset.lokasyonIndex || 0));
        });
    });

    const seciliLokasyon = sayfa?.dataset.selectedLokasyon;
    let baslangicIndex = lokasyonlar.findIndex((lokasyon) => normalize(lokasyon.ad) === normalize(seciliLokasyon));

    if (baslangicIndex < 0) {
        baslangicIndex = 0;
    }

    renderLokasyon(baslangicIndex);
});
