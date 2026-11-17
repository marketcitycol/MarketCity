<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Seller;
use App\Http\Controllers\SellerSignupController;
use Illuminate\Support\Facades\Route;

// --- Tienda pública (rutas compatibles con las del sitio actual para no perder SEO) ---
Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/shop', [CatalogController::class, 'index'])->name('shop');
Route::get('/categoria-producto/{category}', [CatalogController::class, 'category'])->name('category');
Route::get('/producto/{product}', [CatalogController::class, 'product'])->name('product');
Route::get('/tiendas', [CatalogController::class, 'stores'])->name('stores');
Route::get('/tienda/{store}', [CatalogController::class, 'store'])->name('store');
Route::get('/ofertas-relampago', [CatalogController::class, 'offers'])->name('offers');
Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');

Route::get('/carrito', [CartController::class, 'index'])->name('cart');
Route::post('/carrito', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito', [CartController::class, 'update'])->name('cart.update');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'place'])->middleware('throttle:20,1')->name('checkout.place');

Route::get('/pedido/{order}/pago-de-prueba', [OrderController::class, 'sandbox'])->name('payments.sandbox');
Route::post('/pedido/{order}/pago-de-prueba', [OrderController::class, 'sandboxDecide'])->name('payments.sandbox.decide');
Route::get('/pedido/{order}/gracias', [OrderController::class, 'thanks'])->name('orders.thanks');
Route::get('/pedido/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/rastrear-pedido', [OrderController::class, 'trackForm'])->name('track');
Route::post('/rastrear-pedido', [OrderController::class, 'track'])->middleware('throttle:10,1')->name('track.lookup');

Route::get('/vender', [SellerSignupController::class, 'show'])->name('sell');
Route::post('/vender', [SellerSignupController::class, 'store'])->middleware('throttle:5,1')->name('sell.store');

// --- Cuenta ---
Route::get('/ingresar', [AuthController::class, 'loginForm'])->name('login');
Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/registro', [AuthController::class, 'registerForm'])->name('register');
Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
Route::get('/mi-cuenta', [AuthController::class, 'account'])->middleware('auth')->name('account');

// --- Seller Center ---
Route::prefix('seller-center')->name('seller.')->middleware(['auth', 'role:seller'])->group(function () {
    Route::get('/', [Seller\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('productos', Seller\ProductController::class)->except('show')->parameters(['productos' => 'product'])->names('products');
    Route::get('/pedidos', [Seller\OrderController::class, 'index'])->name('orders');
    Route::get('/pedidos/{sellerOrder}', [Seller\OrderController::class, 'show'])->name('orders.show');
    Route::post('/pedidos/{sellerOrder}/estado', [Seller\OrderController::class, 'advance'])->name('orders.advance');
    Route::get('/liquidaciones', [Seller\DashboardController::class, 'settlements'])->name('settlements');
    Route::get('/tienda', [Seller\DashboardController::class, 'editStore'])->name('store.edit');
    Route::put('/tienda', [Seller\DashboardController::class, 'updateStore'])->name('store.update');
});

// --- Backoffice ---
Route::prefix('backoffice')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pedidos', [Admin\OrderController::class, 'index'])->name('orders');
    Route::get('/pedidos/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/pedidos/{order}/pago-recibido', [Admin\OrderController::class, 'markPaid'])->name('orders.paid');
    Route::post('/subpedidos/{sellerOrder}/estado', [Admin\OrderController::class, 'advance'])->name('orders.advance');
    Route::post('/subpedidos/{sellerOrder}/cancelar', [Admin\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/vendedores', [Admin\StoreController::class, 'index'])->name('stores');
    Route::get('/vendedores/{store}', [Admin\StoreController::class, 'edit'])->name('stores.edit');
    Route::put('/vendedores/{store}', [Admin\StoreController::class, 'update'])->name('stores.update');
    Route::post('/vendedores/{store}/estado', [Admin\StoreController::class, 'status'])->name('stores.status');
    Route::get('/productos', [Admin\StoreController::class, 'products'])->name('products');
    Route::post('/productos/{product}/estado', [Admin\StoreController::class, 'productStatus'])->name('products.status');
    Route::get('/liquidaciones', [Admin\SettlementController::class, 'index'])->name('settlements');
    Route::post('/liquidaciones/{sellerOrder}', [Admin\SettlementController::class, 'settle'])->name('settlements.settle');
    Route::get('/importar', [Admin\ImportController::class, 'show'])->name('import');
    Route::post('/importar', [Admin\ImportController::class, 'store'])->name('import.store');
    Route::get('/configuracion', [Admin\SettingsController::class, 'index'])->name('settings');
    Route::put('/configuracion', [Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::post('/titular', [Admin\SettingsController::class, 'storeEntity'])->name('entities.store');
    Route::post('/titular/{entity}/activar', [Admin\SettingsController::class, 'activateEntity'])->name('entities.activate');
    Route::get('/paginas', [Admin\PageController::class, 'index'])->name('pages');
    Route::get('/paginas/nueva', [Admin\PageController::class, 'create'])->name('pages.create');
    Route::post('/paginas', [Admin\PageController::class, 'store'])->name('pages.store');
    Route::post('/paginas/importar', [Admin\PageController::class, 'import'])->name('pages.import');
    Route::get('/paginas/{page}', [Admin\PageController::class, 'edit'])->name('pages.edit');
    Route::put('/paginas/{page}', [Admin\PageController::class, 'update'])->name('pages.update');
    Route::get('/plugins', [Admin\PluginController::class, 'index'])->name('plugins');
    Route::get('/plugins/{plugin}', [Admin\PluginController::class, 'edit'])->name('plugins.edit');
    Route::put('/plugins/{plugin}', [Admin\PluginController::class, 'update'])->name('plugins.update');
    Route::post('/plugins/{plugin}/activar', [Admin\PluginController::class, 'toggle'])->name('plugins.toggle');
    Route::post('/plugins/{plugin}/restablecer', [Admin\PluginController::class, 'reset'])->name('plugins.reset');
});

// --- Redirecciones 301 desde rutas duplicadas del sitio actual (route-and-state-inventory.md) ---
foreach (['cart', 'cart-2'] as $old) {
    Route::permanentRedirect($old, '/carrito');
}
Route::permanentRedirect('checkout-2', '/checkout');
Route::permanentRedirect('rastrea-tu-pedido', '/rastrear-pedido');
foreach (['my-account', 'my-account-2', 'my-orders'] as $old) {
    Route::permanentRedirect($old, '/mi-cuenta');
}
foreach (['vendor-membership', 'vender-2', 'become-a-vendor'] as $old) {
    Route::permanentRedirect($old, '/vender');
}
Route::permanentRedirect('sellercenter', '/seller-center');

// --- Páginas de contenido (políticas, contacto…). Va al final: cualquier ruta anterior tiene prioridad. ---
Route::permanentRedirect('contact-us', '/contacto');
Route::get('/{page}', [\App\Http\Controllers\PageController::class, 'show'])->where('page', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('page');
