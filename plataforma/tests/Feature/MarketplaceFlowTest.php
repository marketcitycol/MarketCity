<?php

namespace Tests\Feature;

use App\Models\MerchantEntity;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\User;
use App\Support\BusinessDays;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFlowTest extends TestCase
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

    public function test_public_pages_load(): void
    {
        $p = Product::first();
        foreach (['/', '/shop', '/shop?q=jbl', '/tiendas', '/tienda/tecnoandes', '/producto/'.$p->slug, '/categoria-producto/audio', '/rastrear-pedido', '/vender'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/cart-2')->assertRedirect('/carrito');
    }

    public function test_installment_filter_uses_installment_value(): void
    {
        // Cuota de hasta 25.000 con 4 cuotas = productos de hasta 100.000.
        $res = $this->get('/shop?cuota=25000')->assertOk();
        $res->assertSee('Sartén antiadherente');
        $res->assertDontSee('Parlante JBL Flip 6');
    }

    public function test_multivendor_checkout_splits_order_charges_commission_and_schedules_settlement(): void
    {
        Carbon::setTestNow('2026-11-17 15:00:00'); // martes
        $a = Product::where('slug', 'parlante-jbl-flip-6-resistente-al-agua')->first(); // TecnoAndes, 499.900
        $b = Product::where('slug', 'freidora-de-aire-oster-4-l')->first(); // Casa Caribe, 349.900, envío 12.000
        $stockB = $b->stock;

        $this->post('/carrito', ['product_id' => $a->id]);
        $this->post('/carrito', ['product_id' => $b->id, 'quantity' => 2]);
        $this->post('/checkout', $this->checkoutData())->assertRedirect();

        $order = Order::first();
        $this->assertSame(499900 + 699800 + 12000, $order->total);
        $this->assertSame(MerchantEntity::current()->id, $order->merchant_entity_id);
        $this->assertCount(2, $order->sellerOrders);
        $this->assertSame($stockB - 2, $b->fresh()->stock);

        $soA = $order->sellerOrders->firstWhere('store_id', $a->store_id);
        $this->assertSame((int) round(499900 * 0.15), $soA->commission_amount);
        $this->assertSame(499900 - $soA->commission_amount, $soA->seller_net);

        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'approve'])->assertRedirect();
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        // 10 días hábiles desde el martes 17/11/2026 → martes 1/12/2026.
        $this->assertSame('2026-12-01', $order->sellerOrders()->first()->settlement_due_on->toDateString());
    }

    public function test_declined_payment_cancels_and_restores_stock(): void
    {
        $p = Product::where('stock', '>', 2)->first();
        $stock = $p->stock;
        $this->post('/carrito', ['product_id' => $p->id, 'quantity' => 2]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::first();
        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'decline']);

        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame('cancelado', $order->sellerOrders()->first()->status);
        $this->assertSame($stock, $p->fresh()->stock);
    }

    public function test_order_page_requires_token(): void
    {
        $p = Product::first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::first();
        $this->get("/pedido/{$order->number}")->assertNotFound();
        $this->get("/pedido/{$order->number}?t={$order->access_token}")->assertOk();
    }

    public function test_seller_sees_only_own_orders_and_must_give_tracking_to_ship(): void
    {
        $p = Product::first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData(['payment_method' => 'contraentrega']));
        $so = SellerOrder::first();

        $other = User::where('email', 'vendedor2@marketcity.test')->first();
        $this->actingAs($other)->get("/seller-center/pedidos/{$so->id}")->assertNotFound();

        $owner = $so->store->user;
        $this->actingAs($owner)->post("/seller-center/pedidos/{$so->id}/estado", ['to' => 'validado']);
        $this->assertSame('validado', $so->fresh()->status);
        $this->actingAs($owner)->post("/seller-center/pedidos/{$so->id}/estado", ['to' => 'despachado'])->assertSessionHas('error');
        $this->actingAs($owner)->post("/seller-center/pedidos/{$so->id}/estado", ['to' => 'despachado', 'carrier' => 'coordinadora', 'tracking_number' => '123']);
        $this->assertSame('despachado', $so->fresh()->status);
    }

    public function test_roles_protect_panels(): void
    {
        $customer = User::where('role', 'customer')->first();
        $this->actingAs($customer)->get('/backoffice')->assertForbidden();
        $this->actingAs($customer)->get('/seller-center')->assertForbidden();
        $this->actingAs(User::where('role', 'admin')->first())->get('/backoffice')->assertOk();
    }

    public function test_switching_merchant_entity_keeps_old_orders(): void
    {
        $p = Product::first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData());
        $old = MerchantEntity::current();

        $company = MerchantEntity::where('person_type', 'juridica')->first();
        $this->actingAs(User::where('role', 'admin')->first())->post("/backoffice/titular/{$company->id}/activar");

        $this->assertSame($company->id, MerchantEntity::current()->id);
        $this->assertSame($old->id, Order::first()->merchant_entity_id);
    }

    public function test_colombian_holidays(): void
    {
        $h = BusinessDays::holidays(2026);
        foreach (['2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03', '2026-05-18', '2026-06-08', '2026-06-15', '2026-11-16', '2026-12-08'] as $d) {
            $this->assertArrayHasKey($d, $h, $d);
        }
        $this->assertCount(18, $h);
    }

    public function test_woocommerce_csv_import_is_repeatable(): void
    {
        $store = \App\Models\Store::first();
        $importer = app(\App\Services\WooCommerceCsvImporter::class);
        $stats = $importer->import(base_path('tests/fixtures/woocommerce-export.csv'), $store);
        $this->assertSame(2, $stats['created']);
        $this->assertSame(3, $stats['skipped']);

        $simple = Product::where('legacy_id', 501)->first();
        $this->assertSame(45000, $simple->price);
        $this->assertSame(60000, $simple->regular_price);
        $this->assertSame(7, $simple->stock);
        $this->assertSame('Audio', $simple->category->name);

        $variable = Product::where('legacy_id', 502)->first();
        $this->assertSame(25000, $variable->price);
        $this->assertSame(7, $variable->stock);

        $again = $importer->import(base_path('tests/fixtures/woocommerce-export.csv'), $store);
        $this->assertSame(0, $again['created']);
        $this->assertSame(2, $again['updated']);
    }

    public function test_negotiated_commission_and_payment_methods_per_store(): void
    {
        $sport = \App\Models\Store::where('slug', 'ruta-sport')->first(); // 12 %, solo pago en línea
        $p = $sport->products()->first();
        $this->post('/carrito', ['product_id' => $p->id]);

        $this->get('/checkout')->assertSee('Pago de prueba')->assertDontSee('Pago contraentrega');
        $this->post('/checkout', $this->checkoutData(['payment_method' => 'contraentrega']))->assertSessionHasErrors('payment_method');

        $this->post('/checkout', $this->checkoutData());
        $so = SellerOrder::first();
        $this->assertSame((int) round($p->price * 0.12), $so->commission_amount);
        $this->assertSame('negociada con el comercio', $so->commission_rule['source']);
    }

    public function test_admin_sets_store_terms(): void
    {
        $store = \App\Models\Store::where('slug', 'tecnoandes')->first();
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->get("/backoffice/vendedores/{$store->slug}")->assertOk();
        $this->actingAs($admin)->put("/backoffice/vendedores/{$store->slug}", [
            'commission_percent' => '10', 'commission_fixed' => '', 'payments_mode' => 'custom', 'payment_methods' => ['contraentrega'],
        ])->assertRedirect('/backoffice/vendedores');
        $store->refresh();
        $this->assertEquals(10, $store->commission_percent);
        $this->assertSame(['contraentrega'], $store->allowedPaymentMethods());
    }
}
