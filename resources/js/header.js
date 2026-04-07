const ham_btn = document.getElementById('ham-btn');
const mob_menu = document.getElementById('mobile-menu');
const search_toggle = document.getElementById('search-toggle');
const search_drawer = document.getElementById('search-drawer');
const header = document.getElementById('main-header');
const cart_triggers = document.querySelectorAll('[data-cart-trigger]');
const cart_drawer = document.getElementById('cart-drawer');
const cart_drawer_overlay = document.getElementById('cart-drawer-overlay');
const cart_drawer_close = document.getElementById('cart-drawer-close');
const cart_drawer_items = document.getElementById('cart-drawer-items');
const cart_drawer_count = document.getElementById('cart-drawer-count');
const cart_drawer_total = document.getElementById('cart-drawer-total');

const closeMobileMenu = () => {
  if (!ham_btn || !mob_menu) {
    return;
  }

  ham_btn.classList.remove('open');
  ham_btn.setAttribute('aria-expanded', 'false');
  mob_menu.classList.remove('open');
  mob_menu.setAttribute('aria-hidden', 'true');
};

const closeSearchDrawer = () => {
  if (!search_toggle || !search_drawer) {
    return;
  }

  search_toggle.setAttribute('aria-expanded', 'false');
  search_drawer.classList.remove('open');
  search_drawer.setAttribute('aria-hidden', 'true');
};

const escape_html = (value = '') => String(value)
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')
  .replaceAll('"', '&quot;')
  .replaceAll("'", '&#039;');

const format_money = (value = 0) => `₺${Number(value || 0).toLocaleString('tr-TR', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})}`;

let cart_items = [];

try {
  cart_items = JSON.parse(cart_drawer_items?.dataset.cartItems || '[]');
} catch {
  cart_items = [];
}

const render_cart_drawer = () => {
  if (!cart_drawer_items) {
    return;
  }

  const toplam = cart_items.reduce((deger, satir) => deger + Number(satir?.toplam || 0), 0);
  const badge = document.getElementById('sepet-badge');

  if (cart_drawer_count) {
    cart_drawer_count.textContent = cart_items.length ? `${cart_items.length} kalem` : 'Sepet boş';
  }

  if (cart_drawer_total) {
    cart_drawer_total.textContent = format_money(toplam);
  }

  if (badge) {
    if (cart_items.length) {
      badge.textContent = `${cart_items.length}`;
      badge.style.display = 'flex';
    } else {
      badge.textContent = '0';
      badge.style.display = 'none';
    }
  }

  if (!cart_items.length) {
    cart_drawer_items.innerHTML = `
      <div class="rounded-2xl border border-dashed border-primary/15 bg-bg-soft px-4 py-6 text-center">
        <p class="font-jakarta text-sm font-semibold text-primary">Sepetiniz şu an boş.</p>
        <p class="mt-1 font-jakarta text-xs leading-5 text-teal-muted">Bağış türlerinden birini seçip eklediğinizde burada anında görünecektir.</p>
      </div>`;
    return;
  }

  cart_drawer_items.innerHTML = cart_items.map((satir) => {
    const sahip_metni = satir?.sahip_tipi === 'baskasi' ? 'Başkası adına' : 'Kendi adıma';
    const adet = Number(satir?.adet || 1);
    const adet_metni = adet > 1 ? `${adet} adet / hisse` : '1 adet';

    return `
      <div class="rounded-2xl border border-primary/10 bg-white p-3 shadow-sm">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-jakarta text-[13px] font-bold text-primary">${escape_html(satir?.ad || 'Bağış Kalemi')}</p>
            <p class="mt-1 font-jakarta text-[11.5px] text-teal-muted">${adet_metni} · ${sahip_metni}</p>
          </div>
          <button type="button" data-cart-remove="${Number(satir?.satir_id || 0)}" class="shrink-0 rounded-lg border border-red-200 px-2.5 py-1 font-jakarta text-[11px] font-bold text-red-600 transition hover:bg-red-50">
            Sil
          </button>
        </div>
        <div class="mt-3 font-baskerville text-[18px] font-bold text-primary">${format_money(satir?.toplam || 0)}</div>
      </div>`;
  }).join('');
};

const openCartDrawer = () => {
  if (!cart_drawer || !cart_drawer_overlay) {
    return;
  }

  render_cart_drawer();
  closeMobileMenu();
  closeSearchDrawer();

  cart_drawer_overlay.classList.remove('hidden');
  window.requestAnimationFrame(() => {
    cart_drawer_overlay.classList.add('opacity-100');
    cart_drawer.classList.remove('translate-x-full');
  });

  cart_drawer.setAttribute('aria-hidden', 'false');
  document.body.classList.add('overflow-hidden');
  cart_triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'true'));
};

const closeCartDrawer = () => {
  if (!cart_drawer || !cart_drawer_overlay) {
    return;
  }

  cart_drawer.classList.add('translate-x-full');
  cart_drawer.setAttribute('aria-hidden', 'true');
  cart_drawer_overlay.classList.remove('opacity-100');
  cart_triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));
  document.body.classList.remove('overflow-hidden');

  window.setTimeout(() => {
    if (cart_drawer.getAttribute('aria-hidden') === 'true') {
      cart_drawer_overlay.classList.add('hidden');
    }
  }, 200);
};

if (ham_btn && mob_menu) {
  ham_btn.addEventListener('click', () => {
    const is_open = mob_menu.classList.contains('open');

    if (!is_open) {
      closeSearchDrawer();
      closeCartDrawer();
    }

    mob_menu.classList.toggle('open', !is_open);
    ham_btn.classList.toggle('open', !is_open);
    ham_btn.setAttribute('aria-expanded', String(!is_open));
    mob_menu.setAttribute('aria-hidden', String(is_open));
  });
}

if (search_toggle && search_drawer) {
  search_toggle.addEventListener('click', () => {
    const is_open = search_drawer.classList.contains('open');

    if (!is_open) {
      closeMobileMenu();
      closeCartDrawer();
    }

    search_drawer.classList.toggle('open', !is_open);
    search_toggle.setAttribute('aria-expanded', String(!is_open));
    search_drawer.setAttribute('aria-hidden', String(is_open));
  });
}

cart_triggers.forEach((trigger) => {
  trigger.addEventListener('click', (event) => {
    if (!cart_drawer) {
      return;
    }

    event.preventDefault();
    openCartDrawer();
  });
});

if (cart_drawer_overlay) {
  cart_drawer_overlay.addEventListener('click', closeCartDrawer);
}

if (cart_drawer_close) {
  cart_drawer_close.addEventListener('click', closeCartDrawer);
}

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') {
    return;
  }

  closeMobileMenu();
  closeSearchDrawer();
  closeCartDrawer();
});

document.querySelectorAll('.mob-acc-btn').forEach((btn) => {
  btn.addEventListener('click', () => {
    const panel = document.getElementById(btn.dataset.target);

    if (!panel) {
      return;
    }

    const is_open = panel.classList.contains('open');
    document.querySelectorAll('.mob-sub').forEach((sub) => sub.classList.remove('open'));
    document.querySelectorAll('.mob-acc-btn').forEach((acc_btn) => acc_btn.classList.remove('open'));

    if (!is_open) {
      panel.classList.add('open');
      btn.classList.add('open');
    }
  });
});

if (header) {
  window.addEventListener('scroll', () => {
    const scrolled = window.scrollY > 8;
    header.classList.toggle('scrolled', scrolled);
  });
}

if (cart_drawer_items) {
  cart_drawer_items.addEventListener('click', async (event) => {
    const sil_butonu = event.target.closest('[data-cart-remove]');
    const csrf_token = document.querySelector('meta[name="csrf-token"]')?.content;
    const remove_url = cart_drawer_items.dataset.removeUrl || '';

    if (!sil_butonu || !csrf_token || !remove_url) {
      return;
    }

    event.preventDefault();

    const satir_id = Number(sil_butonu.dataset.cartRemove || 0);

    if (!satir_id) {
      return;
    }

    sil_butonu.disabled = true;
    sil_butonu.classList.add('opacity-60');

    try {
      const response = await fetch(`${remove_url}/${satir_id}`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf_token,
        },
      });

      const data = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(data.message || 'Sepet kalemi silinemedi.');
      }

      cart_items = Array.isArray(data.sepet) ? data.sepet : [];
      render_cart_drawer();
      document.dispatchEvent(new CustomEvent('kp:sepet-guncellendi', {
        detail: {
          sepet: cart_items,
          toplam: Number(data.toplam || 0),
        },
      }));
    } catch {
      sil_butonu.disabled = false;
      sil_butonu.classList.remove('opacity-60');
    }
  });
}

document.addEventListener('kp:sepet-guncellendi', (event) => {
  if (!Array.isArray(event.detail?.sepet)) {
    return;
  }

  cart_items = event.detail.sepet;
  render_cart_drawer();
});

const recent_search_key = 'kp_son_aramalar';
const search_forms = document.querySelectorAll('[data-search-form]');
const recent_blocks = document.querySelectorAll('[data-recent-block]');
const recent_targets = document.querySelectorAll('[data-recent-searches]');

const get_recent_searches = () => {
  try {
    const raw_value = window.localStorage.getItem(recent_search_key);
    const parsed = raw_value ? JSON.parse(raw_value) : [];

    return Array.isArray(parsed) ? parsed.filter(Boolean).slice(0, 6) : [];
  } catch {
    return [];
  }
};

const render_recent_searches = () => {
  const items = get_recent_searches();

  recent_blocks.forEach((block) => {
    block.classList.toggle('hidden', items.length === 0);
  });

  recent_targets.forEach((target) => {
    target.innerHTML = items
      .map((item) => `<a href="/arama?q=${encodeURIComponent(item)}" class="search-chip">${escape_html(item)}</a>`)
      .join('');
  });
};

const save_recent_search = (query) => {
  const value = query.trim();

  if (value.length < 2) {
    return;
  }

  const current_items = get_recent_searches().filter((item) => item.toLocaleLowerCase('tr') !== value.toLocaleLowerCase('tr'));
  const next_items = [value, ...current_items].slice(0, 6);

  try {
    window.localStorage.setItem(recent_search_key, JSON.stringify(next_items));
  } catch {
    return;
  }

  render_recent_searches();
};

search_forms.forEach((form) => {
  form.addEventListener('submit', () => {
    const input = form.querySelector('input[name="q"]');

    if (!input) {
      return;
    }

    save_recent_search(input.value);
  });
});

render_cart_drawer();
render_recent_searches();
