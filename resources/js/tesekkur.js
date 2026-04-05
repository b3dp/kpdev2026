function spawnConfetti() {
    const wrap = document.getElementById('confetti-wrap');

    if (!wrap) {
        return;
    }

    wrap.innerHTML = '';

    const colors = ['#B27829', '#E95925', '#162E4B', '#28484C', '#EBDFB5', '#FF9300'];

    for (let i = 0; i < 24; i += 1) {
        const el = document.createElement('div');
        el.className = 'confetti-piece';
        el.style.cssText = `
            left: ${Math.random() * 100}%;
            top: ${-10 + Math.random() * 20}px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            width: ${6 + Math.random() * 6}px;
            height: ${6 + Math.random() * 6}px;
            border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
            animation-delay: ${Math.random() * 0.8}s;
            animation-duration: ${1 + Math.random() * 0.8}s;
        `;

        wrap.appendChild(el);
    }

    window.setTimeout(() => {
        wrap.innerHTML = '';
    }, 2500);
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('confetti-wrap')) {
        spawnConfetti();
    }
});

window.spawnConfetti = spawnConfetti;
