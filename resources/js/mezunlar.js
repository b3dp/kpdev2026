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

    const girisFormu = document.getElementById('mezun-giris-formu');
    const otpFormu = document.getElementById('mezun-otp-formu');
    const otpModal = document.getElementById('mezun-otp-modal');
    const otpKapat = document.getElementById('mezun-otp-kapat');

    const butonYukleniyorYap = (buton, yukleniyor, normalYazi, yukleniyorYazisi) => {
        if (!buton) {
            return;
        }

        buton.disabled = yukleniyor;
        buton.textContent = yukleniyor ? yukleniyorYazisi : normalYazi;
        buton.classList.toggle('opacity-70', yukleniyor);
        buton.classList.toggle('cursor-not-allowed', yukleniyor);
    };

    otpKapat?.addEventListener('click', () => {
        otpModal?.classList.add('hidden');
    });

    girisFormu?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitButon = document.getElementById('mezun-giris-submit');
        const hataKutusu = document.getElementById('mezun-giris-hata-alani');
        const hataMesaji = document.getElementById('mezun-giris-hata-mesaji');
        const recaptchaInput = document.getElementById('mezun-recaptcha-response');

        butonYukleniyorYap(submitButon, true, 'OTP ile Giriş Yap', 'Gönderiliyor...');
        hataKutusu?.classList.add('hidden');

        try {
            if (typeof grecaptcha !== 'undefined' && recaptchaInput && recaptchaInput.dataset.sitekey) {
                const token = await grecaptcha.execute(recaptchaInput.dataset.sitekey, {
                    action: 'giris',
                });

                if (token) {
                    recaptchaInput.value = token;
                }
            }

            const response = await fetch(girisFormu.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': girisFormu.querySelector('[name="_token"]')?.value || '',
                    'Accept': 'application/json',
                },
                body: new FormData(girisFormu),
            });

            const sonuc = await response.json().catch(() => ({}));

            if (!response.ok) {
                const ilkHata = Object.values(sonuc.errors || {}).flat()[0] || sonuc.message || 'Giriş sırasında bir hata oluştu.';

                if (hataMesaji) {
                    hataMesaji.textContent = ilkHata;
                }
                hataKutusu?.classList.remove('hidden');
                return;
            }

            if (sonuc.step === 'otp') {
                otpModal?.classList.remove('hidden');
                return;
            }

            if (sonuc.redirect) {
                window.location.href = sonuc.redirect;
            }
        } catch (error) {
            if (hataMesaji) {
                hataMesaji.textContent = 'Ağ bağlantısı sırasında bir hata oluştu. Lütfen tekrar deneyin.';
            }
            hataKutusu?.classList.remove('hidden');
        } finally {
            butonYukleniyorYap(submitButon, false, 'OTP ile Giriş Yap', 'Gönderiliyor...');
        }
    });

    otpFormu?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitButon = document.getElementById('mezun-otp-submit');
        const hataKutusu = document.getElementById('mezun-otp-hata-alani');
        const hataMesaji = document.getElementById('mezun-otp-hata-mesaji');

        butonYukleniyorYap(submitButon, true, 'Kodu Doğrula', 'Doğrulanıyor...');
        hataKutusu?.classList.add('hidden');

        try {
            const response = await fetch(girisFormu?.dataset.otpUrl || '/giris/otp', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': otpFormu.querySelector('[name="_token"]')?.value || '',
                    'Accept': 'application/json',
                },
                body: new FormData(otpFormu),
            });

            const sonuc = await response.json().catch(() => ({}));

            if (!response.ok) {
                const ilkHata = Object.values(sonuc.errors || {}).flat()[0] || sonuc.message || 'Kod doğrulanamadı.';

                if (hataMesaji) {
                    hataMesaji.textContent = ilkHata;
                }
                hataKutusu?.classList.remove('hidden');
                return;
            }

            if (sonuc.redirect) {
                window.location.href = sonuc.redirect;
            }
        } catch (error) {
            if (hataMesaji) {
                hataMesaji.textContent = 'Ağ bağlantısı sırasında bir hata oluştu. Lütfen tekrar deneyin.';
            }
            hataKutusu?.classList.remove('hidden');
        } finally {
            butonYukleniyorYap(submitButon, false, 'Kodu Doğrula', 'Doğrulanıyor...');
        }
    });
});
