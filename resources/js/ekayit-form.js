document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('ekayit-form');

  if (!form) {
    return;
  }

  const textInputs = document.querySelectorAll(".ekayit-form-wrap input[type='text'], .ekayit-form-wrap input[type='search'], .uppercase-input");
  const telefonInputs = document.querySelectorAll(".ekayit-form-wrap input[type='tel']");

  textInputs.forEach((input) => {
    input.addEventListener('input', function () {
      const start = this.selectionStart;
      const end = this.selectionEnd;
      this.value = this.value.toLocaleUpperCase('tr-TR');
      if (typeof start === 'number' && typeof end === 'number') {
        this.setSelectionRange(start, end);
      }
    });

    input.addEventListener('paste', function () {
      setTimeout(() => {
        this.value = this.value.toLocaleUpperCase('tr-TR');
      }, 0);
    });
  });

  const onayInputler = document.querySelectorAll('.onay-cb');
  const basvurBtn = document.getElementById('basvur-btn');
  const eskiTipKimlikCb = document.getElementById('eski_tip_kimlik_var');
  const eskiKimlikAlanlari = document.getElementById('eski-kimlik-alanlari');
  const ilcelerDataEl = document.getElementById('ekayit-ilceler-data');
  let ilcelerHaritasi = {};
  let aktifAdim = 1;

  if (ilcelerDataEl?.textContent) {
    try {
      ilcelerHaritasi = JSON.parse(ilcelerDataEl.textContent);
    } catch (error) {
      ilcelerHaritasi = {};
    }
  }

  function eskiKimlikAlanlariniGuncelle() {
    if (!eskiTipKimlikCb || !eskiKimlikAlanlari) {
      return;
    }

    const acikMi = eskiTipKimlikCb.checked;
    const alanlar = eskiKimlikAlanlari.querySelectorAll('[data-kimlik-alani="true"]');

    eskiKimlikAlanlari.classList.toggle('hidden', !acikMi);

    alanlar.forEach((alan) => {
      if (acikMi) {
        alan.setAttribute('required', 'required');
      } else {
        alan.removeAttribute('required');
        alan.classList.remove('border-red-400');
      }
    });
  }

  function telefonuGorunumIcinNormalestir(input) {
    let deger = input.value.replace(/\D/g, '');

    if (deger.startsWith('0090')) {
      deger = deger.slice(4);
    } else if (deger.startsWith('90')) {
      deger = deger.slice(2);
    } else if (deger.startsWith('0')) {
      deger = deger.slice(1);
    }

    input.value = deger.slice(0, 10);
    input.setCustomValidity('');
  }

  telefonInputs.forEach((input) => {
    telefonuGorunumIcinNormalestir(input);
    input.addEventListener('input', function () {
      telefonuGorunumIcinNormalestir(this);
    });
  });

  function veliTelefonAlaniniGuncelle(indeks) {
    const sahipSelect = form.querySelector(`[data-telefon-sahibi="${indeks}"]`);
    const alanSarmal = form.querySelector(`[data-telefon-alani="${indeks}"]`);
    const telefonInput = alanSarmal?.querySelector('input');

    if (!sahipSelect || !alanSarmal || !telefonInput) {
      return;
    }

    const secimVarMi = sahipSelect.value !== '';

    alanSarmal.classList.toggle('hidden', !secimVarMi);

    if (secimVarMi) {
      telefonInput.setAttribute('required', 'required');
    } else {
      telefonInput.removeAttribute('required');
      telefonInput.classList.remove('border-red-400');

      if (indeks === 2) {
        telefonInput.value = '';
      }
    }
  }

  function tumOnaylariKontrolEt() {
    const hepsiIsaretli = [...onayInputler].every((cb) => cb.checked);

    if (!basvurBtn) {
      return;
    }

    basvurBtn.disabled = !hepsiIsaretli;
    basvurBtn.classList.toggle('opacity-40', !hepsiIsaretli);
    basvurBtn.classList.toggle('cursor-not-allowed', !hepsiIsaretli);
    basvurBtn.classList.toggle('cursor-pointer', hepsiIsaretli);
    basvurBtn.classList.toggle('hover:bg-[#c94620]', hepsiIsaretli);
    basvurBtn.title = hepsiIsaretli ? '' : 'Lütfen tüm onay kutularını işaretleyin';
  }

  function adimGostergesiniGuncelle(aktif) {
    for (let i = 1; i <= 4; i += 1) {
      const daire = document.getElementById(`adim-daire-${i}`);

      if (!daire) {
        continue;
      }

      daire.classList.remove('aktif', 'tamamlandi');

      if (i < aktif) {
        daire.classList.add('tamamlandi');
        daire.innerHTML = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>';
      } else if (i === aktif) {
        daire.classList.add('aktif');
        daire.textContent = i;
      } else {
        daire.textContent = i;
      }
    }
  }

  function ilceSecenekleriniGuncelle(grup, ilkKurulum = false) {
    const ilSelect = form.querySelector(`[data-il-select="${grup}"]`);
    const ilceSelect = form.querySelector(`[data-ilce-select="${grup}"]`);

    if (!ilSelect || !ilceSelect) {
      return;
    }

    const seciliIl = ilSelect.value;
    const saklananIlce = ilkKurulum ? (ilceSelect.dataset.selected || ilceSelect.value) : '';
    const ilceler = Array.isArray(ilcelerHaritasi[seciliIl]) ? ilcelerHaritasi[seciliIl] : [];

    ilceSelect.innerHTML = '';

    const varsayilanSecenek = document.createElement('option');
    varsayilanSecenek.value = '';
    varsayilanSecenek.textContent = seciliIl ? 'İlçe Seçiniz' : 'Önce il seçiniz';
    ilceSelect.appendChild(varsayilanSecenek);

    ilceler.forEach((ilce) => {
      const secenek = document.createElement('option');
      secenek.value = ilce;
      secenek.textContent = ilce;

      if (saklananIlce && saklananIlce === ilce) {
        secenek.selected = true;
      }

      ilceSelect.appendChild(secenek);
    });

    ilceSelect.disabled = !seciliIl;
  }

  function ozetTablosunuDoldur() {
    const ozetEl = document.getElementById('ozet-tablo');

    if (!ozetEl) {
      return;
    }

    const alanlar = [
      { label: 'Öğrenci Adı', deger: () => form.querySelector('[name="ogrenci_ad"]')?.value },
      { label: 'Öğrenci Soyadı', deger: () => form.querySelector('[name="ogrenci_soyad"]')?.value },
      { label: 'TC Kimlik', deger: () => form.querySelector('[name="ogrenci_tc"]')?.value },
      { label: 'Öğrenci Telefon', deger: () => form.querySelector('[name="ogrenci_telefon"]')?.value },
      { label: 'Öğrenci E-posta', deger: () => form.querySelector('[name="ogrenci_eposta"]')?.value },
      { label: 'Doğum Tarihi', deger: () => form.querySelector('[name="ogrenci_dogum_tarihi"]')?.value },
      {
        label: 'Öğrenci İl / İlçe',
        deger: () => {
          const il = form.querySelector('[name="ogrenci_ikamet_il"]')?.value;
          const ilce = form.querySelector('[name="ogrenci_ikamet_ilce"]')?.value;
          return [il, ilce].filter(Boolean).join(' / ');
        },
      },
      {
        label: 'Nüfusa Kayıtlı İl / İlçe',
        deger: () => {
          const il = form.querySelector('[name="kimlik_kayitli_il"]')?.value;
          const ilce = form.querySelector('[name="kimlik_kayitli_ilce"]')?.value;
          return [il, ilce].filter(Boolean).join(' / ');
        },
      },
      { label: 'Kimlik Seri No', deger: () => form.querySelector('[name="kimlik_seri_no"]')?.value },
      { label: 'Kan Grubu', deger: () => form.querySelector('[name="kimlik_kan_grubu"]')?.value },
      { label: 'Veli Ad Soyad', deger: () => form.querySelector('[name="veli_ad_soyad"]')?.value },
      {
        label: 'Veli Telefon',
        deger: () => {
          const sahip = form.querySelector('[name="veli_telefon_sahibi_1"]')?.selectedOptions?.[0]?.textContent;
          const telefon = form.querySelector('[name="veli_telefon"]')?.value;
          return [sahip && sahip !== 'Seçiniz' ? sahip : null, telefon].filter(Boolean).join(' - ');
        },
      },
      {
        label: 'Veli Telefon 2',
        deger: () => {
          const sahip = form.querySelector('[name="veli_telefon_sahibi_2"]')?.selectedOptions?.[0]?.textContent;
          const telefon = form.querySelector('[name="veli_telefon_2"]')?.value;
          return [sahip && sahip !== 'Seçiniz' ? sahip : null, telefon].filter(Boolean).join(' - ');
        },
      },
      { label: 'Veli E-posta', deger: () => form.querySelector('[name="veli_eposta"]')?.value },
      { label: 'Veli Adres', deger: () => form.querySelector('[name="veli_adres"]')?.value },
      {
        label: 'Veli İl / İlçe',
        deger: () => {
          const il = form.querySelector('[name="veli_il"]')?.value;
          const ilce = form.querySelector('[name="veli_ilce"]')?.value;
          return [il, ilce].filter(Boolean).join(' / ');
        },
      },
      { label: 'Okul Adı', deger: () => form.querySelector('[name="okul_adi"]')?.value },
      { label: 'Okul Numarası', deger: () => form.querySelector('[name="okul_numarasi"]')?.value },
      {
        label: 'Okul İl / İlçe',
        deger: () => {
          const il = form.querySelector('[name="okul_il"]')?.value;
          const ilce = form.querySelector('[name="okul_ilce"]')?.value;
          return [il, ilce].filter(Boolean).join(' / ');
        },
      },
    ];

    ozetEl.innerHTML = alanlar
      .map((alan) => {
        const deger = alan.deger() || '—';

        return `<div class="flex justify-between gap-4 border-b border-primary/5 py-2 last:border-0"><span class="text-teal-muted">${alan.label}</span><span class="text-right font-semibold text-primary">${deger}</span></div>`;
      })
      .join('');
  }

  function telefonlarAyniMi() {
    const telefon1 = (form.querySelector('[name="veli_telefon"]')?.value || '').replace(/\D/g, '');
    const telefon2 = (form.querySelector('[name="veli_telefon_2"]')?.value || '').replace(/\D/g, '');

    return telefon1 !== '' && telefon2 !== '' && telefon1 === telefon2;
  }

  function paneliDogrula(mevcutAdim) {
    const panel = document.getElementById(`adim-panel-${mevcutAdim}`);

    if (!panel) {
      return true;
    }

    const alanlar = panel.querySelectorAll('[required]');
    let hataliAlan = null;

    alanlar.forEach((alan) => {
      const gecerli = alan.type === 'checkbox' ? alan.checked : alan.checkValidity();

      alan.classList.toggle('border-red-400', !gecerli);

      if (!gecerli && !hataliAlan) {
        hataliAlan = alan;
      }
    });

    if (!hataliAlan && mevcutAdim === 2 && telefonlarAyniMi()) {
      hataliAlan = form.querySelector('[name="veli_telefon_2"]');

      if (hataliAlan) {
        hataliAlan.classList.add('border-red-400');
        hataliAlan.setCustomValidity('Veli telefon numaralarının ikisi aynı olamaz.');
        hataliAlan.reportValidity();
      }
    }

    if (hataliAlan) {
      hataliAlan.scrollIntoView({ behavior: 'smooth', block: 'center' });
      hataliAlan.focus();
      return false;
    }

    const ikinciTelefon = form.querySelector('[name="veli_telefon_2"]');
    ikinciTelefon?.setCustomValidity('');

    return true;
  }

  window.sonrakiAdim = function (mevcutAdim) {
    if (!paneliDogrula(mevcutAdim)) {
      return;
    }

    document.getElementById(`adim-panel-${mevcutAdim}`)?.classList.add('hidden');
    aktifAdim = mevcutAdim + 1;
    document.getElementById(`adim-panel-${aktifAdim}`)?.classList.remove('hidden');

    adimGostergesiniGuncelle(aktifAdim);

    if (aktifAdim === 4) {
      ozetTablosunuDoldur();
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  window.oncekiAdim = function (mevcutAdim) {
    document.getElementById(`adim-panel-${mevcutAdim}`)?.classList.add('hidden');
    aktifAdim = mevcutAdim - 1;
    document.getElementById(`adim-panel-${aktifAdim}`)?.classList.remove('hidden');
    adimGostergesiniGuncelle(aktifAdim);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  form.addEventListener('submit', (event) => {
    textInputs.forEach((input) => {
      input.value = input.value.toLocaleUpperCase('tr-TR');
    });

    if (telefonlarAyniMi()) {
      event.preventDefault();
      aktifAdim = 2;
      document.querySelectorAll('.adim-panel').forEach((panel) => panel.classList.add('hidden'));
      document.getElementById('adim-panel-2')?.classList.remove('hidden');
      adimGostergesiniGuncelle(2);

      const hataAlani = form.querySelector('[name="veli_telefon_2"]');
      if (hataAlani) {
        hataAlani.classList.add('border-red-400');
        hataAlani.setCustomValidity('Veli telefon numaralarının ikisi aynı olamaz.');
        hataAlani.reportValidity();
        hataAlani.focus();
      }
    }
  });

  ['ogrenci', 'kimlik', 'veli', 'okul'].forEach((grup) => {
    const ilSelect = form.querySelector(`[data-il-select="${grup}"]`);

    if (!ilSelect) {
      return;
    }

    ilceSecenekleriniGuncelle(grup, true);
    ilSelect.addEventListener('change', () => ilceSecenekleriniGuncelle(grup));
  });

  if (eskiTipKimlikCb) {
    eskiKimlikAlanlariniGuncelle();
    eskiTipKimlikCb.addEventListener('change', eskiKimlikAlanlariniGuncelle);
  }

  [1, 2].forEach((indeks) => {
    veliTelefonAlaniniGuncelle(indeks);
    form.querySelector(`[data-telefon-sahibi="${indeks}"]`)?.addEventListener('change', () => veliTelefonAlaniniGuncelle(indeks));
  });

  onayInputler.forEach((cb) => cb.addEventListener('change', tumOnaylariKontrolEt));

  const ilkHataAlani = form.querySelector('.border-red-400');
  if (ilkHataAlani) {
    const hataPaneli = ilkHataAlani.closest('.adim-panel');
    const panelNumarasi = Number(hataPaneli?.id?.split('-').pop() || 1);

    document.querySelectorAll('.adim-panel').forEach((panel) => panel.classList.add('hidden'));
    document.getElementById(`adim-panel-${panelNumarasi}`)?.classList.remove('hidden');
    aktifAdim = panelNumarasi;
    adimGostergesiniGuncelle(panelNumarasi);

    setTimeout(() => {
      ilkHataAlani.scrollIntoView({ behavior: 'smooth', block: 'center' });
      ilkHataAlani.focus();
    }, 100);
  } else {
    adimGostergesiniGuncelle(1);
  }

  tumOnaylariKontrolEt();
});

window.sonrakiAdim = window.sonrakiAdim || function () {};
window.oncekiAdim = window.oncekiAdim || function () {};
