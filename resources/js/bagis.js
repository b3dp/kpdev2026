document.addEventListener('DOMContentLoaded', () => {
    const tutarGrid = document.getElementById('tutar-grid');
    const baslaMetni = document.getElementById('aylik-basla-text');

    if (!tutarGrid || !baslaMetni) {
        return;
    }

    const tutarButonlari = tutarGrid.querySelectorAll('.tutar-btn');

    if (!tutarButonlari.length) {
        return;
    }

    const aktifSiniflar = ['bg-white', 'border-white', 'text-orange-cta', 'border-2', 'font-bold'];
    const pasifSiniflar = ['bg-white/12', 'border-white/30', 'text-white', 'border', 'font-semibold'];

    tutarButonlari.forEach((buton) => {
        buton.addEventListener('click', () => {
            tutarButonlari.forEach((digerButon) => {
                digerButon.classList.remove(...aktifSiniflar);
                digerButon.classList.add(...pasifSiniflar);
            });

            buton.classList.remove(...pasifSiniflar);
            buton.classList.add(...aktifSiniflar);

            if (baslaMetni) {
                baslaMetni.textContent = `Aylık ₺${buton.dataset.tutar} ile Başla`;
            }
        });
    });
});
