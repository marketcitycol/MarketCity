<?php

namespace Tests\Feature;

use App\Mail\OrderNoticeMail;
use App\Mail\WelcomeMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Correos de pedido que WooCommerce enviaba y v1 no: nuevo pedido, pago fallido, cancelación, entrega, nota y bienvenida. */
class OrderNoticesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function placeOrder(): Order
    {
        $p = Product::visible()->where('stock', '>', 2)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', [
            'first_name' => 'Ana', 'last_name' => 'Pérez', 'document_type' => 'CC', 'document_number' => '1012345678',
            'email' => 'ana@example.com', 'phone' => '3001112233', 'department' => 'Antioquia', 'city' => 'Medellín',
            'address' => 'Calle 10 # 43-12', 'payment_method' => 'sandbox', 'terms' => '1',
        ])->assertRedirect();

        return Order::latest('id')->first();
    }

    public function test_new_order_goes_to_the_team_and_failed_payment_to_customer_and_team(): void
    {
        Mail::fake();
        $admin = User::where('role', 'admin')->first();
        $order = $this->placeOrder();
        Mail::assertSent(OrderNoticeMail::class, fn ($m) => $m->kind === 'admin-new' && $m->hasTo($admin->email));

        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'decline']);
        Mail::assertSent(OrderNoticeMail::class, fn ($m) => $m->kind === 'payment-failed' && $m->hasTo('ana@example.com'));
        Mail::assertSent(OrderNoticeMail::class, fn ($m) => $m->kind === 'admin-failed' && $m->hasTo($admin->email));

        $html = (new OrderNoticeMail($order->fresh(), 'payment-failed', 'Pago rechazado'))->render();
        $this->assertStringContainsString('No se cobró nada', $html);
        $this->assertStringContainsString('Pagar con otro medio', $html);
    }

    public function test_manual_cancel_and_delivery_notify_the_customer(): void
    {
        Mail::fake();
        $order = $this->placeOrder();
        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'approve']);
        $so = $order->sellerOrders()->first();
        $orders = app(OrderService::class);
        $admin = User::where('role', 'admin')->first();

        foreach (['validado', 'despachado', 'entregado'] as $to) {
            $orders->advance($so->fresh(), $to, $admin, 'servientrega', '123456');
        }
        Mail::assertSent(OrderNoticeMail::class, fn ($m) => $m->kind === 'delivered' && $m->hasTo('ana@example.com'));
        $this->assertStringContainsString('5 días hábiles', (new OrderNoticeMail($order, 'delivered', null, $so))->render());

        $other = $this->placeOrder();
        $this->actingAs($admin)->post('/backoffice/subpedidos/'.$other->sellerOrders()->first()->id.'/cancelar', ['reason' => 'Sin unidades'])->assertRedirect();
        Mail::assertSent(OrderNoticeMail::class, fn ($m) => $m->kind === 'cancelled' && $m->detail === 'Sin unidades');
    }

    public function test_backoffice_note_reaches_customer_and_history(): void
    {
        Mail::fake();
        $order = $this->placeOrder();
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->get("/backoffice/pedidos/{$order->number}")->assertSee('Nota para el cliente');
        $this->actingAs($admin)->post("/backoffice/pedidos/{$order->number}/nota", ['note' => 'Sale mañana.'])->assertSessionHas('success');
        Mail::assertSent(OrderNoticeMail::class, fn ($m) => $m->kind === 'note' && $m->detail === 'Sale mañana.' && $m->hasTo('ana@example.com'));
        $this->assertTrue($order->events()->where('message', 'like', '%Sale mañana.%')->exists());
    }

    public function test_register_sends_welcome_without_password(): void
    {
        Mail::fake();
        $this->post('/registro', ['name' => 'Luz Gómez', 'email' => 'luz@example.com', 'password' => 'clave-segura-1', 'password_confirmation' => 'clave-segura-1', 'terms' => '1'])->assertRedirect();
        Mail::assertSent(WelcomeMail::class, fn ($m) => $m->hasTo('luz@example.com'));
        $this->assertStringNotContainsString('clave-segura-1', (new WelcomeMail(User::where('email', 'luz@example.com')->first()))->render());
    }
}
