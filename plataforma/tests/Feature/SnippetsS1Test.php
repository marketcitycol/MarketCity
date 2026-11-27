<?php

namespace Tests\Feature;

use App\Mail\CartReminderMail;
use App\Mail\EmailPriceMail;
use App\Models\AbandonedCart;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\EmailPriceCoupon;
use App\Models\MarketingConsent;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Campaigns\CampaignService;
use App\Services\Content\Plugins;
use App\Services\Marketing\AbandonedCarts;
use App\Services\Marketing\MessagingPermissions;
use App\Services\Marketing\MetaConversions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Traslado de los snippets de WordPress, ola S1 (docs/producto/traslado-snippets.md). */
class SnippetsS1Test extends TestCase
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

    private function campaign(Product $p, int $price, array $extra = [], ?int $emailPrice = null): Campaign
    {
        $c = Campaign::create(array_merge([
            'name' => 'Black City', 'slug' => 'black-city', 'badge' => 'BLACK CITY',
            'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'unit_cap' => 10, 'active' => true,
        ], $extra));
        CampaignProduct::create(['campaign_id' => $c->id, 'product_id' => $p->id, 'campaign_price' => $price, 'email_price' => $emailPrice]);
        app(CampaignService::class)->sync();

        return $c;
    }

    // --- #16, #77, #78: campañas ---

    public function test_campaign_price_applies_in_window_and_restores_at_end(): void
    {
        $p = Product::where('stock', '>', 5)->first();
        $normal = $p->price;
        $c = $this->campaign($p, $normal - 10000);

        $this->assertSame($normal - 10000, $p->fresh()->price);
        $this->assertGreaterThanOrEqual($normal, $p->fresh()->regular_price);
        $this->get('/producto/'.$p->slug)->assertOk()->assertSee('BLACK CITY')->assertSee('La oferta termina en');
        $this->get('/campana/black-city')->assertOk()->assertSee($p->name);
        $this->get('/black-city')->assertRedirect('/campana/black-city');

        Carbon::setTestNow(now()->addDays(2));
        app(CampaignService::class)->sync();
        $this->assertSame($normal, $p->fresh()->price);
        $this->get('/campana/black-city')->assertOk()->assertSee('terminó');
        Carbon::setTestNow();
    }

    public function test_unit_cap_limits_cart_and_closes_campaign_then_reopens_on_cancel(): void
    {
        $p = Product::where('stock', '>', 5)->first();
        $p->update(['stock' => 50]);
        $normal = $p->price;
        $this->campaign($p, $normal - 5000, ['unit_cap' => 3]);

        // El carrito no deja llevar más de lo que queda a precio de campaña.
        $this->post('/carrito', ['product_id' => $p->id, 'quantity' => 5]);
        $this->assertSame(3, session('cart.items')[$p->id]);

        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        $order = Order::first();
        $this->assertSame($p->id, $order->items->first()->product_id);
        $this->assertNotNull($order->items->first()->campaign_id);
        // Se llenó el tope: vuelve al precio normal y ya no se ofrece a precio de campaña.
        $this->assertSame($normal, $p->fresh()->price);
        $this->assertNotNull(CampaignProduct::first()->closed_at);

        // Pago rechazado: las unidades vuelven y el producto recupera el precio de campaña.
        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'decline']);
        $this->assertSame($normal - 5000, $p->fresh()->price);
        $this->assertNull(CampaignProduct::first()->closed_at);
    }

    public function test_seller_price_change_during_campaign_becomes_the_normal_price(): void
    {
        $p = Product::where('stock', '>', 5)->first();
        $normal = $p->price;
        $this->campaign($p, $normal - 5000);
        $this->actingAs($p->store->user)->put('/seller-center/productos/'.$p->slug, [
            'name' => $p->name, 'category_id' => $p->category_id, 'price' => $normal + 1000, 'stock' => $p->stock, 'status' => 'active',
        ])->assertRedirect();
        $this->assertSame($normal - 5000, $p->fresh()->price);
        $this->assertSame($normal + 1000, CampaignProduct::first()->base_price);
    }

    public function test_backoffice_creates_campaign_and_adds_product(): void
    {
        $admin = User::where('role', 'admin')->first();
        $p = Product::where('stock', '>', 0)->whereNotNull('sku')->first();
        $this->actingAs($admin)->post('/backoffice/campanas', [
            'name' => 'Hot Sale', 'starts_at' => now('America/Bogota')->subHour()->format('Y-m-d\TH:i'), 'ends_at' => now('America/Bogota')->addDay()->format('Y-m-d\TH:i'),
            'unit_cap' => 10, 'active' => '1',
        ])->assertRedirect();
        $c = Campaign::where('slug', 'hot-sale')->firstOrFail();
        $this->actingAs($admin)->post("/backoffice/campanas/{$c->slug}/productos", ['product' => $p->sku, 'campaign_price' => $p->price - 1000])->assertRedirect();
        $this->assertSame($p->getOriginal('price') - 1000, $p->fresh()->price);
        $this->actingAs($admin)->get('/backoffice/campanas')->assertOk()->assertSee('Hot Sale');
        $this->actingAs($admin)->get("/backoffice/campanas/{$c->slug}")->assertOk()->assertSee($p->name);
        $this->actingAs($admin)->put('/backoffice/campanas/corte-de-despacho', ['hour' => 14])->assertRedirect();
        $this->assertSame('14', (string) Setting::get('dispatch_cutoff_hour'));
    }

    // --- #76: precio con correo ---

    public function test_email_price_coupon_is_single_use_one_unit_and_tied_to_email(): void
    {
        Mail::fake();
        $p = Product::where('stock', '>', 5)->first();
        $normal = $p->price;
        $this->campaign($p, $normal - 10000, ['email_price_enabled' => true], $normal - 20000);

        $this->get('/producto/'.$p->slug)->assertSee('Ver mi precio');
        $this->post("/producto/{$p->slug}/precio-con-correo", ['email' => 'Luz@Example.com', 'consent' => '1'])->assertRedirect('/carrito');
        Mail::assertSent(EmailPriceMail::class);
        $coupon = EmailPriceCoupon::firstOrFail();
        $this->assertSame('luz@example.com', $coupon->email);

        // Dos unidades: solo una lleva el precio con correo.
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->get('/carrito')->assertSee('Precio con correo en 1 unidad');

        // Con otro correo no se puede pagar con ese precio.
        $this->post('/checkout', $this->checkoutData(['email' => 'otra@example.com']))->assertRedirect('/carrito');
        $this->assertSame(0, Order::count());

        $this->post('/checkout', $this->checkoutData(['email' => 'luz@example.com']))->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame(($normal - 10000) * 2 - 10000, $order->subtotal);
        $this->assertNotNull($coupon->fresh()->used_at);

        // Ya usado: no se vuelve a aplicar.
        $this->post('/carrito/cupon', ['code' => $coupon->code])->assertSessionHas('error');
    }

    public function test_email_price_requires_consent(): void
    {
        $p = Product::where('stock', '>', 5)->first();
        $this->campaign($p, $p->price - 10000, ['email_price_enabled' => true], $p->price - 20000);
        $this->post("/producto/{$p->slug}/precio-con-correo", ['email' => 'luz@example.com'])->assertSessionHasErrors('consent');
        $this->assertSame(0, EmailPriceCoupon::count());
    }

    // --- #82: permisos de mensajes ---

    public function test_checkout_records_consent_per_channel_and_unsubscribe_link_works(): void
    {
        $p = Product::where('stock', '>', 0)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData(['messages' => ['email' => '1']]))->assertRedirect();

        $perm = app(MessagingPermissions::class);
        $this->assertTrue($perm->allows('ana@example.com', 'email', 'carrito'));
        $this->assertFalse($perm->allows('573001112233', 'whatsapp', 'carrito'));
        $row = MarketingConsent::where('channel', 'email')->first();
        $this->assertSame(MessagingPermissions::TEXT_VERSION, $row->text_version);
        $this->assertNotNull($row->ip_hash);
        $this->assertTrue(Order::first()->marketing_optin);

        $url = $perm->unsubscribeUrl('ana@example.com', '3001112233');
        $this->get($url)->assertOk()->assertSee('Dejar de recibir');
        $this->post($url, ['canal' => 'todo'])->assertOk()->assertSee('no te escribiremos más');
        $this->assertFalse($perm->allows('ana@example.com', 'email', 'carrito'));
        // Sin firma no sirve.
        $this->get('/mensajes/baja?d=abc')->assertForbidden();
    }

    public function test_legal_hours_and_daily_limit(): void
    {
        $this->assertTrue(MessagingPermissions::inLegalHours(Carbon::parse('2026-11-24 10:00', 'America/Bogota'))); // martes
        $this->assertFalse(MessagingPermissions::inLegalHours(Carbon::parse('2026-11-24 19:30', 'America/Bogota')));
        $this->assertTrue(MessagingPermissions::inLegalHours(Carbon::parse('2026-11-28 14:00', 'America/Bogota'))); // sábado
        $this->assertFalse(MessagingPermissions::inLegalHours(Carbon::parse('2026-11-28 15:30', 'America/Bogota')));
        $this->assertFalse(MessagingPermissions::inLegalHours(Carbon::parse('2026-11-29 10:00', 'America/Bogota'))); // domingo
        $this->assertFalse(MessagingPermissions::inLegalHours(Carbon::parse('2026-12-08 10:00', 'America/Bogota'))); // festivo

        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00', 'America/Bogota'));
        $perm = app(MessagingPermissions::class);
        $this->assertSame('', $perm->whyNot('ana@example.com', null, 'email'));
        $perm->logContact('ana@example.com', null, 'email', 'carrito');
        $this->assertSame('hoy', $perm->whyNot('ana@example.com', null, 'email'));
        Carbon::setTestNow(Carbon::parse('2026-11-25 10:00', 'America/Bogota'));
        $this->assertSame('otro-canal', $perm->whyNot('ana@example.com', '3001112233', 'whatsapp'));
        Carbon::setTestNow();

        $this->assertNull(MessagingPermissions::normalizePhone('0300111'));
        $this->assertSame('573001112233', MessagingPermissions::normalizePhone('+57 300 111 2233'));
    }

    // --- #83: carritos abandonados ---

    public function test_abandoned_cart_is_saved_reminded_and_restored(): void
    {
        Mail::fake();
        Setting::put('abandoned_cart_mode', 'public');
        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00', 'America/Bogota'));
        $p = Product::where('stock', '>', 0)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->postJson('/checkout/permisos', ['email' => 'ana@example.com', 'first_name' => 'Ana', 'messages' => ['email' => true]])->assertOk();

        $row = AbandonedCart::firstOrFail();
        $this->assertSame('email', $row->channel);
        $this->assertSame([$p->id => 1], array_map('intval', $row->items));

        // Antes de la hora no se envía nada.
        app(AbandonedCarts::class)->tick();
        Mail::assertNothingSent();

        Carbon::setTestNow(Carbon::parse('2026-11-24 11:05', 'America/Bogota'));
        $this->assertSame(1, app(AbandonedCarts::class)->tick()['enviados']);
        Mail::assertSent(CartReminderMail::class, fn ($m) => $m->hasTo('ana@example.com'));
        $this->assertSame(1, $row->fresh()->reminders_sent);

        // El enlace rehace el carrito en otro navegador y lleva al pago.
        $this->flushSession();
        $this->get('/carrito/recuperar/'.$row->token)->assertRedirect('/checkout');
        $this->assertSame(1, session('cart.items')[$p->id]);
        Carbon::setTestNow();
    }

    public function test_abandoned_cart_stops_when_unchecked_or_unsubscribed_and_off_mode_saves_nothing(): void
    {
        $p = Product::where('stock', '>', 0)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->postJson('/checkout/permisos', ['email' => 'ana@example.com', 'messages' => ['email' => true]])->assertOk();
        $this->assertSame(0, AbandonedCart::count()); // modo apagado por defecto

        Setting::put('abandoned_cart_mode', 'public');
        $this->postJson('/checkout/permisos', ['email' => 'ana@example.com', 'messages' => ['email' => true]])->assertOk();
        $this->assertSame(1, AbandonedCart::count());
        // Desmarca antes de comprar y no se le ha escrito: se borra.
        $this->postJson('/checkout/permisos', ['email' => 'ana@example.com', 'messages' => ['email' => false]])->assertOk();
        $this->assertSame(0, AbandonedCart::count());

        $this->postJson('/checkout/permisos', ['email' => 'ana@example.com', 'messages' => ['email' => true]])->assertOk();
        app(MessagingPermissions::class)->revoke('ana@example.com', null);
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00', 'America/Bogota'));
        AbandonedCart::query()->update(['next_reminder_at' => now()->subMinute()]);
        app(AbandonedCarts::class)->tick();
        $this->assertSame('stopped', AbandonedCart::first()->status);
        $this->assertSame('baja', AbandonedCart::first()->stop_reason);
        Carbon::setTestNow();

        $this->postJson('/checkout/permisos', ['email' => 'no-es-correo', 'messages' => ['email' => true]])->assertStatus(422);
    }

    public function test_backoffice_messages_panel_and_csv(): void
    {
        $admin = User::where('role', 'admin')->first();
        app(MessagingPermissions::class)->record('=cmd@example.com', null, ['email' => true]);
        app(MessagingPermissions::class)->record('ana@example.com', null, ['email' => true]);
        $this->actingAs($admin)->get('/backoffice/mensajes')->assertOk()->assertSee('ana@example.com');
        $csv = $this->actingAs($admin)->get('/backoffice/mensajes/permisos.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('ana@example.com', $csv);
        $this->actingAs($admin)->put('/backoffice/mensajes', ['mode' => 'test', 'email_hour_cap' => 10, 'email_day_cap' => 50, 'whatsapp_day_cap' => 20])->assertRedirect();
        $this->assertSame('test', AbandonedCarts::mode());
        $this->actingAs($admin)->post('/backoffice/mensajes/baja', ['contact' => 'ana@example.com'])->assertRedirect();
        $this->assertFalse(app(MessagingPermissions::class)->allows('ana@example.com', 'email', 'carrito'));
    }

    // --- #65: rastreo seguro ---

    public function test_tracking_needs_matching_email_or_phone_and_is_rate_limited(): void
    {
        $p = Product::where('stock', '>', 0)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::firstOrFail();
        $digits = ltrim(preg_replace('/\D/', '', $order->number), '0');

        $this->post('/rastrear-pedido', ['number' => $order->number, 'contact' => 'otro@example.com'])->assertSessionHas('error');
        $this->post('/rastrear-pedido', ['number' => $digits, 'contact' => 'ANA@example.com'])->assertRedirect(route('orders.show', [$order, 't' => $order->access_token]));
        $this->post('/rastrear-pedido', ['number' => $order->number, 'contact' => '300 111 2233'])->assertRedirect(route('orders.show', [$order, 't' => $order->access_token]));
        // Compatibilidad con el formulario anterior (campo email).
        $this->post('/rastrear-pedido', ['number' => $order->number, 'email' => 'ana@example.com'])->assertRedirect();

        // 5 fallos con celular bloquean el celular para ese pedido; el correo sigue sirviendo.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/rastrear-pedido', ['number' => $order->number, 'contact' => '3009999999']);
        }
        $this->post('/rastrear-pedido', ['number' => $order->number, 'contact' => '3001112233'])->assertSessionHas('error');
        $this->post('/rastrear-pedido', ['number' => $order->number, 'contact' => 'ana@example.com'])->assertRedirect(route('orders.show', [$order, 't' => $order->access_token]));
    }

    // --- #85: Meta solo con pedido pagado ---

    public function test_purchase_event_only_when_paid_and_server_event_after_late_payment(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        config(['services.meta.capi_token' => 'token-de-prueba']);
        Plugins::save('analytics', true, ['meta_pixel_id' => '1234567890']);
        $this->assertTrue(MetaConversions::configured());
        $p = Product::where('stock', '>', 0)->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->withUnencryptedCookie('mc_consent', 'granted')->post('/checkout', $this->checkoutData())->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertTrue($order->analytics_consent);

        // Pendiente de pago: la página de gracias no envía la compra.
        $this->get("/pedido/{$order->number}/gracias?t={$order->access_token}")->assertOk()->assertDontSee('"purchase"', false);

        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'approve']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/1234567890/events') && $r['data'][0]['event_id'] === $order->number);
        $this->assertNotNull($order->fresh()->meta_purchase_sent_at);
        $this->get("/pedido/{$order->number}/gracias?t={$order->access_token}")->assertOk()->assertSee('purchase', false);
    }
}
