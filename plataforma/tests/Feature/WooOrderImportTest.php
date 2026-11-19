<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WooOrderImportTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function payload(string $status = 'completed'): array
    {
        $p = Product::where('store_id', '!=', Store::orderBy('id')->value('id'))->firstOrFail();
        $p->legacy_id = 777;
        $p->save();

        return [
            'customers' => [['id' => 55, 'email' => 'Cliente@Example.com', 'first_name' => 'Ana', 'last_name' => 'Ruiz', 'billing' => ['phone' => '3001234567']]],
            'orders' => [[
                'id' => 9001, 'number' => '9001', 'status' => $status, 'customer_id' => 55,
                'date_created_gmt' => '2026-10-01T15:00:00', 'date_paid_gmt' => '2026-10-01T15:05:00',
                'billing' => ['first_name' => 'Ana', 'last_name' => 'Ruiz', 'email' => 'cliente@example.com', 'phone' => '3001234567', 'state' => 'CUN', 'city' => 'Funza', 'address_1' => 'Calle 1 # 2-3'],
                'shipping' => [], 'shipping_total' => '10000', 'total' => '110000',
                'payment_method' => 'addi', 'payment_method_title' => 'Addi', 'transaction_id' => 'TX1',
                'meta_data' => [['key' => 'cedula', 'value' => '1010']],
                'line_items' => [
                    ['product_id' => 777, 'name' => 'Producto conocido', 'quantity' => 2, 'price' => 30000, 'subtotal' => '60000', 'total' => '60000', 'sku' => 'A1'],
                    ['product_id' => 123456, 'name' => 'Producto que ya no existe', 'quantity' => 1, 'price' => 40000, 'subtotal' => '40000', 'total' => '40000'],
                ],
            ]],
        ];
    }

    public function test_imports_customers_and_orders_repeatably_without_settlement_debt(): void
    {
        $admin = User::where('role', 'admin')->first();
        $payload = $this->payload();
        $stock = Product::where('legacy_id', 777)->value('stock');

        $this->actingAs($admin)->postJson(route('admin.import.orders'), $payload)
            ->assertOk()->assertJson(['customers_created' => 1, 'orders_created' => 1]);

        $order = Order::where('legacy_id', 9001)->firstOrFail();
        $this->assertSame('WC-9001', $order->number);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(110000, $order->total);
        $this->assertSame('1010', $order->document_number);
        $this->assertSame('2026-10-01', $order->created_at->toDateString());
        $this->assertSame(User::where('legacy_id', 55)->value('id'), $order->user_id);
        $this->assertCount(2, $order->sellerOrders, 'producto conocido va a su tienda; el desconocido a la tienda de respaldo');
        $this->assertTrue($order->sellerOrders->every(fn ($so) => $so->status === 'entregado' && $so->settled_at !== null));
        $this->assertSame(10000, $order->sellerOrders->sum('shipping_total'));
        $this->assertSame($stock, Product::where('legacy_id', 777)->value('stock'), 'no toca inventario');

        // Repetible: se actualiza el estado, no se duplica.
        $this->actingAs($admin)->postJson(route('admin.import.orders'), $this->payload('cancelled'))
            ->assertJson(['customers_updated' => 1, 'orders_updated' => 1, 'orders_created' => 0]);
        $this->assertSame(1, Order::where('legacy_id', 9001)->count());
        $order->refresh();
        $this->assertSame('failed', $order->payment_status);
        $this->assertCount(2, $order->items);

        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee('WC-9001')->assertSee('Pedido histórico');
        $this->actingAs($admin)->get(route('admin.settlements'))->assertOk()->assertDontSee('WC-9001');
    }

    public function test_never_changes_existing_staff_accounts(): void
    {
        $admin = User::where('role', 'admin')->first();
        $hash = $admin->password;
        $this->actingAs($admin)->postJson(route('admin.import.orders'), ['customers' => [['id' => 1, 'email' => $admin->email, 'first_name' => 'X']], 'orders' => []])->assertOk();
        $admin->refresh();
        $this->assertSame('admin', $admin->role);
        $this->assertSame($hash, $admin->password);
        $this->assertNotSame('X', $admin->name);
    }

    public function test_import_requires_admin(): void
    {
        $this->postJson(route('admin.import.orders'), ['customers' => [], 'orders' => []])->assertUnauthorized();
    }
}
