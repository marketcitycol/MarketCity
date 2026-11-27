<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\Legacy as AdminLegacy;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Seller;
use App\Http\Controllers\Shop\AccountController;
use App\Http\Controllers\Shop\CampaignController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\ContactController;
use App\Http\Controllers\Shop\MessagesController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\PaymentController;
use App\Http\Controllers\Shop\PqrController;
use App\Http\Controllers\Shop\ProductQuestionController;
use App\Http\Controllers\Shop\SellerSignupController;
use App\Http\Controllers\Shop\SitemapController;
use Illuminate\Support\Facades\Route;

// --- Tienda pública (rutas compatibles con las del sitio actual para no perder SEO) ---
Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/shop', [CatalogController::class, 'index'])->name('shop');
Route::get('/categoria-producto/{category}', [CatalogController::class, 'category'])->name('category');
Route::get('/producto/{product}', [CatalogController::class, 'product'])->name('product');
Route::post('/producto/{product}/preguntas', [ProductQuestionController::class, 'store'])->middleware('throttle:5,10')->name('product.questions.store');
Route::get('/tiendas', [CatalogController::class, 'stores'])->name('stores');
Route::get('/tienda/{store}', [CatalogController::class, 'store'])->name('store');
Route::get('/ofertas-relampago', [CatalogController::class, 'offers'])->name('offers');
Route::get('/campana/{campaign}', [CampaignController::class, 'show'])->name('campaign');
Route::post('/producto/{product}/precio-con-correo', [CampaignController::class, 'emailPrice'])->middleware('throttle:5,10')->name('product.email-price');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/catalogo/productos.xml', [SitemapController::class, 'feed'])->name('feed');

Route::get('/carrito', [CartController::class, 'index'])->name('cart');
Route::post('/carrito', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito', [CartController::class, 'update'])->name('cart.update');
Route::post('/carrito/cupon', [CartController::class, 'coupon'])->middleware('throttle:10,1')->name('cart.coupon');
Route::delete('/carrito/cupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
Route::get('/carrito/recuperar/{token}', [CartController::class, 'recover'])->middleware('throttle:20,1')->name('cart.recover');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'place'])->middleware('throttle:20,1')->name('checkout.place');
Route::post('/checkout/permisos', [CheckoutController::class, 'messages'])->middleware('throttle:30,1')->name('checkout.messages');
Route::get('/mensajes/baja', [MessagesController::class, 'show'])->middleware(['signed', 'throttle:20,1'])->name('marketing.unsubscribe');
Route::post('/mensajes/baja', [MessagesController::class, 'unsubscribe'])->middleware(['signed', 'throttle:20,1']);

Route::get('/pedido/{order}/pago-de-prueba', [OrderController::class, 'sandbox'])->name('payments.sandbox');
Route::post('/pedido/{order}/pago-de-prueba', [OrderController::class, 'sandboxDecide'])->name('payments.sandbox.decide');
Route::post('/pagos/aviso/{gateway}', [PaymentController::class, 'webhook'])->middleware('throttle:120,1')->name('payments.webhook');
Route::get('/pagos/regreso/{gateway}/{order}', [PaymentController::class, 'return'])->middleware('throttle:30,1')->name('payments.return');
Route::get('/pedido/{order}/gracias', [OrderController::class, 'thanks'])->name('orders.thanks');
Route::get('/pedido/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/rastrear-pedido', [OrderController::class, 'trackForm'])->name('track');
Route::post('/rastrear-pedido', [OrderController::class, 'track'])->middleware('throttle:30,1')->name('track.lookup');

Route::get('/pqrs', [PqrController::class, 'create'])->name('pqrs.create');
Route::post('/pqrs', [PqrController::class, 'store'])->middleware('throttle:5,10')->name('pqrs.store');
Route::get('/contacto', [ContactController::class, 'show'])->name('contact');
Route::get('/compra-ahora-paga-despues', [PageController::class, 'bnpl'])->name('bnpl');
Route::post('/contacto', [ContactController::class, 'store'])->middleware('throttle:5,10')->name('contact.store');
Route::get('/pqrs/estado', [PqrController::class, 'status'])->name('pqrs.status');
Route::post('/pqrs/consultar', [PqrController::class, 'lookup'])->middleware('throttle:10,1')->name('pqrs.lookup');

Route::get('/vender', [SellerSignupController::class, 'show'])->name('sell');
Route::post('/vender', [SellerSignupController::class, 'store'])->middleware('throttle:5,1')->name('sell.store');

// --- Cuenta ---
Route::get('/ingresar', [AuthController::class, 'loginForm'])->name('login');
Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/registro', [AuthController::class, 'registerForm'])->name('register');
Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
Route::get('/olvide-mi-contrasena', [PasswordController::class, 'requestForm'])->middleware('guest')->name('password.request');
Route::post('/olvide-mi-contrasena', [PasswordController::class, 'sendLink'])->middleware(['guest', 'throttle:5,1'])->name('password.email');
Route::get('/restablecer-contrasena/{token}', [PasswordController::class, 'resetForm'])->middleware('guest')->name('password.reset');
Route::post('/restablecer-contrasena', [PasswordController::class, 'reset'])->middleware(['guest', 'throttle:10,1'])->name('password.update');
Route::post('/mi-cuenta/verificar-correo', [EmailVerificationController::class, 'send'])->middleware(['auth', 'throttle:3,10'])->name('verification.send');
Route::get('/verificar-correo/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:10,1'])->name('verification.verify');
Route::put('/mi-cuenta/contrasena', [PasswordController::class, 'update'])->middleware(['auth', 'throttle:5,1'])->name('password.change');
Route::post('/pedido/{order}/crear-cuenta', [OrderController::class, 'createAccount'])->middleware('throttle:5,10')->name('orders.account');
Route::post('/pedido/{order}/pagar-con-otro-medio', [OrderController::class, 'retry'])->middleware('throttle:10,1')->name('orders.retry');

// --- Mi cuenta (#48, #49, #79) ---
Route::prefix('mi-cuenta')->middleware('auth')->controller(AccountController::class)->group(function () {
    Route::get('/', 'index')->name('account');
    Route::get('/pedidos', 'orders')->name('account.orders');
    Route::get('/direcciones', 'addresses')->name('account.addresses');
    Route::post('/direcciones', 'storeAddress')->middleware('throttle:20,1')->name('account.addresses.store');
    Route::put('/direcciones/{address}', 'updateAddress')->name('account.addresses.update');
    Route::post('/direcciones/{address}/principal', 'defaultAddress')->name('account.addresses.default');
    Route::delete('/direcciones/{address}', 'destroyAddress')->name('account.addresses.destroy');
    Route::get('/datos', 'details')->name('account.details');
    Route::put('/datos', 'updateDetails')->name('account.details.update');
    Route::get('/seguridad', 'security')->name('account.security');
    Route::delete('/', 'destroy')->middleware('throttle:5,10')->name('account.destroy');
    Route::get('/favoritos', 'favorites')->name('account.favorites');
    Route::get('/notificaciones', 'notifications')->name('account.notifications');
    Route::put('/notificaciones', 'updateNotifications')->name('account.notifications.update');
    Route::get('/consultas', 'inquiries')->name('account.inquiries');
});
Route::post('/favoritos/{product}', [AccountController::class, 'toggleFavorite'])->middleware(['auth', 'throttle:60,1'])->name('favorites.toggle');

// --- Seller Center ---
Route::prefix('seller-center')->name('seller.')->middleware(['auth', 'role:seller'])->group(function () {
    Route::get('/', [Seller\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('productos', Seller\ProductController::class)->except('show')->parameters(['productos' => 'product'])->names('products');
    Route::get('/pedidos', [Seller\OrderController::class, 'index'])->name('orders');
    Route::get('/pedidos/{sellerOrder}', [Seller\OrderController::class, 'show'])->name('orders.show');
    Route::post('/pedidos/{sellerOrder}/estado', [Seller\OrderController::class, 'advance'])->name('orders.advance');
    Route::get('/liquidaciones', [Seller\DashboardController::class, 'settlements'])->name('settlements');
    Route::get('/preguntas', [Seller\QuestionController::class, 'index'])->name('questions');
    Route::post('/preguntas/{question}', [Seller\QuestionController::class, 'answer'])->middleware('throttle:30,1')->name('questions.answer');
    Route::get('/tienda', [Seller\DashboardController::class, 'editStore'])->name('store.edit');
    Route::put('/tienda', [Seller\DashboardController::class, 'updateStore'])->name('store.update');
    Route::get('/datos-legales', [Seller\LegalController::class, 'show'])->name('legal');
    Route::put('/datos-legales', [Seller\LegalController::class, 'updateLegal'])->name('legal.update');
    Route::post('/cuenta-bancaria', [Seller\LegalController::class, 'requestBankChange'])->middleware('throttle:5,10')->name('legal.bank');
    Route::get('/seguridad', [Seller\DashboardController::class, 'security'])->name('security');
});

// --- Backoffice ---
Route::prefix('backoffice')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pedidos', [Admin\OrderController::class, 'index'])->name('orders');
    Route::get('/pedidos/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/pedidos/{order}/pago-recibido', [Admin\OrderController::class, 'markPaid'])->name('orders.paid');
    Route::post('/pedidos/{order}/reembolso', [Admin\OrderController::class, 'refund'])->name('orders.refund');
    Route::post('/pedidos/{order}/nota', [Admin\OrderController::class, 'note'])->middleware('throttle:20,1')->name('orders.note');
    Route::post('/subpedidos/{sellerOrder}/estado', [Admin\OrderController::class, 'advance'])->name('orders.advance');
    Route::post('/subpedidos/{sellerOrder}/cancelar', [Admin\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/vendedores', [Admin\StoreController::class, 'index'])->name('stores');
    Route::get('/vendedores/{store}', [Admin\StoreController::class, 'edit'])->name('stores.edit');
    Route::put('/vendedores/{store}', [Admin\StoreController::class, 'update'])->name('stores.update');
    Route::post('/vendedores/{store}/directorio', [Admin\StoreController::class, 'directory'])->name('stores.directory');
    Route::post('/vendedores/{store}/estado', [Admin\StoreController::class, 'status'])->name('stores.status');
    Route::post('/vendedores/{store}/cuenta/{account}/aprobar', [Admin\StoreLegalController::class, 'approve'])->name('stores.bank.approve');
    Route::post('/vendedores/{store}/cuenta/{account}/rechazar', [Admin\StoreLegalController::class, 'reject'])->name('stores.bank.reject');
    Route::post('/vendedores/{store}/verificada', [Admin\StoreLegalController::class, 'verify'])->name('stores.verify');
    Route::get('/vendedores/{store}/rut', [Admin\StoreLegalController::class, 'rut'])->name('stores.rut');
    Route::get('/productos', [Admin\StoreController::class, 'products'])->name('products');
    Route::post('/productos/{product}/estado', [Admin\StoreController::class, 'productStatus'])->name('products.status');
    Route::get('/liquidaciones', [Admin\SettlementController::class, 'index'])->name('settlements');
    Route::get('/preguntas', [Admin\QuestionController::class, 'index'])->name('questions');
    Route::get('/contacto', [Admin\ContactController::class, 'index'])->name('contact');
    Route::post('/contacto/{message}', [Admin\ContactController::class, 'handle'])->name('contact.handle');
    Route::post('/preguntas/{question}/visibilidad', [Admin\QuestionController::class, 'toggle'])->name('questions.toggle');
    Route::post('/liquidaciones/{sellerOrder}', [Admin\SettlementController::class, 'settle'])->name('settlements.settle');
    Route::get('/importar', [AdminLegacy\CatalogImportController::class, 'show'])->name('import');
    Route::post('/importar', [AdminLegacy\CatalogImportController::class, 'store'])->name('import.store');
    Route::post('/importar/fotos', [AdminLegacy\CatalogImportController::class, 'media'])->name('import.media');
    Route::get('/configuracion', [Admin\SettingsController::class, 'index'])->name('settings');
    Route::put('/configuracion', [Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::put('/configuracion/transferencia', [Admin\SettingsController::class, 'updateTransfer'])->name('settings.transfer');
    Route::post('/titular', [Admin\SettingsController::class, 'storeEntity'])->name('entities.store');
    Route::post('/titular/{entity}/activar', [Admin\SettingsController::class, 'activateEntity'])->name('entities.activate');
    Route::post('/importar/pedidos', [AdminLegacy\OrderImportController::class, 'orders'])->name('import.orders');
    Route::get('/cambio-de-dominio', [AdminLegacy\CutoverController::class, 'index'])->name('cutover');
    Route::post('/cambio-de-dominio/revisar', [AdminLegacy\CutoverController::class, 'start'])->name('cutover.start');
    Route::post('/cambio-de-dominio/lote', [AdminLegacy\CutoverController::class, 'batch'])->name('cutover.batch');
    Route::get('/cambio-de-dominio/detalle.csv', [AdminLegacy\CutoverController::class, 'csv'])->name('cutover.csv');
    Route::get('/pqrs', [Admin\PqrController::class, 'index'])->name('pqrs');
    Route::get('/pqrs/{pqr}', [Admin\PqrController::class, 'show'])->name('pqrs.show');
    Route::put('/pqrs/{pqr}', [Admin\PqrController::class, 'update'])->name('pqrs.update');
    Route::get('/paginas', [Admin\PageController::class, 'index'])->name('pages');
    Route::get('/paginas/nueva', [Admin\PageController::class, 'create'])->name('pages.create');
    Route::post('/paginas', [Admin\PageController::class, 'store'])->name('pages.store');
    Route::post('/paginas/importar', [Admin\PageController::class, 'import'])->name('pages.import');
    Route::get('/paginas/{page}', [Admin\PageController::class, 'edit'])->name('pages.edit');
    Route::put('/paginas/{page}', [Admin\PageController::class, 'update'])->name('pages.update');
    Route::get('/campanas', [Admin\CampaignController::class, 'index'])->name('campaigns');
    Route::get('/campanas/nueva', [Admin\CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campanas', [Admin\CampaignController::class, 'store'])->name('campaigns.store');
    Route::put('/campanas/corte-de-despacho', [Admin\CampaignController::class, 'cutoff'])->name('campaigns.cutoff');
    Route::get('/campanas/{campaign}', [Admin\CampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('/campanas/{campaign}', [Admin\CampaignController::class, 'update'])->name('campaigns.update');
    Route::post('/campanas/{campaign}/productos', [Admin\CampaignController::class, 'addProduct'])->name('campaigns.products');
    Route::delete('/campanas/{campaign}/productos/{item}', [Admin\CampaignController::class, 'removeProduct'])->name('campaigns.products.remove');
    Route::get('/cupones', [Admin\CouponController::class, 'index'])->name('coupons');
    Route::get('/cupones/nuevo', [Admin\CouponController::class, 'create'])->name('coupons.create');
    Route::post('/cupones', [Admin\CouponController::class, 'store'])->name('coupons.store');
    Route::get('/cupones/{coupon}', [Admin\CouponController::class, 'edit'])->name('coupons.edit');
    Route::put('/cupones/{coupon}', [Admin\CouponController::class, 'update'])->name('coupons.update');
    Route::get('/mensajes', [Admin\MessagesController::class, 'index'])->name('messages');
    Route::put('/mensajes', [Admin\MessagesController::class, 'update'])->name('messages.update');
    Route::post('/mensajes/baja', [Admin\MessagesController::class, 'revoke'])->name('messages.revoke');
    Route::get('/mensajes/permisos.csv', [Admin\MessagesController::class, 'csv'])->name('messages.csv');
    Route::post('/mensajes/carritos/{cart}/whatsapp', [Admin\MessagesController::class, 'whatsappSent'])->name('messages.whatsapp');
    Route::get('/plugins', [Admin\PluginController::class, 'index'])->name('plugins');
    Route::get('/plugins/{plugin}', [Admin\PluginController::class, 'edit'])->name('plugins.edit');
    Route::put('/plugins/{plugin}', [Admin\PluginController::class, 'update'])->name('plugins.update');
    Route::post('/plugins/{plugin}/activar', [Admin\PluginController::class, 'toggle'])->name('plugins.toggle');
    Route::post('/plugins/{plugin}/restablecer', [Admin\PluginController::class, 'reset'])->name('plugins.reset');
});

// Las redirecciones 301 desde las URL de WordPress viven en App\Http\Middleware\LegacyRedirects.

// --- Páginas de contenido (políticas, contacto…). Va al final: cualquier ruta anterior tiene prioridad. ---
Route::get('/{page}', [PageController::class, 'show'])->where('page', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('page');
