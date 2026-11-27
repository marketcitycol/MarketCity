<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\WebhookEvent;
use App\Services\Payments\PaymentRegistry;
use App\Services\Payments\WompiGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Wompi: checkout web con firma de integridad, evento firmado y consulta de la transacción. */
class WompiPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.wompi' => [
            'public_key' => 'pub_test_abc', 'private_key' => 'prv_test_abc',
            'integrity_secret' => 'test_integrity_x', 'events_secret' => 'test_events_x',
        ]]);
        Setting::put('payment_methods', 'sandbox,transferencia,wompi');
    }

    private function placeOrder(): Order
    {
        $p = Product::visible()->where('stock', '>', 2)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $res = $this->post('/checkout', [
            'first_name' => 'Ana', 'last_name' => 'Pérez', 'document_type' => 'CC', 'document_number' => '1012345678',
            'email' => 'ana@example.com', 'phone' => '3001112233', 'department' => 'Antioquia', 'city' => 'Medellín',
            'address' => 'Calle 10 # 43-12', 'payment_method' => 'wompi', 'terms' => '1',
        ]);
        $order = Order::latest('id')->first();
        $url = $res->headers->get('Location');
        $this->assertStringStartsWith('https://checkout.wompi.co/p/?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $ref = $order->number.'-P'.$order->payments()->first()->id;
        $this->assertSame($ref, $q['reference']);
        $this->assertSame((string) ($order->total * 100), $q['amount-in-cents']);
        $this->assertSame(hash('sha256', $ref.($order->total * 100).'COPtest_integrity_x'), $q['signature:integrity']);
        $this->assertArrayNotHasKey('customer-data:email', $q, 'sin datos personales en la dirección');

        return $order;
    }

    private function fakeTx(Order $order, string $status = 'APPROVED', ?int $cents = null): void
    {
        Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::response(['data' => [
            'id' => '1234-1610641025-49201', 'status' => $status, 'currency' => 'COP',
            'amount_in_cents' => $cents ?? $order->total * 100,
            'reference' => $order->number.'-P'.$order->payments()->first()->id,
        ]])]);
    }

    private function event(string $txId, string $status, int $cents, string $secret = 'test_events_x')
    {
        $ts = time();
        $checksum = hash('sha256', $txId.$status.$cents.$ts.$secret);

        return $this->postJson('/pagos/aviso/wompi', [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => $txId, 'status' => $status, 'amount_in_cents' => $cents]],
            'signature' => ['properties' => ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'], 'checksum' => $checksum],
            'timestamp' => $ts,
        ]);
    }

    public function test_not_offered_without_all_keys(): void
    {
        config(['services.wompi.events_secret' => null]);
        $this->assertArrayNotHasKey('wompi', app(PaymentRegistry::class)->enabled());
    }

    public function test_signed_event_approves_after_checking_transaction_once(): void
    {
        $order = $this->placeOrder();
        $this->fakeTx($order);
        $this->event('1234-1610641025-49201', 'APPROVED', $order->total * 100)->assertOk();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->event('1234-1610641025-49201', 'APPROVED', $order->total * 100)->assertOk();
        $this->assertSame(1, WebhookEvent::where('provider', 'wompi')->count());
    }

    public function test_bad_checksum_is_rejected(): void
    {
        $order = $this->placeOrder();
        $this->fakeTx($order);
        $this->event('1234-1610641025-49201', 'APPROVED', $order->total * 100, 'otro')->assertStatus(401);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_amount_mismatch_goes_to_review(): void
    {
        $order = $this->placeOrder();
        $this->fakeTx($order, 'APPROVED', $order->total * 100 - 50);
        $this->event('1234-1610641025-49201', 'APPROVED', $order->total * 100 - 50)->assertOk();
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('review', $order->payments()->first()->status);
    }

    public function test_browser_return_with_declined_transaction_cancels(): void
    {
        $other = $this->placeOrder();
        $this->fakeTx($other, 'DECLINED');
        $this->get("/pagos/regreso/wompi/{$other->number}?t={$other->access_token}&id=1234-1610641025-49201")
            ->assertRedirect("/pedido/{$other->number}/gracias?t={$other->access_token}");
        $this->assertSame('failed', $other->fresh()->payment_status);
    }

    public function test_reference_parsing(): void
    {
        $this->assertSame('MC-000123', WompiGateway::orderNumberFromReference('MC-000123-P45'));
        $this->assertSame('WC-9001', WompiGateway::orderNumberFromReference('WC-9001-P1'));
        $this->assertNull(WompiGateway::orderNumberFromReference('MC-000123'));
    }
}
