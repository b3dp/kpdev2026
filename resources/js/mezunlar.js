document.addEventListener('DOMContentLoaded', () => {
    window.switchPanel = (panel) => {
        const giris = panel === 'giris';
        document.getElementById('tab-giris')?.classList.toggle('active', giris);
        document.getElementById('tab-kayit')?.classList.toggle('active', !giris);
        document.getElementById('panel-giris')?.classList.toggle('hidden', !giris);
        document.getElementById('panel-giris')?.classList.toggle('flex', giris);
        document.getElementById('panel-kayit')?.classList.toggle('hidden', giris);
        document.getElementById('panel-kayit')?.classList.toggle('flex', !giris);
    };

    window.switchGirisTip = (tip) => {
        const eposta = tip === 'eposta';
        document.getElementById('tip-eposta')?.classList.toggle('active', eposta);
        document.getElementById('tip-telefon')?.classList.toggle('active', !eposta);
        document.getElementById('giris-eposta')?.classList.toggle('hidden', !eposta);
        document.getElementById('giris-eposta')?.classList.toggle('flex', eposta);
        document.getElementById('giris-telefon')?.classList.toggle('hidden', eposta);
        document.getElementById('giris-telefon')?.classList.toggle('flex', !eposta);
    };

    const baslangicPanel = document.querySelector('[data-baslangic-panel]')?.dataset.baslangicPanel;

    if (baslangicPanel === 'kayit') {
        window.switchPanel('kayit');
    }
});
