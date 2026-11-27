<?php

namespace Tests\Feature;

use App\Mail\SellerNewOrderMail;
use App\Models\Address;
use App\Models\MarketingConsent;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\Content\Plugins;
use App\Services\Marketing\MessagingPermissions;
use App\Services\Orders\Cart;
use App\Support\Analytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Traslado de los snippets de WordPress, ola S2 (docs/producto/traslado-snippets.md). */
class SnippetsS2Test extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function checkoutData(array $extra = []): array
    {
        return array_merge([
            'first_name' => 'Ana', 'last_name' => 'Pérez', 'document_type' => 'CC', 'document_number' => '1012345678',
            'email' => 'ana@example.com', 'phone' => '3001112233', 'department' => 'Antioquia', 'city' => 'Medellín',
            'address' => 'Calle 10 # 43-12', 'payment_method' => 'sandbox', 'terms' => '1',
        ], $extra);
    }

    private function customer(): User
    {
        return User::where('email', 'cliente@marketcity.test')->firstOrFail();
    }

    private function placeOrder(array $extra = []): Order
    {
        $p = Product::visible()->where('stock', '>', 3)->first();
        $this->post('/carrito', ['product_id' => $p->id, 'quantity' => 2]);
        $this->post('/checkout', $this->checkoutData($extra))->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    // --- #48, #49, #79: Mi cuenta ---

    public function test_account_sections_render_for_customer(): void
    {
        $user = $this->customer();
        foreach (['/mi-cuenta', '/mi-cuenta/pedidos', '/mi-cuenta/direcciones', '/mi-cuenta/datos', '/mi-cuenta/seguridad', '/mi-cuenta/favoritos', '/mi-cuenta/notificaciones', '/mi-cuenta/consultas'] as $url) {
            $this->actingAs($user)->get($url)->assertOk()->assertSee('Mi cuenta');
        }
        $this->get('/mi-cuenta/direcciones')->assertOk();
        auth()->logout();
        $this->get('/mi-cuenta/pedidos')->assertRedirect('/ingresar');
    }

    public function test_addresses_crud_default_and_ownership(): void
    {
        $user = $this->customer();
        $data = ['label' => 'Casa', 'department' => 'Antioquia', 'city' => 'Medellín', 'address' => 'Calle 1 # 2-3'];
        $this->actingAs($user)->post('/mi-cuenta/direcciones', $data)->assertRedirect('/mi-cuenta/direcciones');
        $this->actingAs($user)->post('/mi-cuenta/direcciones', ['label' => 'Oficina', 'address' => 'Carrera 7 # 70-10'] + $data)->assertRedirect();
        [$home, $office] = [Address::where('label', 'Casa')->first(), Address::where('label', 'Oficina')->first()];
        $this->assertTrue($home->is_default);
        $this->assertFalse($office->is_default);

        $this->actingAs($user)->post("/mi-cuenta/direcciones/{$office->id}/principal")->assertRedirect();
        $this->assertTrue($office->fresh()->is_default);
        $this->assertFalse($home->fresh()->is_default);

        // El pago se llena con la dirección principal.
        $this->post('/carrito', ['product_id' => Product::visible()->where('stock', '>', 0)->value('id')]);
        $this->actingAs($user)->get('/checkout')->assertSee('Carrera 7 # 70-10', false);

        // Nadie más puede tocarla.
        $other = User::factory()->create(['role' => 'customer']);
        $this->actingAs($other)->delete("/mi-cuenta/direcciones/{$office->id}")->assertNotFound();
        $this->actingAs($user)->delete("/mi-cuenta/direcciones/{$office->id}")->assertRedirect();
        $this->assertTrue($home->fresh()->is_default, 'al borrar la principal, otra pasa a principal');
    }

    public function test_first_logged_in_purchase_saves_address_and_details(): void
    {
        $user = $this->customer();
        $user->update(['phone' => null, 'document_number' => null]);
        $this->actingAs($user);
        $this->placeOrder(['email' => $user->email]);
        $addr = $user->fresh()->defaultAddress();
        $this->assertSame('Calle 10 # 43-12', $addr->address);
        $this->assertSame('3001112233', $user->fresh()->phone);
        $this->assertSame('1012345678', $user->fresh()->document_number);
    }

    public function test_details_and_favorites(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->put('/mi-cuenta/datos', ['name' => 'Cliente Nuevo', 'phone' => '310 555 1234', 'document_type' => 'CC', 'document_number' => '79999999'])->assertRedirect();
        $this->assertSame('Cliente Nuevo', $user->fresh()->name);

        $p = Product::visible()->first();
        $this->actingAs($user)->get('/producto/'.$p->slug)->assertSee('Guardar en favoritos');
        $this->actingAs($user)->post("/favoritos/{$p->slug}")->assertRedirect();
        $this->actingAs($user)->get('/mi-cuenta/favoritos')->assertSee($p->name);
        $this->actingAs($user)->get('/producto/'.$p->slug)->assertSee('En tus favoritos');
        $this->actingAs($user)->postJson("/favoritos/{$p->slug}")->assertJson(['favorite' => false]);
        $this->assertSame(0, $user->favorites()->count());
        auth()->logout();
        $this->post("/favoritos/{$p->slug}")->assertRedirect('/ingresar');
    }

    public function test_notifications_preferences_record_consent(): void
    {
        $user = $this->customer();
        $user->update(['phone' => '3001234567']);
        $this->actingAs($user)->put('/mi-cuenta/notificaciones', ['email' => '1', 'whatsapp' => '1'])->assertRedirect();
        $perm = app(MessagingPermissions::class);
        $this->assertTrue($perm->allows(mb_strtolower($user->email), 'email', 'ofertas'));
        $this->assertTrue($perm->allows('573001234567', 'whatsapp', 'ofertas'));
        $this->assertSame('cuenta', MarketingConsent::latest('id')->value('source'));

        $this->actingAs($user)->put('/mi-cuenta/notificaciones', ['email' => '1'])->assertRedirect();
        $this->assertFalse($perm->allows('573001234567', 'whatsapp', 'ofertas'));
        $this->actingAs($user)->get('/mi-cuenta/notificaciones')->assertSee('checked', false);
    }

    public function test_delete_account_needs_password_keeps_orders_and_revokes_messages(): void
    {
        $user = $this->customer();
        $this->actingAs($user);
        $order = $this->placeOrder(['email' => $user->email, 'messages' => ['email' => '1']]);
        $this->assertTrue(app(MessagingPermissions::class)->allows(mb_strtolower($user->email), 'email'));

        $this->actingAs($user)->from('/mi-cuenta/seguridad')->delete('/mi-cuenta', ['current_password' => 'equivocada', 'confirm' => '1'])->assertSessionHasErrors('current_password');
        $this->actingAs($user)->delete('/mi-cuenta', ['current_password' => 'marketcity', 'confirm' => '1'])->assertRedirect('/');
        $this->assertNull(User::find($user->id));
        $this->assertGuest();
        $this->assertNotNull(Order::find($order->id), 'el pedido se conserva');
        $this->assertNull($order->fresh()->user_id);
        $this->assertFalse(app(MessagingPermissions::class)->allows(mb_strtolower($user->email), 'email'));

        $seller = User::where('email', 'vendedor1@marketcity.test')->first();
        $this->actingAs($seller)->delete('/mi-cuenta', ['current_password' => 'marketcity', 'confirm' => '1'])->assertSessionHas('error');
        $this->assertNotNull(User::find($seller->id));
    }

    // --- #47 y #62: crear cuenta ---

    public function test_failed_login_offers_account_with_same_message_for_any_email(): void
    {
        $this->from('/ingresar')->post('/ingresar', ['email' => 'nueva@example.com', 'password' => 'xxxxxxxx'])->assertRedirect('/ingresar');
        $this->get('/ingresar')->assertSee('Crear mi cuenta con este correo');
        $this->get('/registro')->assertSee('value="nueva@example.com"', false);

        // Con una cuenta que sí existe el mensaje es el mismo (no revela qué correos están registrados).
        $this->from('/ingresar')->post('/ingresar', ['email' => 'cliente@marketcity.test', 'password' => 'xxxxxxxx']);
        $this->get('/ingresar')->assertSee('Crear mi cuenta con este correo')->assertSee('El correo o la contraseña no coinciden');
    }

    public function test_guest_creates_account_from_thanks_page_with_set_password_email(): void
    {
        Notification::fake();
        $order = $this->placeOrder(['email' => 'Nuevo@Example.com']);
        $thanks = "/pedido/{$order->number}/gracias?t={$order->access_token}";
        $this->get($thanks)->assertSee('Crear mi cuenta');

        $this->post("/pedido/{$order->number}/crear-cuenta?t={$order->access_token}")->assertRedirect($thanks);
        $user = User::where('email', 'nuevo@example.com')->firstOrFail();
        $this->assertSame($user->id, $order->fresh()->user_id);
        $this->assertNull($user->email_verified_at);
        $this->assertGuest();
        $this->assertSame('Calle 10 # 43-12', $user->defaultAddress()->address);
        Notification::assertSentTo($user, ResetPasswordNotification::class);

        // Sin la sesión que hizo el pedido no se ofrece ni se acepta.
        $this->flushSession();
        $this->get($thanks)->assertDontSee('Crear mi cuenta');
        $this->post("/pedido/{$order->number}/crear-cuenta?t={$order->access_token}")->assertNotFound();
    }

    public function test_thanks_page_suggests_login_when_account_exists(): void
    {
        $order = $this->placeOrder(['email' => 'cliente@marketcity.test']);
        $this->get("/pedido/{$order->number}/gracias?t={$order->access_token}")->assertSee('Ya tienes una cuenta')->assertDontSee('Crear mi cuenta');
    }

    // --- #87: pago claro ---

    public function test_failed_payment_offers_plan_b_and_rebuilds_cart(): void
    {
        $order = $this->placeOrder();
        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'decline']);
        $thanks = $this->get("/pedido/{$order->number}/gracias?t={$order->access_token}");
        $thanks->assertSee('Pagar con otro medio')->assertSee('no se cobró nada')->assertDontSee('¡Gracias por tu compra');

        $this->post("/pedido/{$order->number}/pagar-con-otro-medio?t={$order->access_token}")->assertRedirect('/checkout');
        $item = $order->items()->first();
        $this->assertSame([$item->product_id => 2], session('cart.items') ?? session(Cart::KEY));
        $this->get('/checkout')->assertOk()->assertSee('Escríbenos por WhatsApp');

        $this->post("/pedido/{$order->number}/pagar-con-otro-medio?t=otro")->assertNotFound();
    }

    // --- #11, #7, #75: Open Graph, Google Ads y verificación de Meta ---

    public function test_open_graph_and_meta_domain_verification(): void
    {
        $p = Product::visible()->first();
        $res = $this->get('/producto/'.$p->slug);
        $res->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:title" content="'.e($p->name), false)
            ->assertSee('<meta property="product:price:amount" content="'.$p->price.'">', false)
            ->assertDontSee('facebook-domain-verification', false);

        Plugins::save('analytics', false, ['meta_domain_verification' => 'abc123def456ghi789', 'google_ads_id' => 'aw-123456789']);
        $this->get('/')->assertSee('<meta name="facebook-domain-verification" content="abc123def456ghi789">', false)
            ->assertSee('<meta property="og:type" content="website">', false);

        Plugins::save('analytics', true, ['google_ads_purchase' => 'AW-123456789/AbC_xyz1', 'meta_domain_verification' => '"><script>']);
        $ids = Analytics::ids();
        $this->assertSame('AW-123456789', $ids['ads']);
        $this->assertSame('AW-123456789/AbC_xyz1', $ids['ads_purchase']);
        $this->get('/')->assertDontSee('facebook-domain-verification', false);
    }

    // --- #14: Lo que debes saber ---

    public function test_product_shows_what_you_should_know_with_store_logistics(): void
    {
        $p = Product::visible()->first();
        $p->store->update(['dispatch_days' => 3, 'carriers' => 'Servientrega']);
        $this->get('/producto/'.$p->slug)->assertSee('Lo que debes saber')
            ->assertSee('máximo 3 días hábiles')->assertSee('Servientrega')
            ->assertSee('5 días hábiles')->assertSee('Reversión del pago');
    }

    // --- #68: directorio ---

    public function test_backoffice_features_and_hides_stores_in_directory(): void
    {
        $admin = User::where('role', 'admin')->first();
        $stores = Store::where('status', 'active')->get();
        $this->assertGreaterThanOrEqual(2, $stores->count());
        [$a, $b] = [$stores[0], $stores[1]];

        $this->actingAs($admin)->post("/backoffice/vendedores/{$a->slug}/directorio", ['directory_hidden' => '1'])->assertRedirect();
        $this->actingAs($admin)->post("/backoffice/vendedores/{$b->slug}/directorio", ['featured' => '1'])->assertRedirect();
        auth()->logout();

        $html = $this->get('/tiendas')->assertOk()->assertDontSee('>'.e($a->name).'</a></h3>', false)->assertSee('Destacada')->getContent();
        $this->get('/tienda/'.$a->slug)->assertOk();
        $this->get('/sitemap.xml')->assertDontSee('/tienda/'.$a->slug.'<', false);
        $this->assertNotFalse(strpos($html, e($b->name)));
    }

    // --- #69: perfil de vendedor ---

    public function test_seller_logistics_and_notification_preferences(): void
    {
        Mail::fake();
        $seller = User::where('email', 'vendedor1@marketcity.test')->first();
        $store = $seller->store;
        $this->actingAs($seller)->get('/seller-center/seguridad')->assertOk()->assertSee('Cambiar contraseña');
        $this->actingAs($seller)->put('/seller-center/tienda', [
            'name' => $store->name, 'department' => $store->department, 'city' => $store->city,
            'dispatch_days' => 4, 'carriers' => 'Coordinadora', 'notify_email' => 'Pedidos@Tienda.co', 'notify_new_order' => '0',
        ])->assertRedirect();
        $store->refresh();
        $this->assertSame(4, $store->dispatch_days);
        $this->assertSame('pedidos@tienda.co', $store->notificationEmail());
        $this->assertFalse($store->wantsNewOrderEmail());

        auth()->logout();
        $p = $store->products()->visible()->where('stock', '>', 0)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        $order = Order::latest('id')->first();
        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'approve']);
        Mail::assertNotSent(SellerNewOrderMail::class);

        $store->update(['notify_new_order' => true]);
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        $order = Order::latest('id')->first();
        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'approve']);
        Mail::assertSent(SellerNewOrderMail::class, fn ($m) => $m->hasTo('pedidos@tienda.co'));
    }
}
