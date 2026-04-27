function sadeceRakam(deger) {
    return String(deger || '').replace(/\D+/g, '');
}

function kartNumarasiniMaskele(deger) {
    const rakamlar = sadeceRakam(deger).slice(0, 19);
    return rakamlar.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
}

function sonKullanmayiMaskele(deger) {
    const rakamlar = sadeceRakam(deger).slice(0, 6);

    if (rakamlar.length <= 2) {
        return rakamlar;
    }

    let ay = rakamlar.slice(0, 2);
    const aySayi = parseInt(ay, 10);

    if (!Number.isNaN(aySayi)) {
        if (aySayi <= 0) {
            ay = '01';
        } else if (aySayi > 12) {
            ay = '12';
        }
    }

    if (rakamlar.length <= 4) {
        return `${ay}/${rakamlar.slice(2, 4)}`;
    }

    return `${ay}/${rakamlar.slice(2, 6)}`;
}

function odemeMesajiGoster(mesaj, tip = 'error') {
    const alan = document.getElementById('odeme-mesaj');

    if (!alan) {
        window.alert(mesaj);
        return;
    }

    alan.classList.remove('hidden', 'border-red-200', 'bg-red-50', 'text-red-700', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
    alan.textContent = mesaj;

    if (tip === 'success') {
        alan.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
    } else {
        alan.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
    }
}

function formVerisiOku(form) {
    try {
        return JSON.parse(form?.dataset?.formVerisi || '{}');
    } catch (error) {
        return {};
    }
}

function sahipBilgisiniBul(formVerisi = {}) {
    const kucukbasAd = String(formVerisi.kucukbas_ad_soyad || '').trim();

    if (kucukbasAd) {
        return {
            ad: kucukbasAd,
            tc: String(formVerisi.kucukbas_tc || '').trim(),
            email: String(formVerisi.kucukbas_eposta || '').trim(),
            telefon: String(formVerisi.kucukbas_telefon || '').trim(),
        };
    }

    const hissedarAd = String(formVerisi['hissedarlar[0][ad_soyad]'] || '').trim();

    if (hissedarAd) {
        return {
            ad: hissedarAd,
            tc: String(formVerisi['hissedarlar[0][tc_kimlik]'] || '').trim(),
            email: String(formVerisi['hissedarlar[0][eposta]'] || '').trim(),
            telefon: String(formVerisi['hissedarlar[0][telefon]'] || '').trim(),
        };
    }

    const sahipAd = String(formVerisi.sahip_ad_soyad || '').trim();

    if (sahipAd) {
        return {
            ad: sahipAd,
            tc: '',
            email: '',
            telefon: String(formVerisi.sahip_telefon || '').trim(),
        };
    }

    return null;
}

function odemeAlanlariniDoldur(kaynak) {
    if (!kaynak) {
        return;
    }

    const odeyenAd = document.getElementById('odeyen-ad');
    const odeyenTc = document.getElementById('odeyen-tc');
    const odeyenEmail = document.getElementById('odeyen-email');
    const odeyenTel = document.getElementById('odeyen-tel');

    if (odeyenAd && kaynak.ad) {
        odeyenAd.value = kaynak.ad;
    }

    if (odeyenTc && kaynak.tc) {
        odeyenTc.value = kaynak.tc;
    }

    if (odeyenEmail && kaynak.email) {
        odeyenEmail.value = kaynak.email;
    }

    if (odeyenTel && kaynak.telefon) {
        odeyenTel.value = kaynak.telefon;
    }
}

async function odemeyiTamamlaOdemeSayfasinda() {
    const form = document.getElementById('bagis-odeme-form');

    if (!form) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const odemeUrl = form.dataset.odemeUrl;
    const buton = document.getElementById('odeme-tamamla-btn');

    if (!csrfToken || !odemeUrl) {
        odemeMesajiGoster('Ödeme bağlantısı hazır değil. Lütfen sayfayı yenileyin.');
        return;
    }

    let formVerisi = {};

    try {
        formVerisi = JSON.parse(form.dataset.formVerisi || '{}');
    } catch (error) {
        formVerisi = {};
    }

    formVerisi.odeyen_ad_soyad = (document.getElementById('odeyen-ad')?.value || '').trim();
    formVerisi.odeyen_tc = (document.getElementById('odeyen-tc')?.value || '').trim();
    formVerisi.odeyen_eposta = (document.getElementById('odeyen-email')?.value || '').trim();
    formVerisi.odeyen_telefon = (document.getElementById('odeyen-tel')?.value || '').trim();

    const payload = {
        slug: form.dataset.slug,
        tutar: Number(form.dataset.tutar || 0),
        adet: Number(form.dataset.adet || 1),
        sahip_tipi: form.dataset.sahipTipi || 'kendi',
        odeme_yontemi: 'albaraka',
        kart_no: (document.getElementById('kart-no')?.value || '').trim(),
        kart_sahibi: (document.getElementById('kart-sahibi')?.value || '').trim(),
        son_kullanma: (document.getElementById('kart-son-kullanma')?.value || '').trim(),
        cvv: (document.getElementById('kart-cvv')?.value || '').trim(),
        form_verisi: formVerisi,
    };

    try {
        if (buton) {
            buton.disabled = true;
            buton.classList.add('opacity-70');
        }

        const response = await fetch(odemeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json, text/html',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('text/html')) {
            const html = await response.text();
            document.open();
            document.write(html);
            document.close();
            return;
        }

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const ilkHata = data.errors ? Object.values(data.errors).flat()[0] : null;
            throw new Error(ilkHata || data.message || 'Ödeme işlemi tamamlanamadı.');
        }

        odemeMesajiGoster(data.message || 'Ödeme işlemi tamamlandı.', 'success');

        if (data.redirect_url) {
            window.setTimeout(() => {
                window.location.href = data.redirect_url;
            }, 500);
            return;
        }

        window.location.href = form.dataset.redirectUrl || '/bagis/tesekkur';
    } catch (error) {
        odemeMesajiGoster(error.message || 'Ödeme sırasında bir hata oluştu.');
    } finally {
        if (buton) {
            buton.disabled = false;
            buton.classList.remove('opacity-70');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('bagis-odeme-form');

    if (!form) {
        return;
    }

    const kartNoAlani = document.getElementById('kart-no');
    const sonKullanmaAlani = document.getElementById('kart-son-kullanma');
    const cvvAlani = document.getElementById('kart-cvv');
    const odemeButonu = document.getElementById('odeme-tamamla-btn');
    const kopyalaToggle = document.getElementById('kopyala-odeme-toggle');
    const sahipBilgisi = sahipBilgisiniBul(formVerisiOku(form));

    if (kopyalaToggle) {
        kopyalaToggle.disabled = !sahipBilgisi;
        if (!sahipBilgisi) {
            kopyalaToggle.checked = false;
        }

        kopyalaToggle.addEventListener('change', () => {
            if (kopyalaToggle.checked) {
                odemeAlanlariniDoldur(sahipBilgisi);
            }
        });
    }

    if (kartNoAlani) {
        kartNoAlani.addEventListener('input', () => {
            kartNoAlani.value = kartNumarasiniMaskele(kartNoAlani.value);
        });
    }

    if (sonKullanmaAlani) {
        sonKullanmaAlani.addEventListener('input', () => {
            sonKullanmaAlani.value = sonKullanmayiMaskele(sonKullanmaAlani.value);
        });
    }

    if (cvvAlani) {
        cvvAlani.addEventListener('input', () => {
            cvvAlani.value = sadeceRakam(cvvAlani.value).slice(0, 4);
        });
    }

    document.querySelectorAll('.test-kart-btn[data-kart-no]').forEach((buton) => {
        buton.addEventListener('click', () => {
            if (kartNoAlani) {
                kartNoAlani.value = kartNumarasiniMaskele(buton.dataset.kartNo || '');
            }

            const kartSahibiAlani = document.getElementById('kart-sahibi');
            const odeyenAd = (document.getElementById('odeyen-ad')?.value || '').trim();

            if (kartSahibiAlani && !kartSahibiAlani.value) {
                kartSahibiAlani.value = odeyenAd || 'Test Bağışçı';
            }

            if (sonKullanmaAlani && !sonKullanmaAlani.value) {
                sonKullanmaAlani.value = '12/30';
            }

            if (cvvAlani && !cvvAlani.value) {
                cvvAlani.value = '123';
            }
        });
    });

    if (odemeButonu) {
        odemeButonu.addEventListener('click', odemeyiTamamlaOdemeSayfasinda);
    }
});
