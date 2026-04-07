document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('ekayit-form');

  if (!form) {
    return;
  }

  const textInputs = document.querySelectorAll(".ekayit-form-wrap input[type='text'], .ekayit-form-wrap input[type='search'], .uppercase-input");

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
  let aktifAdim = 1;

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

  function ozetTablosunuDoldur() {
    const ozetEl = document.getElementById('ozet-tablo');

    if (!ozetEl) {
      return;
    }

    const alanlar = [
      { label: 'Öğrenci Adı', deger: () => form.querySelector('[name="ogrenci_ad"]')?.value },
      { label: 'Öğrenci Soyadı', deger: () => form.querySelector('[name="ogrenci_soyad"]')?.value },
      { label: 'TC Kimlik', deger: () => form.querySelector('[name="ogrenci_tc"]')?.value },
      { label: 'Doğum Tarihi', deger: () => form.querySelector('[name="ogrenci_dogum_tarihi"]')?.value },
      { label: 'Veli Ad Soyad', deger: () => form.querySelector('[name="veli_ad_soyad"]')?.value },
      { label: 'Veli Telefon', deger: () => form.querySelector('[name="veli_telefon"]')?.value },
      { label: 'Veli E-posta', deger: () => form.querySelector('[name="veli_eposta"]')?.value },
      { label: 'Okul Adı', deger: () => form.querySelector('[name="okul_adi"]')?.value },
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

    if (hataliAlan) {
      hataliAlan.scrollIntoView({ behavior: 'smooth', block: 'center' });
      hataliAlan.focus();
      return false;
    }

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

  form.addEventListener('submit', () => {
    textInputs.forEach((input) => {
      input.value = input.value.toLocaleUpperCase('tr-TR');
    });
  });

  onayInputler.forEach((cb) => cb.addEventListener('change', tumOnaylariKontrolEt));

  tumOnaylariKontrolEt();
  adimGostergesiniGuncelle(1);
});

window.sonrakiAdim = window.sonrakiAdim || function () {};
window.oncekiAdim = window.oncekiAdim || function () {};
