<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\User;
use App\Services\Orders\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pruebas de los hallazgos de la auditoría Fase A (docs/archivo/fase-0/marketcity-audit/13-matriz-trazabilidad.md). */
class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function placeOrder(string $email = 'ana@example.com'): Order
    {
        $p = Product::where('stock', '>', 2)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', [
            'first_name' => 'Ana', 'last_name' => 'Pérez', 'document_type' => 'CC', 'document_number' => '1012345678',
            'email' => $email, 'phone' => '3001112233', 'department' => 'Antioquia', 'city' => 'Medellín',
            'address' => 'Calle 10 # 43-12', 'payment_method' => 'sandbox', 'terms' => '1',
        ]);

        return Order::latest('id')->first();
    }

    /** MC-FR-034: el título y el JSON-LD de la ficha son del producto, no de un relacionado. */
    public function test_product_page_title_and_structured_data(): void
    {
        $p = Product::where('slug', 'parlante-jbl-flip-6-resistente-al-agua')->first();
        $html = $this->get('/producto/'.$p->slug)->assertOk()->getContent();

        $this->assertStringContainsString('<title>'.e($p->name).' · MarketCity</title>', $html);
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $ld = json_decode($m[1] ?? '', true);
        $this->assertIsArray($ld, 'El JSON-LD debe ser JSON válido');
        $this->assertSame('https://schema.org', $ld['@context']);
        $this->assertSame($p->name, $ld['name']);
    }

    /** MC-FR-033: sitemap y canonical en listados. */
    public function test_sitemap_and_canonical(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString('/producto/', $xml);
        $this->get('/categoria-producto/audio')->assertSee('<link rel="canonical" href="'.url('/categoria-producto/audio').'">', false);
    }

    /** MC-NFR-010: cabeceras de seguridad y error 404 en español. */
    public function test_security_headers_and_spanish_404(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->get('/no-existe-xyz')->assertNotFound()->assertSee('No encontramos esta página');
    }

    /** MC-NFR-012: registrarse con el correo de otra persona no muestra sus pedidos de invitado. */
    public function test_unverified_account_does_not_see_guest_orders_with_same_email(): void
    {
        $order = $this->placeOrder('victima@example.com');
        $this->post('/salir');

        $intruder = User::create(['name' => 'X', 'email' => 'victima@example.com', 'password' => 'secreto123', 'role' => 'customer']);
        $this->actingAs($intruder)->get('/mi-cuenta')->assertOk()->assertDontSee($order->number);

        $intruder->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($intruder)->get('/mi-cuenta')->assertSee($order->number);
    }

    /** MC-PAY-006: una aprobación tardía no revive un pedido cancelado ni descuenta stock dos veces. */
    public function test_late_approval_after_decline_goes_to_review(): void
    {
        $order = $this->placeOrder();
        $payment = $order->payments()->first();
        $service = app(OrderService::class);

        $service->markFailed($order, $payment, ['sandbox' => 'declined']);
        $stock = $order->items()->first()->product->fresh()->stock;

        $service->markPaid($order->fresh(), $payment->fresh(), ['late' => true]);

        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame('review', $payment->fresh()->status);
        $this->assertSame($stock, $order->items()->first()->product->fresh()->stock);
    }

    /** MC-PAY-005: confirmar dos veces el mismo pago no duplica efectos. */
    public function test_mark_paid_is_idempotent(): void
    {
        $order = $this->placeOrder();
        $payment = $order->payments()->first();
        $service = app(OrderService::class);
        $service->markPaid($order, $payment);
        $service->markPaid($order, $payment);

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, $order->events()->where('type', 'pago')->count());
    }

    /** MC-FR-024: no se liquida un subpedido sin pago confirmado. */
    public function test_cannot_settle_unpaid_seller_order(): void
    {
        $order = $this->placeOrder();
        $so = $order->sellerOrders()->first();
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->post("/backoffice/liquidaciones/{$so->id}", ['reference' => 'TRX-1'])->assertStatus(422);
        $this->assertNull(SellerOrder::find($so->id)->settled_at);
    }
}
