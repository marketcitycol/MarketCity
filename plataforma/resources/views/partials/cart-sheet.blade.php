<div id="mc-cart-sheet" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="mc-cart-sheet-title" aria-hidden="true">
    <div class="absolute inset-0 bg-black/40" data-sheet-close></div>
    <div class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-white p-5 shadow-xl sm:inset-x-auto sm:top-0 sm:right-0 sm:bottom-auto sm:m-4 sm:w-96 sm:rounded-2xl">
        <div class="flex items-center justify-between">
            <p id="mc-cart-sheet-title" class="font-semibold text-emerald-700">Agregado al carrito</p>
            <button type="button" class="rounded-lg p-2 hover:bg-slate-100" data-sheet-close aria-label="Cerrar">✕</button>
        </div>
        <div class="mt-3 flex items-center gap-3">
            <img data-sheet-img class="hidden h-16 w-16 rounded-lg object-cover" alt="">
            <div><p class="font-medium" data-sheet-name></p><p class="text-sm text-slate-500" data-sheet-price></p></div>
        </div>
        <p class="mt-4 flex justify-between text-sm"><span>Subtotal del carrito</span><strong data-sheet-subtotal></strong></p>
        <p class="hidden mt-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" data-sheet-error></p>
        <div class="mt-4 grid gap-2">
            <a href="{{ route('checkout') }}" class="btn-primary text-center" data-sheet-primary>Ir a pagar</a>
            <a href="{{ route('cart') }}" class="btn-ghost text-center">Ver el carrito</a>
            <button type="button" class="text-sm text-brand-600 hover:underline" data-sheet-close>Seguir comprando</button>
        </div>
    </div>
</div>
