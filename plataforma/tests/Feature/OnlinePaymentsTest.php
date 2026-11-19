<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\WebhookEvent;
use App\Services\Payments\PaymentRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Ola 2: pasarela en línea (Mercado Pago) con aviso firmado, consulta servidor a servidor e idempotencia. */
class OnlinePaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const SECRET = 'secreto-de-prueba';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mercadopago.access_token' => 'TEST-token', 'services.mercadopago.webhook_secret' => self::SECRET]);
        Setting::put('payment_methods', 'sandbox,transferencia,mercadopago');
    }

    private function placeMpOrder(): Order
    {
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'PREF-1', 'init_point' => 'https://www.mercadopago.com.co/checkout/v1/redirect?pref_id=PREF-1'])]);
        $p = Product::visible()->where('stock', '>', 2)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', [
            'first_name' => 'Ana', 'last_name' => 'Pérez', 'document_type' => 'CC', 'document_number' => '1012345678',
            'email' => 'ana@example.com', 'phone' => '3001112233', 'department' => 'Antioquia', 'city' => 'Medellín',
            'address' => 'Calle 10 # 43-12', 'payment_method' => 'mercadopago', 'terms' => '1',
        ])->assertRedirect('https://www.mercadopago.com.co/checkout/v1/redirect?pref_id=PREF-1');

        return Order::latest('id')->first();
    }

    private function fakePayment(Order $order, string $status = 'approved', ?int $amount = null, string $currency = 'COP'): void
    {
        Http::fake(['api.mercadopago.com/v1/payments/*' => Http::response([
            'id' => 555, 'status' => $status, 'external_reference' => $order->number,
            'transaction_amount' => $amount ?? $order->total, 'currency_id' => $currency,
        ])]);
    }

    private function signedWebhook(string $paymentId, string $requestId = 'req-1', ?string $secret = null)
    {
        $ts = (string) time();
        $v1 = hash_hmac('sha256', "id:{$paymentId};request-id:{$requestId};ts:{$ts};", $secret ?? self::SECRET);

        return $this->withHeaders(['x-signature' => "ts={$ts},v1={$v1}", 'x-request-id' => $requestId])
            ->postJson("/pagos/aviso/mercadopago?data.id={$paymentId}&type=payment", ['action' => 'payment.updated', 'data' => ['id' => $paymentId]]);
    }

    public function test_not_offered_without_keys(): void
    {
        config(['services.mercadopago.access_token' => null]);
        $this->assertArrayNotHasKey('mercadopago', app(PaymentRegistry::class)->enabled());
    }

    public function test_signed_webhook_approves_after_checking_with_provider_once(): void
    {
        $order = $this->placeMpOrder();
        $this->assertSame('PREF-1', $order->payments()->first()->provider_reference);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'checkout/preferences') && $r['external_reference'] === $order->number && $r['items'][0]['unit_price'] === $order->total);

        $this->fakePayment($order);
        $this->signedWebhook('555')->assertOk();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, WebhookEvent::count());

        // El mismo aviso otra vez no se procesa dos veces.
        $this->signedWebhook('555')->assertOk()->assertJson(['repetido' => true]);
        $this->assertSame(1, WebhookEvent::count());
    }

    public function test_bad_signature_is_rejected_and_changes_nothing(): void
    {
        $order = $this->placeMpOrder();
        $this->fakePayment($order);
        $this->signedWebhook('555', 'req-1', 'otra-clave')->assertStatus(401);
        $this->postJson('/pagos/aviso/mercadopago?data.id=555', [])->assertStatus(401);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_amount_or_currency_mismatch_goes_to_review(): void
    {
        $order = $this->placeMpOrder();
        $this->fakePayment($order, 'approved', 1000);
        $this->signedWebhook('555')->assertOk();
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('review', $order->payments()->first()->status);
    }

    public function test_browser_return_never_trusts_the_url(): void
    {
        $order = $this->placeMpOrder();
        // La URL dice "approved", pero el proveedor dice "rejected": manda el proveedor.
        $this->fakePayment($order, 'rejected');
        $this->get("/pagos/regreso/mercadopago/{$order->number}?t={$order->access_token}&payment_id=555&status=approved&collection_status=approved")
            ->assertRedirect();
        $this->assertSame('failed', $order->fresh()->payment_status);

        $this->get("/pagos/regreso/mercadopago/{$order->number}?t=malo&payment_id=555")->assertNotFound();
    }

    public function test_provider_down_cancels_order_and_returns_stock(): void
    {
        Http::fake(['api.mercadopago.com/*' => Http::response([], 500)]);
        $p = Product::visible()->where('stock', '>', 2)->first();
        $stock = $p->stock;
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', [
            'first_name' => 'Ana', 'last_name' => 'Pérez', 'document_type' => 'CC', 'document_number' => '1012345678',
            'email' => 'ana@example.com', 'phone' => '3001112233', 'department' => 'Antioquia', 'city' => 'Medellín',
            'address' => 'Calle 10 # 43-12', 'payment_method' => 'mercadopago', 'terms' => '1',
        ])->assertRedirect('/carrito');
        $this->assertSame('failed', Order::latest('id')->first()->payment_status);
        $this->assertSame($stock, $p->fresh()->stock);
        $html = $this->get('/carrito')->getContent(); file_put_contents('/tmp/cart.html', $html); $this->assertStringContainsString(e($p->name), $html); // el carrito se conserva
    }

    public function test_reconciliation_finds_payments_whose_notice_never_arrived(): void
    {
        $order = $this->placeMpOrder();
        $this->travel(20)->minutes();
        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => [['id' => 777]]]),
            'api.mercadopago.com/v1/payments/777' => Http::response(['id' => 777, 'status' => 'approved', 'external_reference' => $order->number, 'transaction_amount' => $order->total, 'currency_id' => 'COP']),
        ]);
        $this->artisan('pagos:conciliar')->assertSuccessful();
        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}
