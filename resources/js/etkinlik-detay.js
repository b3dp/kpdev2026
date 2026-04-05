function openLightbox(src) {
    const lightbox = document.getElementById('lightbox');
    const image = document.getElementById('lightbox-img');

    if (!lightbox || !image || !src) {
        return;
    }

    image.src = src;
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    const image = document.getElementById('lightbox-img');

    if (!lightbox || !image) {
        return;
    }

    lightbox.classList.remove('open');
    image.src = '';
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeLightbox();
        }
    });
});

window.openLightbox = openLightbox;
window.closeLightbox = closeLightbox;
