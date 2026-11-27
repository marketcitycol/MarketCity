<?php

namespace Tests\Feature;

use App\Mail\OrderNoticeMail;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreBankAccount;
use App\Models\User;
use App\Services\Campaigns\CampaignService;
use App\Support\SearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Pendientes P1 frente a WooCommerce: cupones generales, reembolsos, búsqueda con precios y límite por cliente en ofertas. */
class CouponsRefundsSearchTest extends TestCase
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

    /** Producto sin oferta, con stock y precio fijo, para cuentas exactas. */
    private function product(int $price = 100000): Product
    {
        $p = Product::visible()->where('stock', '>', 2)->whereHas('store', fn ($s) => $s->whereNull('payment_methods')->orWhere('payment_methods', 'like', '%sandbox%'))->first();
        $p->update(['price' => $price, 'regular_price' => null, 'stock' => 20]);

        return $p->fresh();
    }

    private function approvedAccount(Product $p): void
    {
        StoreBankAccount::create(['store_id' => $p->store_id, 'bank' => 'Bancolombia', 'account_type' => 'Ahorros', 'account_number' => '1234567890',
            'holder_name' => 'Tienda SAS', 'holder_document_type' => 'NIT', 'holder_document_number' => '900123456', 'status' => 'approved']);
    }

    private function pay(Order $order): void
    {
        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'approve']);
    }

    // --- Cupones ---

    public function test_site_coupon_is_paid_by_marketcity_and_does_not_lower_seller_net(): void
    {
        Mail::fake();
        $p = $this->product(100000);
        Coupon::create(['code' => 'BIENVENIDA10', 'type' => 'percent', 'amount' => 10, 'usage_limit_per_customer' => 1]);

        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/carrito/cupon', ['code' => ' bienvenida10 '])->assertSessionHas('success');
        $this->get('/carrito')->assertSee('BIENVENIDA10')->assertSee('−$ 10.000', false);
        $this->post('/checkout', $this->checkoutData())->assertRedirect();

        $order = Order::latest('id')->first();
        $so = $order->sellerOrders->first();
        $this->assertSame(10000, $order->discount_total);
        $this->assertSame('BIENVENIDA10', $order->coupon_code);
        $this->assertSame(100000 + $so->shipping_total - 10000, $order->total);
        $this->assertSame('marketcity', $so->discount_funded_by);
        // Comisión sobre el valor de lista; el vendedor recibe lo mismo que sin cupón.
        $rate = (float) $so->commission_rule['percent'];
        $this->assertSame((int) round(100000 * $rate / 100), $so->commission_amount);
        $this->assertSame(100000 + $so->shipping_total - $so->commission_amount, $so->seller_net);

        // Un uso por cliente: el mismo correo ya no puede usarlo.
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/carrito/cupon', ['code' => 'BIENVENIDA10']);
        $this->post('/checkout', $this->checkoutData())->assertRedirect('/carrito')->assertSessionHas('error', fn ($e) => str_contains($e, 'Ya usaste ese cupón'));

        // Con otro correo sí.
        $this->post('/checkout', $this->checkoutData(['email' => 'otra@example.com']))->assertRedirect();
        $this->assertSame(2, Order::where('coupon_code', 'BIENVENIDA10')->count());
    }

    public function test_store_coupon_only_applies_to_that_store_and_lowers_its_net(): void
    {
        $p = $this->product(200000);
        $other = Product::visible()->where('store_id', '!=', $p->store_id)->where('stock', '>', 0)->first();
        Coupon::create(['code' => 'TIENDA20', 'type' => 'fixed', 'amount' => 20000, 'store_id' => $p->store_id, 'min_subtotal' => 150000]);

        $this->post('/carrito', ['product_id' => $other->id]);
        $this->post('/carrito/cupon', ['code' => 'TIENDA20'])->assertSessionHas('error', fn ($e) => str_contains($e, 'solo para productos de'));

        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/carrito/cupon', ['code' => 'TIENDA20'])->assertSessionHas('success');
        $this->post('/checkout', $this->checkoutData())->assertRedirect();

        $order = Order::latest('id')->first();
        $so = $order->sellerOrders->firstWhere('store_id', $p->store_id);
        $this->assertSame(20000, $order->discount_total);
        $this->assertSame(20000, $so->discount_total);
        $this->assertSame(0, (int) $order->sellerOrders->firstWhere('store_id', $other->store_id)->discount_total);
        $rate = (float) $so->commission_rule['percent'];
        $this->assertSame((int) round(180000 * $rate / 100), $so->commission_amount);
        $this->assertSame(200000 + $so->shipping_total - 20000 - $so->commission_amount, $so->seller_net);
    }

    public function test_coupon_rules_dates_minimum_total_uses_and_sale_items(): void
    {
        $p = $this->product(50000);
        Coupon::create(['code' => 'VENCIDO', 'type' => 'fixed', 'amount' => 5000, 'ends_at' => now()->subDay()]);
        Coupon::create(['code' => 'MINIMO', 'type' => 'fixed', 'amount' => 5000, 'min_subtotal' => 80000]);
        Coupon::create(['code' => 'UNO', 'type' => 'fixed', 'amount' => 5000, 'usage_limit' => 1]);
        Coupon::create(['code' => 'SINOFERTAS', 'type' => 'percent', 'amount' => 50, 'exclude_sale_items' => true]);
        $this->post('/carrito', ['product_id' => $p->id]);

        $this->post('/carrito/cupon', ['code' => 'VENCIDO'])->assertSessionHas('error', 'Ese cupón ya venció.');
        $this->post('/carrito/cupon', ['code' => 'MINIMO'])->assertSessionHas('error', fn ($e) => str_contains($e, 'compra mínima'));

        $this->post('/carrito/cupon', ['code' => 'UNO'])->assertSessionHas('success');
        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/carrito/cupon', ['code' => 'UNO'])->assertSessionHas('error', fn ($e) => str_contains($e, 'número de usos'));

        // Un pedido cancelado libera el uso.
        $first = Order::where('coupon_code', 'UNO')->first();
        $this->post("/pedido/{$first->number}/pago-de-prueba?t={$first->access_token}", ['decision' => 'decline']);
        $this->post('/carrito/cupon', ['code' => 'UNO'])->assertSessionHas('success');
        $this->delete('/carrito/cupon')->assertRedirect('/carrito');

        $p->update(['regular_price' => 70000]);
        $this->post('/carrito/cupon', ['code' => 'SINOFERTAS'])->assertSessionHas('error', fn ($e) => str_contains($e, 'no aplica'));
    }

    public function test_free_shipping_coupon_and_backoffice_crud(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->get('/backoffice/cupones')->assertOk()->assertSee('Nuevo cupón');
        $this->actingAs($admin)->post('/backoffice/cupones', ['code' => 'envío!', 'type' => 'free_shipping', 'active' => '1'])
            ->assertSessionHasErrors('code');
        $this->actingAs($admin)->post('/backoffice/cupones', ['code' => 'percent150', 'type' => 'percent', 'amount' => 150, 'active' => '1'])
            ->assertSessionHasErrors('amount');
        $this->actingAs($admin)->post('/backoffice/cupones', ['code' => 'enviogratis', 'type' => 'free_shipping', 'active' => '1', 'ends_at' => now()->addDay()->format('Y-m-d\TH:i')])
            ->assertRedirect();
        $coupon = Coupon::where('code', 'ENVIOGRATIS')->firstOrFail();
        $this->assertSame(0, $coupon->amount);
        $this->actingAs($admin)->get('/backoffice/cupones')->assertSee('ENVIOGRATIS')->assertSee('Envío gratis');
        auth()->logout();

        $p = $this->product(100000);
        $p->store->update(['free_shipping' => false, 'shipping_fee' => 12000]);
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/carrito/cupon', ['code' => 'ENVIOGRATIS'])->assertSessionHas('success');
        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        $order = Order::latest('id')->first();
        $this->assertSame(12000, $order->discount_total);
        $this->assertSame(100000, $order->total);
    }

    // --- Reembolsos ---

    public function test_partial_refund_charged_to_store_returns_commission_and_emails_customer(): void
    {
        Mail::fake();
        $p = $this->product(100000);
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::latest('id')->first();
        $admin = User::where('role', 'admin')->first();

        // Sin pagar no se reembolsa.
        $this->actingAs($admin)->post("/backoffice/pedidos/{$order->number}/reembolso", ['seller_order_id' => $order->sellerOrders->first()->id, 'amount' => 1000, 'reason' => 'x', 'charged_to' => 'tienda'])
            ->assertSessionHas('error', 'Solo se reembolsan pedidos pagados.');

        $this->pay($order);
        $so = $order->sellerOrders()->first();
        $commission = $so->commission_amount;
        $net = $so->seller_net;
        $this->actingAs($admin)->get("/backoffice/pedidos/{$order->number}")->assertSee('Registrar reembolso');
        $this->actingAs($admin)->post("/backoffice/pedidos/{$order->number}/reembolso", [
            'seller_order_id' => $so->id, 'amount' => 40000, 'reason' => 'Garantía', 'charged_to' => 'tienda', 'reference' => 'RF-1',
        ])->assertSessionHas('success');

        $so->refresh();
        $order->refresh();
        $reversed = (int) round(40000 * (float) $so->commission_rule['percent'] / 100);
        $this->assertSame($commission - $reversed, $so->commission_amount);
        $this->assertSame($net - (40000 - $reversed), $so->seller_net);
        $this->assertSame(40000, $order->refunded_total);
        $this->assertSame('paid', $order->payment_status);
        Mail::assertSent(OrderNoticeMail::class, fn ($m) => $m->kind === 'refunded' && $m->amount === 40000 && $m->hasTo('ana@example.com'));
        $this->assertStringContainsString('Te reembolsamos $ 40.000', (new OrderNoticeMail($order, 'refunded', 'Garantía', $so, 40000))->render());

        // No se puede reembolsar más de lo pagado.
        $this->actingAs($admin)->post("/backoffice/pedidos/{$order->number}/reembolso", ['seller_order_id' => $so->id, 'amount' => $order->total, 'reason' => 'x', 'charged_to' => 'tienda'])
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'debe estar entre'));
    }

    public function test_full_refund_of_undelivered_order_cancels_restocks_and_marks_refunded(): void
    {
        Mail::fake();
        $p = $this->product(100000);
        $this->post('/carrito', ['product_id' => $p->id, 'quantity' => 2]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::latest('id')->first();
        $this->pay($order);
        $this->assertSame(18, $p->fresh()->stock);
        $so = $order->sellerOrders()->first();
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->post("/backoffice/pedidos/{$order->number}/reembolso", [
            'seller_order_id' => $so->id, 'amount' => $order->total, 'reason' => 'Retracto', 'charged_to' => 'tienda', 'restock' => '1',
        ])->assertSessionHas('success');

        $this->assertSame(20, $p->fresh()->stock);
        $this->assertSame('cancelado', $so->fresh()->status);
        $this->assertSame(0, $so->fresh()->seller_net);
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame('Reembolsado', $order->fresh()->paymentStatusLabel());
        $this->get("/pedido/{$order->number}?t={$order->access_token}")->assertSee('Reembolso de');
    }

    public function test_refund_after_settlement_is_left_as_pending_deduction(): void
    {
        Mail::fake();
        $p = $this->product(100000);
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::latest('id')->first();
        $this->pay($order);
        $so = $order->sellerOrders()->first();
        $so->update(['status' => 'entregado', 'settled_at' => now(), 'settlement_reference' => 'LQ-1']);
        $net = $so->seller_net;
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->post("/backoffice/pedidos/{$order->number}/reembolso", [
            'seller_order_id' => $so->id, 'amount' => 30000, 'reason' => 'Defecto', 'charged_to' => 'tienda',
        ])->assertSessionHas('success', fn ($m) => str_contains($m, 'próxima liquidación'));
        $this->assertSame($net, $so->fresh()->seller_net);
        $this->assertTrue($order->refunds()->first()->pending_deduction);
    }

    public function test_pending_deduction_is_subtracted_from_next_settlements(): void
    {
        Mail::fake();
        $p = $this->product(100000);
        $this->approvedAccount($p);
        $admin = User::where('role', 'admin')->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData());
        $first = Order::latest('id')->first();
        $this->pay($first);
        $so1 = $first->sellerOrders()->first();
        $so1->update(['status' => 'entregado', 'settled_at' => now(), 'settlement_reference' => 'LQ-1']);
        $this->actingAs($admin)->post("/backoffice/pedidos/{$first->number}/reembolso", [
            'seller_order_id' => $so1->id, 'amount' => 100000, 'reason' => 'Retracto', 'charged_to' => 'tienda',
        ]);
        $debit = $first->refunds()->first()->seller_debit;
        $this->assertGreaterThan(0, $debit);
        $this->assertSame($debit, $so1->store->pendingDeduction());

        // Venta nueva y pequeña de la misma tienda: no alcanza a cubrir todo el descuento.
        auth()->logout();
        $p->update(['price' => 30000]);
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData(['email' => 'luis@example.com']));
        $second = Order::latest('id')->first();
        $this->pay($second);
        $so2 = $second->sellerOrders()->first();
        $net2 = (int) $so2->seller_net;
        $this->assertLessThan($debit, $net2);

        $this->actingAs($admin)->get('/backoffice/liquidaciones')->assertOk()->assertSee('de reembolsos ya liquidados');
        $this->post("/backoffice/liquidaciones/{$so2->id}", ['reference' => 'LQ-2'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'transferir $ 0'));
        $this->assertSame($net2, $so2->fresh()->settlement_deduction);
        $this->assertSame($debit - $net2, $so1->store->fresh()->pendingDeduction());
        $this->assertNull($first->refunds()->first()->deducted_at);

        // La siguiente liquidación termina de descontarlo y el resto se transfiere.
        auth()->logout();
        $p->update(['price' => 200000]);
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData(['email' => 'eva@example.com']));
        $third = Order::latest('id')->first();
        $this->pay($third);
        $so3 = $third->sellerOrders()->first();
        $this->actingAs($admin)->post("/backoffice/liquidaciones/{$so3->id}", ['reference' => 'LQ-3']);
        $this->assertSame($debit - $net2, $so3->fresh()->settlement_deduction);
        $this->assertSame(0, $so1->store->fresh()->pendingDeduction());
        $this->assertNotNull($first->refunds()->first()->deducted_at);
        $this->assertStringContainsString('descuentos por reembolsos', $third->events()->where('type', 'liquidacion')->first()->message);

        $seller = $so1->store->user;
        $this->actingAs($seller)->get('/seller-center/liquidaciones')->assertOk()->assertSee('de reembolsos)');
    }

    public function test_fully_refunded_order_charged_to_marketcity_is_still_settled_to_the_seller(): void
    {
        Mail::fake();
        $p = $this->product(100000);
        $this->approvedAccount($p);
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::latest('id')->first();
        $this->pay($order);
        $so = $order->sellerOrders()->first();
        $so->update(['status' => 'entregado']);
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->post("/backoffice/pedidos/{$order->number}/reembolso", [
            'amount' => $order->total, 'reason' => 'Compensación', 'charged_to' => 'marketcity',
        ]);
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->get('/backoffice/liquidaciones')->assertSee($order->number);
        $this->post("/backoffice/liquidaciones/{$so->id}", ['reference' => 'LQ-9'])->assertSessionHas('success');
        $this->assertNotNull($so->fresh()->settled_at);
    }

    // --- Búsqueda con precios ---

    public function test_search_parses_prices(): void
    {
        $this->assertSame(['term' => 'audífonos', 'min' => null, 'max' => 80000], SearchQuery::parse('audífonos menos de 80 mil'));
        $this->assertSame(['term' => 'celular', 'min' => null, 'max' => 1200000], SearchQuery::parse('celular hasta $1.200.000'));
        $this->assertSame(['term' => 'tenis', 'min' => 100000, 'max' => 200000], SearchQuery::parse('tenis entre 100 y 200 mil'));
        $this->assertSame(['term' => 'reloj', 'min' => 50000, 'max' => null], SearchQuery::parse('reloj más de 50k'));
        $this->assertSame(['term' => 'portátil', 'min' => null, 'max' => 2000000], SearchQuery::parse('portátil por debajo de 2 millones'));
        $this->assertSame(['term' => 'audifonos', 'min' => null, 'max' => 80000], SearchQuery::parse('audifonos baratos menos de 80mil'));
        // Medidas no son precios.
        $this->assertSame(['term' => 'tv hasta 50 pulgadas', 'min' => null, 'max' => null], SearchQuery::parse('tv hasta 50 pulgadas'));
        $this->assertSame(['term' => 'memoria hasta 128 gb', 'min' => null, 'max' => null], SearchQuery::parse('memoria hasta 128 gb'));
    }

    public function test_catalog_search_filters_by_words_and_price(): void
    {
        $p = $this->product(60000);
        $p->update(['name' => 'Audífonos inalámbricos Pulse']);
        $cheap = Product::visible()->where('id', '!=', $p->id)->first();
        $cheap->update(['name' => 'Audífonos de cable Pulse', 'price' => 95000, 'regular_price' => null]);

        $this->get('/shop?q='.urlencode('pulse audífonos menos de 80 mil'))->assertOk()
            ->assertSee('Audífonos inalámbricos Pulse')->assertDontSee('Audífonos de cable Pulse')
            ->assertSee('por menos de $ 80.000', false);
        $this->get('/shop?q='.urlencode('pulse inalámbricos'))->assertSee('Audífonos inalámbricos Pulse')->assertDontSee('Audífonos de cable Pulse');
    }

    // --- Límite por cliente en ofertas ---

    public function test_flash_offer_limit_per_customer(): void
    {
        $p = $this->product(100000);
        $c = Campaign::create(['name' => 'Relámpago', 'slug' => 'relampago', 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'active' => true, 'per_customer_limit' => 2]);
        CampaignProduct::create(['campaign_id' => $c->id, 'product_id' => $p->id, 'campaign_price' => 80000]);
        app(CampaignService::class)->sync();

        $this->get('/producto/'.$p->slug)->assertSee('máximo 2 por cliente');
        $this->post('/carrito', ['product_id' => $p->id, 'quantity' => 5]);
        $this->assertSame(2, session('cart.items')[$p->id]);
        $this->post('/checkout', $this->checkoutData())->assertRedirect();

        // El mismo correo (sin sesión) ya no puede llevar más a ese precio.
        $this->post('/carrito', ['product_id' => $p->id, 'quantity' => 1]);
        $this->post('/checkout', $this->checkoutData(['email' => 'ANA@example.com']))->assertRedirect('/carrito')
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'máximo de 2 unidades por cliente'));
        $this->post('/checkout', $this->checkoutData(['email' => 'luis@example.com']))->assertRedirect();

        // Con sesión, el carrito ya no deja agregar.
        $user = User::factory()->create(['email' => 'cliente@example.com', 'role' => 'customer']);
        $this->actingAs($user)->post('/carrito', ['product_id' => $p->id, 'quantity' => 2]);
        $this->actingAs($user)->post('/checkout', $this->checkoutData(['email' => 'cliente@example.com']))->assertRedirect();
        $this->actingAs($user)->post('/carrito', ['product_id' => $p->id])->assertSessionHas('error', 'Ya llevaste el máximo de unidades por cliente de esta oferta.');
    }
}
