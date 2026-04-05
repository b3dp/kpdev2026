const profilKoku = document.querySelector('[data-uye-profil]');

if (profilKoku) {
    const sekmeler = Array.from(profilKoku.querySelectorAll('[data-profil-tab]'));
    const paneller = Array.from(profilKoku.querySelectorAll('[data-profil-panel]'));

    const sekmeDegistir = (hedef) => {
        sekmeler.forEach((sekme) => {
            const aktif = sekme.dataset.profilTab === hedef;
            sekme.classList.toggle('is-active', aktif);
            sekme.setAttribute('aria-selected', aktif ? 'true' : 'false');
        });

        paneller.forEach((panel) => {
            const aktif = panel.dataset.profilPanel === hedef;
            panel.classList.toggle('hidden', !aktif);
        });
    };

    sekmeler.forEach((sekme) => {
        sekme.addEventListener('click', () => sekmeDegistir(sekme.dataset.profilTab));
    });

    const ilkSekme = window.location.hash?.replace('#', '') || sekmeler[0]?.dataset.profilTab || 'bilgiler';
    sekmeDegistir(ilkSekme);

    const formlar = profilKoku.querySelectorAll('[data-ajax-form]');

    formlar.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const buton = form.querySelector('button[type="submit"]');
            const baslangicYazi = buton?.dataset.label || buton?.textContent || '';
            const basariKutusu = form.querySelector('[data-success-box]');

            form.querySelectorAll('[data-error-for]').forEach((alan) => {
                alan.textContent = '';
                alan.classList.add('hidden');
            });

            if (basariKutusu) {
                basariKutusu.classList.add('hidden');
            }

            if (buton) {
                buton.disabled = true;
                buton.textContent = 'Kaydediliyor...';
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': form.querySelector('[name="_token"]')?.value || '',
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });

                const sonuc = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && sonuc.errors) {
                        Object.entries(sonuc.errors).forEach(([alan, mesajlar]) => {
                            const hataAlani = form.querySelector(`[data-error-for="${alan}"]`);
                            if (hataAlani) {
                                hataAlani.textContent = mesajlar[0];
                                hataAlani.classList.remove('hidden');
                            }
                        });
                    } else {
                        window.alert(sonuc.message || 'İşlem sırasında bir hata oluştu.');
                    }

                    return;
                }

                if (basariKutusu) {
                    basariKutusu.textContent = sonuc.message || 'Bilgiler güncellendi.';
                    basariKutusu.classList.remove('hidden');
                }

                if (form.dataset.resetOnSuccess === 'true') {
                    form.reset();
                }
            } catch (error) {
                window.alert('Ağ bağlantısı sırasında bir hata oluştu.');
            } finally {
                if (buton) {
                    buton.disabled = false;
                    buton.textContent = baslangicYazi;
                }
            }
        });
    });
}
