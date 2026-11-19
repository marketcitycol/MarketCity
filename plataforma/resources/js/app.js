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

// Panel "Agregado al carrito": agrega sin salir de la página y muestra el subtotal real del carrito.
// Sin JavaScript, el formulario funciona igual que antes (recarga la página).
const sheet = document.getElementById('mc-cart-sheet');
if (sheet) {
    const close = () => { sheet.classList.add('hidden'); sheet.setAttribute('aria-hidden', 'true'); };
    sheet.querySelectorAll('[data-sheet-close]').forEach((b) => b.addEventListener('click', close));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
    document.querySelectorAll('form[data-add-to-cart]').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            if (e.submitter && e.submitter.name === 'buy_now') return; // "Comprar ahora" va directo al checkout
            e.preventDefault();
            const btn = e.submitter;
            btn && (btn.disabled = true);
            try {
                const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (!res.ok || !data.ok) { alertBox(data.message || 'No pudimos agregar el producto.'); return; }
                sheet.querySelector('[data-sheet-name]').textContent = data.product.name;
                sheet.querySelector('[data-sheet-price]').textContent = data.product.price;
                sheet.querySelector('[data-sheet-subtotal]').textContent = data.subtotal;
                const img = sheet.querySelector('[data-sheet-img]');
                if (data.product.image) { img.src = data.product.image; img.classList.remove('hidden'); } else { img.classList.add('hidden'); }
                document.querySelectorAll('[data-cart-count]').forEach((el) => { el.textContent = data.count; el.classList.remove('hidden'); });
                document.querySelectorAll('[data-cart-link]').forEach((el) => el.setAttribute('aria-label', `Carrito, ${data.count} productos`));
                sheet.classList.remove('hidden');
                sheet.setAttribute('aria-hidden', 'false');
                sheet.querySelector('[data-sheet-primary]').focus();
            } catch (err) {
                form.submit();
            } finally {
                btn && (btn.disabled = false);
            }
        });
    });
    function alertBox(msg) {
        const el = sheet.querySelector('[data-sheet-error]');
        el.textContent = msg; el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 4000);
    }
}
