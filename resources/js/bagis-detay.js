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

const turToSlug = {
    zekat: 'zekat',
    fitre: 'fitre',
    normal: 'genel-bagis',
    kucukbas: 'kucukbas-kurban',
    buyukbas: 'buyukbas-kurban-hissesi',
};

let aktifTur = 'zekat';
let aktifTutar = 100;
let aktifAdet = 1;
let hisseSayisi = 1;
let kopyalaOn = false;
let sepetKalemleri = [];
let bagisSayfaGoruntulendi = false;
let bagisTamamlandiEventGonderildi = false;

function consentEventGonder(eventName, params = {}, category = 'analitik') {
    if (window.kpCerez?.trackEvent) {
        window.kpCerez.trackEvent(eventName, params, category);
    }
}

function escapeHtml(deger = '') {
    return String(deger)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatPara(deger = 0) {
    return `₺${Number(deger || 0).toLocaleString('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function bagisFormunuAl() {
    return document.getElementById('bagis-form');
}

function adetModuAktifMi() {
    return bagisFormunuAl()?.dataset.adetModu === '1';
}

function sabitBirimFiyatAl() {
    return parseFloat(bagisFormunuAl()?.dataset.birimFiyat || '0') || 0;
}

function gecerliAdetAl(deger) {
    return Math.min(Math.max(parseInt(deger, 10) || 1, 1), 30);
}

function gecerliTutarAl() {
    return adetModuAktifMi() ? sabitBirimFiyatAl() : (aktifTutar || 0);
}

function gecerliCarpanAl() {
    if (aktifTur === 'buyukbas') {
        return hisseSayisi;
    }

    return adetModuAktifMi() ? aktifAdet : 1;
}

function aktifSlugAl() {
    const form = bagisFormunuAl();
    const initSlug = form?.dataset.slug || 'zekat';
    const initTur = slugToTur[initSlug] || 'zekat';

    if (aktifTur === initTur) {
        return initSlug;
    }

    return turToSlug[aktifTur] || initSlug;
}

function bagisSayfaGoruntulemesiniGonder() {
    if (bagisSayfaGoruntulendi) {
        return;
    }

    const form = bagisFormunuAl();

    if (!form) {
        return;
    }

    bagisSayfaGoruntulendi = true;

    const payload = {
        bagis_slug: form.dataset.slug || aktifSlugAl(),
        bagis_turu: form.dataset.baslik || mevcutTurBilgisiniAl(aktifTur)?.baslik || 'Bagis',
        page_type: 'bagis_detay',
    };

    consentEventGonder('bagis_sayfa_goruntuleme', payload, 'analitik');
    consentEventGonder('bagis_sayfa_goruntuleme', payload, 'pazarlama');
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
    document.querySelectorAll('.radio-opt[id^="odeme-"]').forEach((element) => element.classList.remove('selected'));
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

function sepetToplaminiHesapla() {
    return sepetKalemleri.reduce((toplam, satir) => toplam + Number(satir?.toplam || 0), 0);
}

function renderSepetOzeti() {
    const elIcerik = document.getElementById('sepet-icerik');
    const elToplam = document.getElementById('sepet-toplam');
    const elAdet = document.getElementById('sepet-adet');
    const badge = document.getElementById('sepet-badge');
    const seciliToplam = gecerliCarpanAl() * gecerliTutarAl();
    const sepetToplam = sepetToplaminiHesapla();

    if (elIcerik) {
        if (!sepetKalemleri.length) {
            elIcerik.innerHTML = `
                <div id="sepet-bos" style="border:1px dashed rgba(22,46,75,.12);border-radius:12px;padding:12px;background:#F7F5F0;font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;">
                    Henüz sepetinizde ekli bir bağış bulunmuyor. Seçtiğiniz bağışı “Sepete Ekle” ile burada biriktirebilirsiniz.
                </div>`;
        } else {
            elIcerik.innerHTML = sepetKalemleri.map((satir) => {
                const sahipMetni = satir?.sahip_tipi === 'baskasi' ? 'Başkası adına' : 'Kendi adıma';
                const adet = Number(satir?.adet || 1);
                const adetMetni = adet > 1 ? `${adet} adet / hisse` : '1 adet';

                return `
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;border:1px solid rgba(22,46,75,.08);border-radius:12px;padding:10px 12px;background:#fff;">
                        <div>
                            <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:700;color:#162E4B;">${escapeHtml(satir?.ad || 'Bağış Kalemi')}</p>
                            <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:11.5px;color:#62868D;margin-top:2px;">${adetMetni} · ${sahipMetni}</p>
                        </div>
                        <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                            <span style="font-family:'Libre Baskerville',serif;font-weight:700;font-size:15px;color:#162E4B;white-space:nowrap;">${formatPara(satir?.toplam || 0)}</span>
                            <button type="button" onclick="sepettenCikar(${Number(satir?.satir_id || 0)})" style="border:none;background:transparent;padding:0;font-family:'Plus Jakarta Sans',sans-serif;font-size:11px;font-weight:700;color:#dc2626;cursor:pointer;">Sil</button>
                        </div>
                    </div>`;
            }).join('');
        }
    }

    if (elToplam) {
        elToplam.textContent = formatPara(sepetKalemleri.length ? sepetToplam : seciliToplam);
    }

    if (elAdet) {
        elAdet.textContent = sepetKalemleri.length ? `${sepetKalemleri.length} kalem` : 'Sepet boş';
    }

    if (badge) {
        if (sepetKalemleri.length) {
            badge.textContent = `${sepetKalemleri.length}`;
            badge.style.display = 'flex';
        } else {
            badge.textContent = '0';
            badge.style.display = 'none';
        }
    }
}

function updateSepet() {
    const t = mevcutTurBilgisiniAl(aktifTur);
    const tutar = gecerliTutarAl();
    const carpan = gecerliCarpanAl();
    const toplam = tutar * carpan;

    const elTutar = document.getElementById('sepet-tutar-goster');
    const elSecili = document.getElementById('sepet-secili-onizleme');
    const hisseText = aktifTur === 'buyukbas'
        ? ` · ${hisseSayisi} hisse`
        : adetModuAktifMi()
            ? ` · ${aktifAdet} adet`
            : '';

    if (elTutar) {
        elTutar.textContent = formatPara(toplam);
    }

    if (elSecili) {
        elSecili.innerHTML = `
            <div>
                <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;font-weight:600;color:#162E4B;">${escapeHtml(t.baslik)}${hisseText}</p>
                <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#62868D;margin-top:2px;">${aktifTur === 'buyukbas' ? `Hisse başı: ${formatPara(tutar)}` : adetModuAktifMi() ? `Birim fiyat: ${formatPara(tutar)}` : seciliSahipMetni()}</p>
            </div>
            <p id="sepet-tutar-goster" style="font-family:'Libre Baskerville',serif;font-weight:700;font-size:16px;color:#162E4B;white-space:nowrap;">${formatPara(toplam)}</p>`;
    }

    renderSepetOzeti();
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

function sepetGuncellemesiniYayinla() {
    document.dispatchEvent(new CustomEvent('kp:sepet-guncellendi', {
        detail: {
            sepet: sepetKalemleri,
            toplam: sepetToplaminiHesapla(),
        },
    }));
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
                slug: aktifSlugAl(),
                tutar: gecerliTutarAl(),
                adet: gecerliCarpanAl(),
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

        if (Array.isArray(data.sepet)) {
            sepetKalemleri = data.sepet;
            renderSepetOzeti();
            sepetGuncellemesiniYayinla();
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

async function sepettenCikar(satirId) {
    const form = bagisFormunuAl();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const baseUrl = form?.dataset?.sepettenCikarUrl;

    if (!baseUrl || !csrfToken || !satirId) {
        sepetMesajiGoster('Silme bağlantısı şu anda hazır değil.', 'error');
        return;
    }

    try {
        const response = await fetch(`${baseUrl}/${satirId}`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.message || 'Sepet kalemi silinemedi.');
        }

        sepetKalemleri = Array.isArray(data.sepet) ? data.sepet : [];
        renderSepetOzeti();
        sepetGuncellemesiniYayinla();
        sepetMesajiGoster(data.message || 'Bağış kalemi sepetten çıkarıldı.');
    } catch (error) {
        sepetMesajiGoster(error.message || 'Sepet kalemi silinirken bir hata oluştu.', 'error');
    }
}

function kartAlaniDegeriniAl(id) {
    return document.getElementById(id)?.value?.trim() || '';
}

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

function kartAlanlariniHazirla() {
    const kartNoAlani = document.getElementById('kart-no');
    const kartSahibiAlani = document.getElementById('kart-sahibi');
    const odeyenAdAlani = document.getElementById('odeyen-ad');
    const sonKullanmaAlani = document.getElementById('kart-son-kullanma');
    const cvvAlani = document.getElementById('kart-cvv');

    if (kartSahibiAlani) {
        const tekSoyisimDuzelt = () => {
            const kartSahibi = kartSahibiAlani.value.trim();
            const odeyenAd = odeyenAdAlani?.value?.trim() || '';

            // Safari bazı profillerde sadece soyadı yazabiliyor; mümkünse tam ada tamamla.
            if (kartSahibi !== '' && !kartSahibi.includes(' ') && odeyenAd.includes(' ')) {
                const odeyenParcalar = odeyenAd.split(/\s+/).filter(Boolean);
                const soyad = odeyenParcalar[odeyenParcalar.length - 1] || '';

                if (soyad !== '' && kartSahibi.localeCompare(soyad, 'tr', { sensitivity: 'base' }) === 0) {
                    kartSahibiAlani.value = odeyenAd;
                }
            }
        };

        kartSahibiAlani.addEventListener('change', tekSoyisimDuzelt);
        kartSahibiAlani.addEventListener('blur', tekSoyisimDuzelt);
    }

    if (kartNoAlani) {
        kartNoAlani.addEventListener('input', () => {
            kartNoAlani.value = kartNumarasiniMaskele(kartNoAlani.value);
        });
        kartNoAlani.value = kartNumarasiniMaskele(kartNoAlani.value);
    }

    if (sonKullanmaAlani) {
        sonKullanmaAlani.addEventListener('input', () => {
            sonKullanmaAlani.value = sonKullanmayiMaskele(sonKullanmaAlani.value);
        });
        sonKullanmaAlani.value = sonKullanmayiMaskele(sonKullanmaAlani.value);
    }

    if (cvvAlani) {
        cvvAlani.addEventListener('input', () => {
            cvvAlani.value = sadeceRakam(cvvAlani.value).slice(0, 4);
        });
        cvvAlani.value = sadeceRakam(cvvAlani.value).slice(0, 4);
    }
}

function testKartiniDoldur(kartNo) {
    const kartNoAlani = document.getElementById('kart-no');
    const kartSahibi = document.getElementById('kart-sahibi');
    const sonKullanmaAlani = document.getElementById('kart-son-kullanma');
    const cvvAlani = document.getElementById('kart-cvv');

    if (kartNoAlani) {
        kartNoAlani.value = kartNumarasiniMaskele(kartNo);
    }

    if (kartSahibi && !kartSahibi.value) {
        kartSahibi.value = kartAlaniDegeriniAl('odeyen-ad') || 'Test Bağışçı';
    }

    if (sonKullanmaAlani && !sonKullanmaAlani.value) {
        sonKullanmaAlani.value = sonKullanmayiMaskele(`12${String(new Date().getFullYear() + 1).slice(-2)}`);
    }

    if (cvvAlani && !cvvAlani.value) {
        cvvAlani.value = '123';
    }
}

async function odemeyiTamamla() {
    const form = bagisFormunuAl();

    if (!form) {
        return;
    }

    const odemeUrl = form.dataset.odemeUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const butonlar = [document.getElementById('odeme-tamamla-btn'), document.getElementById('odeme-ozet-btn')].filter(Boolean);

    if (!odemeUrl || !csrfToken) {
        sepetMesajiGoster('Ödeme bağlantısı şu anda hazır değil.', 'error');
        return;
    }

    try {
        butonlar.forEach((buton) => {
            buton.disabled = true;
            buton.classList.add('opacity-70');
        });

        const response = await fetch(odemeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                slug: aktifSlugAl(),
                tutar: gecerliTutarAl(),
                adet: gecerliCarpanAl(),
                sahip_tipi: document.getElementById('radio-baskasi')?.classList.contains('selected') ? 'baskasi' : 'kendi',
                odeme_yontemi: document.getElementById('odeme-paytr')?.classList.contains('selected') ? 'paytr' : 'albaraka',
                kart_no: kartAlaniDegeriniAl('kart-no'),
                kart_sahibi: kartAlaniDegeriniAl('kart-sahibi'),
                son_kullanma: kartAlaniDegeriniAl('kart-son-kullanma'),
                cvv: kartAlaniDegeriniAl('kart-cvv'),
                form_verisi: formVerisiniTopla(),
            }),
        });

        // Albaraka 3D Secure: yanıt HTML ise doğrudan sayfaya yaz (otomatik form submit)
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
            throw new Error(ilkHata || data.message || 'Test ödeme tamamlanamadı.');
        }

        sepetMesajiGoster(data.message || 'Test ödeme başarıyla tamamlandı.');

        if (!bagisTamamlandiEventGonderildi) {
            bagisTamamlandiEventGonderildi = true;

            const payload = {
                bagis_slug: aktifSlugAl(),
                bagis_turu: mevcutTurBilgisiniAl(aktifTur)?.baslik || 'Bagis',
                value: Number(data.toplam_tutar || sepetToplaminiHesapla() || (gecerliTutarAl() * gecerliCarpanAl()) || 0),
                currency: 'TRY',
                payment_type: 'mock',
            };

            consentEventGonder('bagis_tamamlandi', payload, 'analitik');
            consentEventGonder('conversion', payload, 'pazarlama');
        }

        if (data.redirect_url) {
            window.setTimeout(() => {
                window.location.href = data.redirect_url;
            }, 500);
        }
    } catch (error) {
        sepetMesajiGoster(error.message || 'Ödeme sırasında bir hata oluştu.', 'error');
    } finally {
        butonlar.forEach((buton) => {
            buton.disabled = false;
            buton.classList.remove('opacity-70');
        });
    }
}

document.addEventListener('kp:sepet-guncellendi', (event) => {
    if (!Array.isArray(event.detail?.sepet)) {
        return;
    }

    sepetKalemleri = event.detail.sepet;
    renderSepetOzeti();
});

document.addEventListener('DOMContentLoaded', () => {
    bagisSayfaGoruntulemesiniGonder();
    kartAlanlariniHazirla();

    document.querySelectorAll('.tutar-btn[data-tutar], .tutar-btn[data-adet]').forEach((buton) => {
        buton.addEventListener('click', () => {
            document.querySelectorAll('.tutar-btn[data-tutar], .tutar-btn[data-adet]').forEach((digerButon) => digerButon.classList.remove('selected'));
            buton.classList.add('selected');
            if (adetModuAktifMi()) {
                aktifAdet = gecerliAdetAl(buton.dataset.adet);
                aktifTutar = sabitBirimFiyatAl();
            } else {
                aktifTutar = parseInt(buton.dataset.tutar, 10) || 0;
            }

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
                document.querySelectorAll('.tutar-btn[data-tutar], .tutar-btn[data-adet]').forEach((buton) => buton.classList.remove('selected'));
                if (adetModuAktifMi()) {
                    aktifAdet = gecerliAdetAl(this.value);
                    this.value = String(aktifAdet);
                    aktifTutar = sabitBirimFiyatAl();
                } else {
                    aktifTutar = parseInt(this.value, 10) || 0;
                }
                updateSepet();
            }
        });
    }

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
    const ilkAdet = document.querySelector('.tutar-btn[data-adet].selected');

    try {
        sepetKalemleri = JSON.parse(form?.dataset.sepet || '[]');
    } catch (error) {
        sepetKalemleri = [];
    }

    if (ilkTutar) {
        aktifTutar = parseInt(ilkTutar.dataset.tutar, 10) || 100;
    }

    if (ilkAdet) {
        aktifAdet = gecerliAdetAl(ilkAdet.dataset.adet);
        aktifTutar = sabitBirimFiyatAl();
    }

    setTur(initTur);
    renderSepetOzeti();
    sepetGuncellemesiniYayinla();
    updateSepet();
});

window.setTur = setTur;
window.setHisse = setHisse;
window.selectRadio = selectRadio;
window.toggleKopyala = toggleKopyala;
window.selectOdeme = selectOdeme;
window.sepeteEkle = sepeteEkle;
window.sepettenCikar = sepettenCikar;
window.testKartiniDoldur = testKartiniDoldur;
window.odemeyiTamamla = odemeyiTamamla;
window.updateSepet = updateSepet;
