const ham_btn = document.getElementById('ham-btn');
const mob_menu = document.getElementById('mobile-menu');

if (ham_btn && mob_menu) {
  ham_btn.addEventListener('click', () => {
    const is_open = mob_menu.classList.contains('open');
    mob_menu.classList.toggle('open', !is_open);
    ham_btn.classList.toggle('open', !is_open);
    ham_btn.setAttribute('aria-expanded', String(!is_open));
    mob_menu.setAttribute('aria-hidden', String(is_open));
  });
}

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

const header = document.getElementById('main-header');

if (header) {
  window.addEventListener('scroll', () => {
    const scrolled = window.scrollY > 8;
    header.classList.toggle('scrolled', scrolled);
  });
}
