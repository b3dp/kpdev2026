document.addEventListener('DOMContentLoaded', () => {
    const navLinks = Array.from(document.querySelectorAll('[data-kurumsal-nav]'));

    if (!navLinks.length) {
        return;
    }

    const sections = navLinks
        .map((link) => {
            const hedef = link.getAttribute('href');
            return hedef ? document.querySelector(hedef) : null;
        })
        .filter(Boolean);

    if (!sections.length) {
        return;
    }

    const aktiflestir = (id) => {
        navLinks.forEach((link) => {
            const aktifMi = link.getAttribute('href') === `#${id}`;
            link.classList.toggle('active', aktifMi);
        });
    };

    const gozlemci = new IntersectionObserver(
        (entries) => {
            const gorunen = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

            if (gorunen?.target?.id) {
                aktiflestir(gorunen.target.id);
            }
        },
        {
            rootMargin: '-35% 0px -45% 0px',
            threshold: [0.2, 0.35, 0.6],
        },
    );

    sections.forEach((section) => gozlemci.observe(section));
});
