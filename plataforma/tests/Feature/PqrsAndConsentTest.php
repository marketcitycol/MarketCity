<?php

namespace Tests\Feature;

use App\Mail\PqrAnsweredMail;
use App\Mail\PqrNoticeMail;
use App\Mail\PqrReceivedMail;
use App\Models\Order;
use App\Models\Pqr;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreBankAccount;
use App\Models\User;
use App\Services\Content\Plugins;
use App\Services\Orders\OrderService;
use App\Support\ColombiaCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Ola 5: PQRS con radicado, retracto que frena la liquidación y medición con consentimiento. */
class PqrsAndConsentTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function paidOrder(Store $store): Order
    {
        $p = Product::visible()->where('store_id', $store->id)->where('stock', '>', 1)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', [
            'first_name' => 'Ana', 'last_name' => 'Pérez', 'document_type' => 'CC', 'document_number' => '1012345678',
            'email' => 'ana@example.com', 'phone' => '3001112233', 'department' => 'Antioquia', 'city' => 'Medellín',
            'address' => 'Calle 10 # 43-12', 'payment_method' => 'sandbox', 'terms' => '1',
        ]);
        $order = Order::latest('id')->first();
        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'approve']);

        return $order->fresh();
    }

    private function form(array $extra = []): array
    {
        return array_merge([
            'type' => 'peticion', 'name' => 'Ana Pérez', 'email' => 'ana@example.com',
            'document_number' => '1012345678', 'message' => 'Quiero saber cuándo llega mi pedido.', 'data_consent' => '1',
        ], $extra);
    }

    public function test_colombian_holidays_and_business_days(): void
    {
        $h2026 = ColombiaCalendar::holidays(2026);
        foreach (['2026-01-01', '2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03', '2026-05-18', '2026-06-08', '2026-06-15', '2026-08-17', '2026-11-02', '2026-11-16', '2026-12-08'] as $d) {
            $this->assertArrayHasKey($d, $h2026, $d);
        }
        $this->assertArrayNotHasKey('2026-11-11', $h2026);
        $this->assertArrayHasKey('2027-03-26', ColombiaCalendar::holidays(2027)); // Viernes Santo 2027

        // Viernes 13-nov-2026 + 5 hábiles: salta el fin de semana y el festivo del lunes 16 → lunes 23 (17, 18, 19, 20 y 23).
        $end = ColombiaCalendar::addBusinessDays(CarbonImmutable::create(2026, 11, 13, 10, 0, 0, 'America/Bogota'), 5);
        $this->assertSame('2026-11-23', $end->format('Y-m-d'));
    }

    public function test_public_pqr_gets_radicado_and_emails(): void
    {
        Mail::fake();
        $this->get('/pqrs')->assertOk()->assertSee('Radicar solicitud');

        // Sin autorización de datos no se radica.
        $this->post('/pqrs', $this->form(['data_consent' => null]))->assertSessionHasErrors('data_consent');
        // Bots: el campo trampa no puede venir lleno.
        $this->post('/pqrs', $this->form(['website' => 'spam']))->assertSessionHasErrors('website');

        $this->post('/pqrs', $this->form())->assertRedirect('/pqrs/estado');
        $pqr = Pqr::first();
        $this->assertMatchesRegularExpression('/^PQR-\d{4}-000001$/', $pqr->radicado);
        $this->assertSame('recibida', $pqr->status);
        $this->assertTrue($pqr->due_at->isFuture());
        $this->assertStringNotContainsString('1012345678', (string) DB::table('pqrs')->value('document_number'));
        Mail::assertSent(PqrReceivedMail::class, fn ($m) => $m->hasTo('ana@example.com'));
        Mail::assertSent(PqrNoticeMail::class, fn ($m) => ! $m->forSeller);

        $this->post('/pqrs', $this->form());
        $this->assertSame(str_replace('000001', '000002', $pqr->radicado), Pqr::latest('id')->value('radicado'));

        // Consulta: con correo distinto no muestra nada.
        $this->post('/pqrs/consultar', ['radicado' => $pqr->radicado, 'email' => 'otro@example.com'])->assertSessionHas('error');
        $this->post('/pqrs/consultar', ['radicado' => strtolower($pqr->radicado), 'email' => 'ana@example.com'])->assertOk()->assertSee('Recibida');
    }

    public function test_retract_requires_matching_order_and_holds_settlement_until_answered(): void
    {
        Mail::fake();
        $store = Store::where('slug', 'tecnoandes')->first();
        $admin = User::where('role', 'admin')->first();
        $order = $this->paidOrder($store);
        $so = $order->sellerOrders()->first();

        StoreBankAccount::create(['store_id' => $store->id, 'bank' => 'Bancolombia', 'account_type' => 'Ahorros', 'account_number' => '1234567890',
            'holder_name' => 'Tecno Andes SAS', 'holder_document_type' => 'NIT', 'holder_document_number' => '900123456', 'status' => 'approved']);

        // Retracto sin pedido o con otro correo: no se acepta.
        $this->post('/pqrs', $this->form(['type' => 'retracto']))->assertSessionHasErrors('order_number');
        $this->post('/pqrs', $this->form(['type' => 'retracto', 'order_number' => $order->number, 'email' => 'otra@example.com']))->assertSessionHas('error');
        $this->assertSame(0, Pqr::count());

        $this->post('/pqrs', $this->form(['type' => 'retracto', 'order_number' => strtolower($order->number), 'message' => 'Me arrepentí de la compra.']))->assertRedirect('/pqrs/estado');
        $pqr = Pqr::first();
        $this->assertSame($order->id, $pqr->order_id);
        $this->assertTrue($pqr->within_term); // aún no entregado
        Mail::assertSent(PqrNoticeMail::class, fn ($m) => $m->forSeller && $m->hasTo($store->notificationEmail()));

        // Con el retracto abierto no se liquida a la tienda.
        $this->actingAs($admin)->get('/backoffice/liquidaciones')->assertSee('RETENIDA');
        $this->actingAs($admin)->post("/backoffice/liquidaciones/{$so->id}", ['reference' => 'TRF-9'])->assertSessionHas('error');
        $this->assertNull($so->fresh()->settled_at);

        // Responder exige texto; al responder se avisa al cliente y se libera.
        $this->actingAs($admin)->get("/backoffice/pqrs/{$pqr->radicado}")->assertOk()->assertSee('Me arrepentí')->assertSee('••••••5678');
        $this->actingAs($admin)->put("/backoffice/pqrs/{$pqr->radicado}", ['status' => 'respondida', 'response' => ''])->assertSessionHasErrors('response');
        $this->actingAs($admin)->put("/backoffice/pqrs/{$pqr->radicado}", ['status' => 'respondida', 'response' => 'Aprobamos tu retracto. Te enviamos la guía de devolución.', 'notify' => '1'])->assertSessionHas('success');
        Mail::assertSent(PqrAnsweredMail::class, fn ($m) => $m->hasTo('ana@example.com'));
        $this->assertNotNull($pqr->fresh()->responded_at);

        $this->actingAs($admin)->post("/backoffice/liquidaciones/{$so->id}", ['reference' => 'TRF-9'])->assertSessionHas('success');
    }

    public function test_retract_after_five_business_days_is_flagged(): void
    {
        Mail::fake();
        $store = Store::where('slug', 'tecnoandes')->first();
        $order = $this->paidOrder($store);
        $so = $order->sellerOrders()->first();
        $svc = app(OrderService::class);
        $svc->advance($so, 'validado', null);
        $svc->advance($so->fresh(), 'despachado', null, 'servientrega', 'G123');
        $svc->advance($so->fresh(), 'entregado', null);
        $this->assertNotNull($so->fresh()->delivered_at);

        $this->travel(15)->days();
        $this->post('/pqrs', $this->form(['type' => 'retracto', 'order_number' => $order->number]));
        $this->assertFalse(Pqr::first()->within_term);
    }

    public function test_admin_pqrs_list_requires_admin(): void
    {
        $this->get('/backoffice/pqrs')->assertRedirect('/ingresar');
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->get('/backoffice/pqrs')->assertOk()->assertSee('PQRS');
    }

    public function test_analytics_loads_nothing_until_enabled_with_valid_ids(): void
    {
        $p = Product::visible()->first();

        // Apagado (de fábrica): ni aviso ni etiquetas.
        $this->get("/producto/{$p->slug}")->assertOk()->assertDontSee('mc-consent', false)->assertDontSee('mcAnalytics', false);

        // Encendido con un id inválido: tampoco.
        Plugins::save('analytics', true, ['gtm_id' => '"><script>alert(1)</script>', 'ga4_id' => '', 'meta_pixel_id' => 'abc']);
        $this->get("/producto/{$p->slug}")->assertDontSee('mcAnalytics', false)->assertDontSee('alert(1)', false);

        Plugins::save('analytics', true, ['gtm_id' => '', 'ga4_id' => 'g-abc1234567', 'meta_pixel_id' => '123456789012', 'banner_text' => 'Usamos cookies', 'policy_url' => '/politica-de-privacidad']);
        $html = $this->get("/producto/{$p->slug}")->assertOk()
            ->assertSee('mc-consent', false)
            ->assertSee("gtag('consent', 'default'", false)
            ->assertSee('"view_item"', false)
            ->getContent();
        $this->assertStringContainsString('G-ABC1234567', $html);
        // Las etiquetas de terceros no van en el HTML: las carga app.js solo tras «Aceptar».
        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $html);
        $this->assertStringNotContainsString('connect.facebook.net', $html);
    }
}
