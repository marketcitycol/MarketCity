// Menú lateral del encabezado (plugin "Encabezado MarketCity").
const drawer = document.getElementById('mc-drawer');
if (drawer) {
    const open = () => { drawer.classList.remove('hidden'); document.body.style.overflow = 'hidden'; drawer.querySelector('[data-drawer-close]:not(.absolute)')?.focus(); };
    const close = () => { drawer.classList.add('hidden'); document.body.style.overflow = ''; };
    document.querySelectorAll('[data-drawer-open]').forEach((b) => b.addEventListener('click', open));
    drawer.querySelectorAll('[data-drawer-close]').forEach((b) => b.addEventListener('click', close));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !drawer.classList.contains('hidden')) close(); });
}

// Cuenta regresiva de la barra de anuncio.
document.querySelectorAll('[data-countdown]').forEach((el) => {
    const end = Number(el.dataset.countdown) * 1000;
    const tick = () => {
        let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
        const d = Math.floor(s / 86400); s %= 86400;
        const p = (n) => String(n).padStart(2, '0');
        el.textContent = `${d}d ${p(Math.floor(s / 3600))}:${p(Math.floor((s % 3600) / 60))}:${p(s % 60)}`;
    };
    tick();
    setInterval(tick, 1000);
});
