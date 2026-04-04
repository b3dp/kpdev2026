const ham_btn = document.getElementById('ham-btn');
const mob_menu = document.getElementById('mobile-menu');
const search_toggle = document.getElementById('search-toggle');
const search_drawer = document.getElementById('search-drawer');
const header = document.getElementById('main-header');

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

if (ham_btn && mob_menu) {
  ham_btn.addEventListener('click', () => {
    const is_open = mob_menu.classList.contains('open');

    if (!is_open) {
      closeSearchDrawer();
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
    }

    search_drawer.classList.toggle('open', !is_open);
    search_toggle.setAttribute('aria-expanded', String(!is_open));
    search_drawer.setAttribute('aria-hidden', String(is_open));
  });
}

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') {
    return;
  }

  closeMobileMenu();
  closeSearchDrawer();
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

const recent_search_key = 'kp_son_aramalar';
const search_forms = document.querySelectorAll('[data-search-form]');
const recent_blocks = document.querySelectorAll('[data-recent-block]');
const recent_targets = document.querySelectorAll('[data-recent-searches]');

const escape_html = (value) => value
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')
  .replaceAll('"', '&quot;')
  .replaceAll("'", '&#039;');

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

render_recent_searches();
