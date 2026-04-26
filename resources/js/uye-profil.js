const profilKoku = document.querySelector('[data-uye-profil]');

if (profilKoku) {
    const sekmeler = Array.from(profilKoku.querySelectorAll('[data-profil-tab]'));
    const paneller = Array.from(profilKoku.querySelectorAll('[data-profil-panel]'));
    const cerezKoku = profilKoku.querySelector('[data-cerez-profil]');

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

    const cerezDurumunuGuncelle = () => {
        if (!cerezKoku || !window.kpCerez?.getPreferences) {
            return;
        }

        const tercihler = window.kpCerez.getPreferences();
        const analitikDurum = cerezKoku.querySelector('[data-cerez-durum="analitik"]');
        const pazarlamaDurum = cerezKoku.querySelector('[data-cerez-durum="pazarlama"]');
        const genelDurum = cerezKoku.querySelector('[data-cerez-genel-durum]');

        if (analitikDurum) {
            analitikDurum.textContent = tercihler.analitik ? 'Açık' : 'Kapalı';
            analitikDurum.className = tercihler.analitik ? 'uye-profil__pill uye-profil__pill--green' : 'uye-profil__pill uye-profil__pill--gray';
        }

        if (pazarlamaDurum) {
            pazarlamaDurum.textContent = tercihler.pazarlama ? 'Açık' : 'Kapalı';
            pazarlamaDurum.className = tercihler.pazarlama ? 'uye-profil__pill uye-profil__pill--green' : 'uye-profil__pill uye-profil__pill--gray';
        }

        if (genelDurum) {
            genelDurum.textContent = tercihler.analitik || tercihler.pazarlama
                ? 'Tercihleriniz kaydedildi. İstediğiniz zaman güncelleyebilirsiniz.'
                : 'Şu anda yalnızca zorunlu çerezler aktif.';
        }
    };

    cerezDurumunuGuncelle();

    cerezKoku?.querySelector('[data-cerez-aksiyon="ac"]')?.addEventListener('click', () => {
        window.kpCerez?.openPreferences?.();
    });

    cerezKoku?.querySelector('[data-cerez-aksiyon="kabul"]')?.addEventListener('click', () => {
        window.kpCerez?.acceptAll?.();
    });

    cerezKoku?.querySelector('[data-cerez-aksiyon="reddet"]')?.addEventListener('click', () => {
        window.kpCerez?.rejectAll?.();
    });

    window.addEventListener('kp:cerez-guncellendi', cerezDurumunuGuncelle);

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
