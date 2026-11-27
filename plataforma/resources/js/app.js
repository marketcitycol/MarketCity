// Menú lateral del encabezado (plugin "Encabezado MarketCity").
const drawer = document.getElementById('mc-drawer');
if (drawer) {
    const open = () => { drawer.classList.remove('hidden'); document.body.style.overflow = 'hidden'; drawer.querySelector('[data-drawer-close]:not(.absolute)')?.focus(); };
    const close = () => { drawer.classList.add('hidden'); document.body.style.overflow = ''; };
    document.querySelectorAll('[data-drawer-open]').forEach((b) => b.addEventListener('click', open));
    drawer.querySelectorAll('[data-drawer-close]').forEach((b) => b.addEventListener('click', close));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !drawer.classList.contains('hidden')) close(); });
    // Pestañas "Quiero comprar" / "Quiero vender".
    const tabs = drawer.querySelectorAll('[data-drawer-tab]');
    tabs.forEach((t) => t.addEventListener('click', () => {
        tabs.forEach((x) => x.setAttribute('aria-selected', x === t ? 'true' : 'false'));
        drawer.querySelectorAll('[data-drawer-pane]').forEach((p) => p.classList.toggle('hidden', p.dataset.drawerPane !== t.dataset.drawerTab));
    }));
}

// Almacenamiento del navegador: puede no estar disponible (modo privado).
const store = {
    get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* sin almacenamiento */ } },
};

// Franja de avisos: rota en celular y se puede ocultar por hoy.
const promo = document.querySelector('[data-promo]');
if (promo) {
    const today = new Date().toISOString().slice(0, 10);
    if (store.get('mc_promo_hidden') === today) promo.remove();
    else {
        promo.querySelector('[data-promo-close]')?.addEventListener('click', () => { store.set('mc_promo_hidden', today); promo.remove(); });
        const items = [...promo.querySelectorAll('[data-promo-item]')];
        let i = 0;
        if (items.length > 1) setInterval(() => {
            if (window.matchMedia('(min-width: 768px)').matches) return;
            items[i].classList.replace('inline-flex', 'hidden');
            i = (i + 1) % items.length;
            items[i].classList.replace('hidden', 'inline-flex');
        }, 4500);
    }
}

// Búsqueda por voz cuando el navegador la ofrece.
const Speech = window.SpeechRecognition || window.webkitSpeechRecognition;
if (Speech) {
    document.querySelectorAll('[data-voice]').forEach((btn) => {
        btn.classList.remove('hidden');
        btn.classList.add('grid');
        btn.addEventListener('click', () => {
            const form = btn.closest('form');
            const input = form.querySelector('input[name="q"]');
            const rec = new Speech();
            rec.lang = 'es-CO';
            rec.onresult = (e) => { input.value = e.results[0][0].transcript; form.submit(); };
            rec.start();
        });
    });
}

// "Retoma donde quedaste": último producto visto, solo en este navegador.
const seen = document.querySelector('[data-recent-save]');
if (seen) store.set('mc_recent', seen.dataset.recentSave);
const recentBox = document.querySelector('[data-recent]');
if (recentBox) {
    try {
        const r = JSON.parse(store.get('mc_recent') || 'null');
        if (r && r.url && r.name) {
            recentBox.querySelector('[data-recent-link]').href = r.url;
            recentBox.querySelector('[data-recent-name]').textContent = r.name;
            recentBox.querySelector('[data-recent-price]').textContent = r.price || '';
            recentBox.classList.remove('hidden');
        }
    } catch (e) { /* dato viejo o dañado */ }
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
                window.mcTrack && window.mcTrack('add_to_cart', { currency: 'COP', value: data.value, items: [data.item] });
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

// Medición con consentimiento (plugin «Medición y aviso de cookies»).
// Sin «Aceptar» no se carga ningún script de Google ni de Meta; los eventos se quedan en la página.
(function () {
    const ids = window.mcAnalytics;
    if (!ids) return;
    const banner = document.getElementById('mc-consent');
    const COOKIE = 'mc_consent';
    const read = () => (document.cookie.match(/(?:^|; )mc_consent=(granted|denied)/) || [])[1] || null;
    const save = (v) => { document.cookie = `${COOKIE}=${v}; Max-Age=${60 * 60 * 24 * 180}; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`; };
    let loaded = false;
    const META = { view_item: 'ViewContent', add_to_cart: 'AddToCart', begin_checkout: 'InitiateCheckout', add_payment_info: 'AddPaymentInfo', purchase: 'Purchase', whatsapp_click: 'Contact' };

    function script(src) { const s = document.createElement('script'); s.async = true; s.src = src; document.head.appendChild(s); }

    function load() {
        if (loaded) return;
        loaded = true;
        window.gtag('consent', 'update', { ad_storage: 'granted', ad_user_data: 'granted', ad_personalization: 'granted', analytics_storage: 'granted' });
        if (ids.gtm) {
            window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
            script(`https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(ids.gtm)}`);
        } else if (ids.ga4) {
            script(`https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ids.ga4)}`);
            window.gtag('js', new Date());
            window.gtag('config', ids.ga4);
        }
        // Google Ads (#7): con GTM se configura dentro de GTM; sin GTM se carga con la misma gtag.
        if (ids.ads && !ids.gtm) {
            if (!ids.ga4) {
                script(`https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ids.ads)}`);
                window.gtag('js', new Date());
            }
            window.gtag('config', ids.ads);
        }
        if (ids.meta) {
            /* eslint-disable */
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
            /* eslint-enable */
            window.fbq('init', ids.meta);
            window.fbq('track', 'PageView');
        }
        (window.mcEvents || []).splice(0).forEach(([name, params]) => send(name, params));
    }

    function send(name, params) {
        params = params || {};
        if (name === 'purchase' && params.transaction_id) {
            const key = `mc_purchase_${params.transaction_id}`;
            try { if (localStorage.getItem(key)) return; localStorage.setItem(key, '1'); } catch (e) { /* sin almacenamiento: se envía igual */ }
        }
        if (ids.gtm) {
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({ event: name, ecommerce: params });
        } else if (ids.ga4) {
            window.gtag('event', name, params);
        }
        if (name === 'purchase' && ids.ads_purchase && !ids.gtm) {
            window.gtag('event', 'conversion', { send_to: ids.ads_purchase, value: params.value, currency: params.currency, transaction_id: params.transaction_id });
        }
        if (ids.meta && window.fbq && META[name]) {
            const items = params.items || [];
            window.fbq('track', META[name], {
                value: params.value, currency: params.currency,
                content_ids: items.map((i) => i.item_id), content_type: 'product',
                contents: items.map((i) => ({ id: i.item_id, quantity: i.quantity })),
            }, params.transaction_id ? { eventID: params.transaction_id } : undefined);
        }
    }

    window.mcTrack = (name, params) => {
        if (loaded) send(name, params);
        // Sin consentimiento no se guarda ni se envía nada.
    };

    function clearTrackingCookies() {
        const host = location.hostname.split('.').slice(-2).join('.');
        document.cookie.split('; ').map((c) => c.split('=')[0]).filter((n) => /^(_ga|_gid|_gcl|_fbp|_fbc)/.test(n)).forEach((n) => {
            [location.hostname, `.${host}`].forEach((d) => { document.cookie = `${n}=; Max-Age=0; Path=/; Domain=${d}`; });
            document.cookie = `${n}=; Max-Age=0; Path=/`;
        });
    }

    function decide(v) {
        save(v);
        if (v === 'denied') clearTrackingCookies();
        banner && banner.classList.add('hidden');
        if (v === 'granted') load();
        else if (loaded) location.reload(); // retirar el consentimiento descarga las etiquetas
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-consent]');
        if (btn) { decide(btn.dataset.consent); return; }
        if (e.target.closest('[data-consent-open]')) { banner && banner.classList.remove('hidden'); return; }
        const t = e.target.closest('[data-track]');
        if (t) window.mcTrack(t.dataset.track, t.dataset.trackParams ? JSON.parse(t.dataset.trackParams) : {});
    });

    const form = document.querySelector('form[data-checkout-form]');
    if (form) {
        form.addEventListener('submit', () => {
            const pm = form.querySelector('input[name="payment_method"]:checked');
            window.mcTrack('add_payment_info', { currency: 'COP', payment_type: pm ? pm.value : undefined });
        });
    }

    const choice = read();
    if (choice === 'granted') load();
    else if (!choice && banner) banner.classList.remove('hidden');
})();

// Permisos de mensajes en el pago (#82 y #83): cada cambio de casilla se guarda al instante, siempre con el
// último estado, para poder recordar el carrito si el cliente no termina la compra. Nada viene marcado.
(function () {
    const box = document.querySelector('[data-messages-consent]');
    const form = box && box.closest('form');
    if (!box || !form) return;
    const token = form.querySelector('input[name="_token"]');
    let timer = null;
    let busy = false;
    let again = false;

    function payload() {
        const v = (n) => (form.querySelector(`[name="${n}"]`) || {}).value || '';
        const c = (n) => !!(form.querySelector(`[name="messages[${n}]"]`) || {}).checked;
        return { email: v('email'), phone: v('phone'), first_name: v('first_name'), messages: { email: c('email'), whatsapp: c('whatsapp') } };
    }

    async function sync(retry = true) {
        if (busy) { again = true; return; }
        busy = true;
        try {
            const r = await fetch(box.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token ? token.value : '' },
                body: JSON.stringify(payload()),
            });
            if (r.status >= 500 && retry) { busy = false; return sync(false); }
        } catch (e) {
            if (retry) { busy = false; return sync(false); }
        }
        busy = false;
        if (again) { again = false; sync(); }
    }

    const anyChecked = () => !!box.querySelector('input[type="checkbox"]:checked');
    box.addEventListener('change', () => { clearTimeout(timer); timer = setTimeout(sync, 250); });
    ['email', 'phone'].forEach((n) => {
        const el = form.querySelector(`[name="${n}"]`);
        if (el) el.addEventListener('change', () => { if (anyChecked()) sync(); });
    });
})();

// Pago claro (#87): el enlace de WhatsApp suma el medio de pago elegido al tocarlo (sin datos personales),
// y los datos de envío de un invitado se recuerdan mientras la pestaña siga abierta.
(function () {
    const form = document.querySelector('form[data-checkout-form]');
    if (!form) return;

    const wa = document.querySelector('[data-checkout-whatsapp]');
    if (wa) {
        wa.addEventListener('click', () => {
            const pm = form.querySelector('input[name="payment_method"]:checked');
            const label = pm ? (pm.closest('label').querySelector('.font-medium') || {}).textContent : '';
            const text = wa.dataset.text + (label ? `\nMedio de pago: ${label.trim()}` : '');
            wa.href = `https://wa.me/${wa.dataset.checkoutWhatsapp}?text=${encodeURIComponent(text)}`;
        });
    }

    // Solo campos de contacto y entrega; nunca el documento ni el medio de pago. Solo rellena campos vacíos.
    if (!form.hasAttribute('data-remember-fields')) return;
    const KEY = 'mc_ck_v1';
    const FIELDS = ['first_name', 'last_name', 'email', 'phone', 'department', 'city', 'address', 'address_2'];
    let saved = {};
    try { saved = JSON.parse(sessionStorage.getItem(KEY) || '{}'); } catch (e) { saved = {}; }
    FIELDS.forEach((n) => {
        const el = form.querySelector(`[name="${n}"]`);
        if (!el) return;
        if (!el.value && typeof saved[n] === 'string') el.value = saved[n];
        el.addEventListener('change', () => {
            saved[n] = el.value;
            try { sessionStorage.setItem(KEY, JSON.stringify(saved)); } catch (e) { /* sin almacenamiento */ }
        });
    });
})();

// Galería de la ficha: al tocar una miniatura cambia la foto grande.
(function () {
    const main = document.querySelector('[data-gallery-main]');
    if (!main) return;
    document.querySelectorAll('[data-gallery-thumb]').forEach((b) => {
        b.addEventListener('click', () => {
            main.src = b.dataset.galleryThumb;
            document.querySelectorAll('[data-gallery-thumb]').forEach((o) => {
                const on = o === b;
                o.classList.toggle('border-brand-500', on);
                o.classList.toggle('border-transparent', !on);
                on ? o.setAttribute('aria-current', 'true') : o.removeAttribute('aria-current');
            });
        });
    });
})();

// Mapa de cobertura (#33): Leaflet + OpenStreetMap, sin claves. Se descarga solo cuando la sección
// se acerca a la pantalla. Los puntos son ciudades con totales agregados (nunca datos de clientes).
const coverage = document.querySelector('[data-coverage-map]');
if (coverage) {
    const draw = async () => {
        const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);
        const points = JSON.parse(coverage.dataset.points || '[]');
        const map = L.map(coverage, { scrollWheelZoom: false, zoomSnap: 0.25, attributionControl: true }).setView([4.6, -74.2], 5);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 12, minZoom: 4,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
        }).addTo(map);
        const max = Math.max(1, ...points.map((p) => p[4]));
        const bounds = [];
        points.forEach(([city, dept, lat, lng, n]) => {
            const r = n ? 5 + Math.round(9 * Math.sqrt(n / max)) : 4;
            L.circleMarker([lat, lng], { radius: r, color: '#f4e2ac', weight: 1, fillColor: '#d9b871', fillOpacity: 0.85, className: 'mc-dot' })
                .bindTooltip(`<b>${city}</b>, ${dept}<br>${n ? `${n.toLocaleString('es-CO')} pedidos` : 'Ya llegamos aquí'}`, { direction: 'top', className: 'mc-tip' })
                .addTo(map);
            bounds.push([lat, lng]);
        });
        if (bounds.length > 1) map.fitBounds(bounds, { padding: [24, 24], maxZoom: 6 });
        coverage.classList.add('is-on');
    };
    const io = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) { io.disconnect(); draw().catch(() => coverage.remove()); }
    }, { rootMargin: '300px' });
    io.observe(coverage);
}

// Simulador de cuotas de «Compra ahora, paga después» (#59): valor ÷ cuotas, solo de referencia.
const sim = document.querySelector('[data-bnpl-sim]');
if (sim) {
    const cop = (n) => '$ ' + Math.round(n).toLocaleString('es-CO');
    const value = sim.querySelector('[data-sim-value]');
    const count = sim.querySelector('[data-sim-n]');
    const update = () => {
        const v = parseInt(value.value.replace(/\D/g, ''), 10) || 0;
        const n = parseInt(count.value, 10) || 1;
        value.value = v ? v.toLocaleString('es-CO') : '';
        sim.querySelector('[data-sim-total]').textContent = cop(v);
        sim.querySelector('[data-sim-count]').textContent = n;
        sim.querySelector('[data-sim-quota]').textContent = cop(Math.ceil(v / n));
    };
    value.addEventListener('input', update);
    count.addEventListener('change', update);
}

// Modo oscuro (#64): botón luna/sol; se recuerda en este navegador ("mc-theme") y avisa con "mc:tema".
document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
    const root = document.documentElement;
    const paint = () => {
        const dark = root.dataset.theme === 'dark';
        btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
        btn.setAttribute('aria-label', dark ? 'Modo claro' : 'Modo oscuro');
        btn.title = btn.getAttribute('aria-label');
        btn.querySelector('.mcdk-moon')?.classList.toggle('hidden', dark);
        btn.querySelector('.mcdk-sun')?.classList.toggle('hidden', !dark);
    };
    paint();
    btn.addEventListener('click', () => {
        const dark = root.dataset.theme !== 'dark';
        if (dark) root.dataset.theme = 'dark'; else delete root.dataset.theme;
        store.set('mc-theme', dark ? 'dark' : 'light');
        document.querySelectorAll('[data-theme-toggle]').forEach((b) => b.dispatchEvent(new Event('mc:paint')));
        document.dispatchEvent(new CustomEvent('mc:tema', { detail: { tema: dark ? 'dark' : 'light' } }));
    });
    btn.addEventListener('mc:paint', paint);
});

// Código QR del segundo factor (se descarga solo en esa pantalla).
document.querySelectorAll('canvas[data-qr]').forEach(async (canvas) => {
    const { default: QRCode } = await import('qrcode');
    QRCode.toCanvas(canvas, canvas.dataset.qr, { width: 180, margin: 1 });
});
