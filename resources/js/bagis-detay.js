const TURLER = {
    zekat: { baslik: 'Zekat', aciklama: 'Zekatınızı ihtiyaç sahibi öğrencilere ulaştırıyoruz.', tip: 'normal' },
    normal: { baslik: 'Normal Bağış', aciklama: 'İstediğiniz tutar için bağış yapabilirsiniz.', tip: 'normal' },
    kucukbas: { baslik: 'Küçükbaş Kurban', aciklama: 'Tek kişi için adak/kurban vekaletinizi teslim alıyoruz.', tip: 'kucukbas' },
    buyukbas: { baslik: 'Büyükbaş Kurban', aciklama: '1-7 hisse arası büyükbaş kurban vekaleti.', tip: 'buyukbas' },
    fitre: { baslik: 'Fitre', aciklama: 'Ramazan sonu fitre zekâtınızı güvenle iletiyoruz.', tip: 'normal' },
};

const slugToTur = {
    zekat: 'zekat',
    fitre: 'fitre',
    fidye: 'normal',
    'burs-destegi': 'normal',
    genel: 'normal',
    'genel-bagis': 'normal',
    duzenli: 'normal',
    adak: 'kucukbas',
    'kucukbas-kurban': 'kucukbas',
    kurban: 'buyukbas',
    'buyukbas-kurban-hissesi': 'buyukbas',
};

let aktifTur = 'zekat';
let aktifTutar = 100;
let hisseSayisi = 1;
let kopyalaOn = false;

function bagisFormunuAl() {
    return document.getElementById('bagis-form');
}

function mevcutTurBilgisiniAl(tur) {
    const form = bagisFormunuAl();
    const initSlug = form?.dataset.slug || 'zekat';
    const initTur = slugToTur[initSlug] || 'zekat';
    const varsayilanTur = TURLER[tur] || TURLER.zekat;

    if (tur === initTur && form?.dataset.baslik) {
        return {
            ...varsayilanTur,
            baslik: form.dataset.baslik,
            aciklama: form.dataset.aciklama || varsayilanTur.aciklama,
        };
    }

    return varsayilanTur;
}

function setTur(tur) {
    if (!TURLER[tur]) {
        return;
    }

    aktifTur = tur;
    const t = mevcutTurBilgisiniAl(tur);

    const elBaslik = document.getElementById('tur-baslik');
    const elAciklama = document.getElementById('tur-aciklama');
    const elBreadcrumb = document.getElementById('breadcrumb-tur');

    if (elBaslik) {
        elBaslik.textContent = t.baslik;
    }

    if (elAciklama) {
        elAciklama.textContent = t.aciklama;
    }

    if (elBreadcrumb) {
        elBreadcrumb.textContent = t.baslik;
    }

    document.querySelectorAll('.tur-tab').forEach((tab) => {
        tab.classList.toggle('active', tab.dataset.tur === tur);
    });

    const panelZekat = document.getElementById('panel-zekat-normal');
    const panelKucuk = document.getElementById('panel-kucukbas');
    const panelBuyuk = document.getElementById('panel-buyukbas');
    const kopyalaWrap = document.getElementById('kopyala-toggle-wrap');
    const baskasiSecili = document.getElementById('radio-baskasi')?.classList.contains('selected');

    if (panelZekat) {
        panelZekat.style.display = t.tip === 'normal' ? '' : 'none';
    }

    if (panelKucuk) {
        panelKucuk.style.display = t.tip === 'kucukbas' ? '' : 'none';
    }

    if (panelBuyuk) {
        panelBuyuk.style.display = t.tip === 'buyukbas' ? '' : 'none';
    }

    if (t.tip === 'buyukbas') {
        if (hisseSayisi < 1) {
            hisseSayisi = 1;
        }

        renderHissedarlar(hisseSayisi);
    }

    if (kopyalaWrap) {
        kopyalaWrap.style.display = t.tip !== 'normal' || baskasiSecili ? '' : 'none';
    }

    updateSepet();
}

function renderHissedarlar(n) {
    const liste = document.getElementById('hissedar-listesi');

    if (!liste) {
        return;
    }

    liste.innerHTML = '';

    for (let i = 1; i <= n; i += 1) {
        liste.innerHTML += `
      <div class="kisi-kart">
        <div class="kisi-kart-header">
          <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;font-weight:600;color:#162E4B;display:flex;align-items:center;gap:6px;">
            <span style="width:24px;height:24px;border-radius:50%;background:#162E4B;color:#EBDFB5;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${i}</span>
            ${i === 1 ? 'Birinci Hissedar' : `${i}. Hissedar`}
          </p>
          <span class="badge-tur" style="background:rgba(22,46,75,.08);color:#62868D;">Hisse ${i}/7</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div class="form-group">
            <label class="form-label">Ad Soyad <span style="color:#E95925;">*</span></label>
            <input type="text"
                   name="hissedarlar[${i - 1}][ad_soyad]"
                   class="form-input"
                   autocomplete="name"
                   placeholder="Ad Soyad" />
          </div>
          <div class="form-group">
            <label class="form-label">Telefon</label>
            <input type="tel"
                   name="hissedarlar[${i - 1}][telefon]"
                   class="form-input"
                   autocomplete="tel"
                   inputmode="numeric"
                   pattern="[0-9]*"
                   placeholder="05XX XXX XX XX" />
          </div>
          <div class="form-group">
            <label class="form-label">E-posta</label>
            <input type="email"
                   name="hissedarlar[${i - 1}][eposta]"
                   class="form-input"
                   autocomplete="email"
                   placeholder="ornek@mail.com" />
          </div>
          <div class="form-group">
            <label class="form-label">TC Kimlik <span style="color:#62868D;font-weight:400;">(opsiyonel)</span></label>
            <input type="text"
                   name="hissedarlar[${i - 1}][tc_kimlik]"
                   class="form-input"
                   inputmode="numeric"
                   maxlength="11"
                   placeholder="XXXXXXXXXXX" />
          </div>
        </div>
      </div>`;
    }
}

function setHisse(n) {
    hisseSayisi = Math.min(Math.max(parseInt(n, 10) || 1, 1), 7);

    document.querySelectorAll('#hisse-sayisi-btns button').forEach((buton) => {
        buton.classList.toggle('selected', parseInt(buton.dataset.hisse, 10) === hisseSayisi);
    });

    renderHissedarlar(hisseSayisi);
    updateSepet();

    if (kopyalaOn) {
        kopyaBilgileriniUygula();
    }
}

function selectRadio(tip) {
    const radioKendi = document.getElementById('radio-kendi');
    const radioBaskasi = document.getElementById('radio-baskasi');
    const vekaletForm = document.getElementById('vekalet-form');
    const kopyalaWrap = document.getElementById('kopyala-toggle-wrap');

    if (radioKendi) {
        radioKendi.classList.toggle('selected', tip === 'kendi');
    }

    if (radioBaskasi) {
        radioBaskasi.classList.toggle('selected', tip === 'baskasi');
    }

    if (vekaletForm) {
        vekaletForm.style.display = tip === 'baskasi' ? '' : 'none';
    }

    if (kopyalaWrap) {
        kopyalaWrap.style.display = tip === 'baskasi' || aktifTur === 'kucukbas' || aktifTur === 'buyukbas' ? '' : 'none';
    }

    updateSepet();

    if (kopyalaOn) {
        kopyaBilgileriniUygula();
    }
}

function alanDegeriniAl(seciciler = []) {
    for (const secici of seciciler) {
        const alan = document.querySelector(secici);

        if (alan?.value) {
            return alan.value;
        }
    }

    return '';
}

function kopyaBilgileriniUygula() {
    if (!kopyalaOn) {
        return;
    }

    const adSoyad = aktifTur === 'kucukbas'
        ? alanDegeriniAl(['input[name="kucukbas_ad_soyad"]'])
        : aktifTur === 'buyukbas'
            ? alanDegeriniAl(['input[name="hissedarlar[0][ad_soyad]"]'])
            : alanDegeriniAl(['input[name="sahip_ad_soyad"]']);

    const telefon = aktifTur === 'kucukbas'
        ? alanDegeriniAl(['input[name="kucukbas_telefon"]'])
        : aktifTur === 'buyukbas'
            ? alanDegeriniAl(['input[name="hissedarlar[0][telefon]"]'])
            : alanDegeriniAl(['input[name="sahip_telefon"]']);

    const eposta = aktifTur === 'kucukbas'
        ? alanDegeriniAl(['input[name="kucukbas_eposta"]'])
        : aktifTur === 'buyukbas'
            ? alanDegeriniAl(['input[name="hissedarlar[0][eposta]"]'])
            : '';

    const tcKimlik = aktifTur === 'kucukbas'
        ? alanDegeriniAl(['input[name="kucukbas_tc"]'])
        : aktifTur === 'buyukbas'
            ? alanDegeriniAl(['input[name="hissedarlar[0][tc_kimlik]"]'])
            : '';

    const odeyenAd = document.getElementById('odeyen-ad');
    const odeyenTel = document.getElementById('odeyen-tel');
    const odeyenEmail = document.getElementById('odeyen-email');
    const odeyenTc = document.getElementById('odeyen-tc');

    if (odeyenAd && adSoyad) {
        odeyenAd.value = adSoyad;
    }

    if (odeyenTel && telefon) {
        odeyenTel.value = telefon;
    }

    if (odeyenEmail && eposta) {
        odeyenEmail.value = eposta;
    }

    if (odeyenTc && tcKimlik) {
        odeyenTc.value = tcKimlik;
    }
}

function toggleKopyala() {
    kopyalaOn = !kopyalaOn;
    const track = document.getElementById('kopyala-track');

    if (track) {
        track.classList.toggle('on', kopyalaOn);
    }

    if (kopyalaOn) {
        kopyaBilgileriniUygula();
    }
}

function selectOdeme(tip) {
    document.querySelectorAll('[id^="odeme-"]').forEach((element) => element.classList.remove('selected'));
    const seciliOdeme = document.getElementById(`odeme-${tip}`);

    if (seciliOdeme) {
        seciliOdeme.classList.add('selected');
    }
}

function seciliSahipMetni() {
    if (aktifTur === 'buyukbas') {
        return `${hisseSayisi} hisse`;
    }

    return document.getElementById('radio-baskasi')?.classList.contains('selected')
        ? 'Başkası adına'
        : 'Kendi adıma';
}

function updateSepet() {
    const t = mevcutTurBilgisiniAl(aktifTur);
    const tutar = aktifTutar || 0;
    const carpan = aktifTur === 'buyukbas' ? hisseSayisi : 1;
    const toplam = tutar * carpan;

    const elTutar = document.getElementById('sepet-tutar-goster');
    const elToplam = document.getElementById('sepet-toplam');
    const elIcerik = document.getElementById('sepet-icerik');

    if (elTutar) {
        elTutar.textContent = `₺${tutar.toLocaleString('tr-TR')}`;
    }

    if (elToplam) {
        elToplam.textContent = `₺${toplam.toLocaleString('tr-TR')}`;
    }

    const hisseText = aktifTur === 'buyukbas' ? ` · ${hisseSayisi} hisse` : '';

    if (elIcerik) {
        elIcerik.innerHTML = `
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid rgba(22,46,75,.07);">
        <div>
          <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;font-weight:600;color:#162E4B;">${t.baslik}${hisseText}</p>
          <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;margin-top:2px;">${aktifTur === 'buyukbas' ? `Hisse başı: ₺${tutar.toLocaleString('tr-TR')}` : seciliSahipMetni()}</p>
        </div>
        <p style="font-family:'Libre Baskerville',serif;font-weight:700;font-size:16px;color:#162E4B;white-space:nowrap;">₺${toplam.toLocaleString('tr-TR')}</p>
      </div>`;
    }
}

function formVerisiniTopla() {
    const form = bagisFormunuAl();
    const formVerisi = {};

    if (!form) {
        return formVerisi;
    }

    form.querySelectorAll('input[name], textarea[name], select[name]').forEach((alan) => {
        if (!alan.name) {
            return;
        }

        formVerisi[alan.name] = alan.value ?? '';
    });

    formVerisi.aktif_tur = aktifTur;
    formVerisi.hisse_sayisi = hisseSayisi;
    formVerisi.kopyala_on = kopyalaOn;

    return formVerisi;
}

function sepetMesajiGoster(mesaj, tip = 'success') {
    const mesajAlani = document.getElementById('sepet-mesaj');

    if (!mesajAlani) {
        window.alert(mesaj);
        return;
    }

    mesajAlani.style.display = 'block';
    mesajAlani.textContent = mesaj;
    mesajAlani.classList.remove('border-red-200', 'bg-red-50', 'text-red-700', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-700');

    if (tip === 'error') {
        mesajAlani.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
    } else {
        mesajAlani.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
    }
}

async function sepeteEkle() {
    const form = bagisFormunuAl();

    if (!form) {
        return;
    }

    const sepetUrl = form.dataset.sepetUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const buton = document.getElementById('sepete-ekle-btn');

    if (!sepetUrl || !csrfToken) {
        sepetMesajiGoster('Sepet bağlantısı şu anda hazır değil.', 'error');
        return;
    }

    try {
        if (buton) {
            buton.disabled = true;
            buton.classList.add('opacity-70');
        }

        const response = await fetch(sepetUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                slug: form.dataset.slug || 'zekat',
                tutar: aktifTutar || 0,
                adet: aktifTur === 'buyukbas' ? hisseSayisi : 1,
                sahip_tipi: document.getElementById('radio-baskasi')?.classList.contains('selected') ? 'baskasi' : 'kendi',
                form_verisi: formVerisiniTopla(),
            }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.message || 'Bağış sepetinize eklenemedi.');
        }

        const badge = document.getElementById('sepet-badge');
        const adet = document.getElementById('sepet-adet');

        if (badge) {
            badge.textContent = data.sepet_adet ?? `${(parseInt(badge.textContent, 10) || 0) + 1}`;
            badge.style.display = 'flex';
            badge.style.transform = 'scale(1.4)';
            window.setTimeout(() => {
                badge.style.transform = 'scale(1)';
            }, 200);
        }

        if (adet && data.sepet_adet) {
            adet.textContent = `${data.sepet_adet} kalem`;
        }

        sepetMesajiGoster(data.message || 'Bağış sepetinize eklendi.');
    } catch (error) {
        sepetMesajiGoster(error.message || 'Sepete ekleme sırasında bir hata oluştu.', 'error');
    } finally {
        if (buton) {
            buton.disabled = false;
            buton.classList.remove('opacity-70');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.tutar-btn[data-tutar]').forEach((buton) => {
        buton.addEventListener('click', () => {
            document.querySelectorAll('.tutar-btn[data-tutar]').forEach((digerButon) => digerButon.classList.remove('selected'));
            buton.classList.add('selected');
            aktifTutar = parseInt(buton.dataset.tutar, 10) || 0;

            const manuelInput = document.getElementById('tutar-manuel');

            if (manuelInput) {
                manuelInput.value = '';
            }

            updateSepet();
        });
    });

    const manuelInput = document.getElementById('tutar-manuel');

    if (manuelInput) {
        manuelInput.addEventListener('input', function () {
            if (this.value) {
                document.querySelectorAll('.tutar-btn[data-tutar]').forEach((buton) => buton.classList.remove('selected'));
                aktifTutar = parseInt(this.value, 10) || 0;
                updateSepet();
            }
        });
    }

    document.querySelectorAll('.tur-tab').forEach((tab) => {
        tab.addEventListener('click', () => setTur(tab.dataset.tur));
    });

    document.addEventListener('input', (event) => {
        const isim = event.target?.name || '';

        if (kopyalaOn && (isim.startsWith('sahip_') || isim.startsWith('kucukbas_') || isim.startsWith('hissedarlar['))) {
            kopyaBilgileriniUygula();
        }
    });

    const form = bagisFormunuAl();
    const initSlug = form?.dataset.slug || 'zekat';
    const initTur = slugToTur[initSlug] || form?.dataset.initTur || 'zekat';
    const ilkTutar = document.querySelector('.tutar-btn[data-tutar].selected');

    if (ilkTutar) {
        aktifTutar = parseInt(ilkTutar.dataset.tutar, 10) || 100;
    }

    setTur(initTur);
    updateSepet();
});

window.setTur = setTur;
window.setHisse = setHisse;
window.selectRadio = selectRadio;
window.toggleKopyala = toggleKopyala;
window.selectOdeme = selectOdeme;
window.sepeteEkle = sepeteEkle;
window.updateSepet = updateSepet;
