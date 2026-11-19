<?php

namespace Tests\Feature;

use App\Mail\OrderPlacedMail;
use App\Mail\OrderShippedMail;
use App\Mail\PaymentConfirmedMail;
use App\Mail\SellerNewOrderMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\Payments\PaymentRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Ola 1: lo básico para vender en la versión 1 (sin contraentrega, transferencia, correos, precios mínimos). */
class SellingBasicsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function checkout(string $method = 'transferencia', array $extra = [], ?Product $p = null)
    {
        $p ??= Product::visible()->where('stock', '>', 2)->first();
        $this->post('/carrito', ['product_id' => $p->id]);

        return $this->post('/checkout', array_merge([
            'first_name' => 'Ana', 'last_name' => 'Pérez', 'document_type' => 'CC', 'document_number' => '1012345678',
            'email' => 'ana@example.com', 'phone' => '3001112233', 'department' => 'Antioquia', 'city' => 'Medellín',
            'address' => 'Calle 10 # 43-12', 'payment_method' => $method, 'terms' => '1',
        ], $extra));
    }

    public function test_cash_on_delivery_is_never_offered(): void
    {
        Setting::put('payment_methods', 'sandbox,transferencia,contraentrega');
        $this->assertArrayNotHasKey('contraentrega', app(PaymentRegistry::class)->enabled());
        $p = Product::visible()->first();
        $this->post('/carrito', ['product_id' => $p->id]);
        $this->get('/checkout')->assertDontSee('ontraentrega');
        $this->checkout('contraentrega')->assertSessionHasErrors('payment_method');
        $this->get('/')->assertDontSee('ontraentrega');
    }

    public function test_sandbox_payment_is_blocked_outside_demo_mode(): void
    {
        config(['marketcity.demo' => false]);
        $this->assertArrayNotHasKey('sandbox', app(PaymentRegistry::class)->all());
        $this->checkout('sandbox')->assertSessionHasErrors('payment_method');

        $this->checkout('transferencia')->assertRedirect();
        $order = Order::latest('id')->first();
        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'approve'])->assertNotFound();
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_sandbox_cannot_approve_an_order_paid_by_another_method(): void
    {
        $this->checkout('transferencia');
        $order = Order::latest('id')->first();
        $this->get("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}")->assertNotFound();
        $this->post("/pedido/{$order->number}/pago-de-prueba?t={$order->access_token}", ['decision' => 'approve'])->assertNotFound();
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_transfer_is_hidden_until_bank_details_are_complete(): void
    {
        Setting::put('transfer_account_number', '');
        $this->assertArrayNotHasKey('transferencia', app(PaymentRegistry::class)->enabled());
        $this->checkout('transferencia')->assertSessionHasErrors('payment_method');

        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->put('/backoffice/configuracion/transferencia', [
            'transfer_bank' => 'Bancolombia', 'transfer_account_type' => 'Ahorros', 'transfer_account_number' => '123-456789-01',
            'transfer_holder' => 'MarketCity', 'transfer_holder_document' => 'NIT 900.000.000-1', 'transfer_deadline_hours' => 24,
        ])->assertSessionHas('success');
        $this->assertArrayHasKey('transferencia', app(PaymentRegistry::class)->enabled());
    }

    public function test_transfer_order_shows_how_to_pay_and_sends_email_with_masked_document(): void
    {
        Mail::fake();
        $this->checkout('transferencia')->assertRedirect();
        $order = Order::latest('id')->first();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame($order->number, $order->payments()->first()->provider_reference);

        $this->get("/pedido/{$order->number}/gracias?t={$order->access_token}")->assertOk()
            ->assertSee('Cómo pagar')->assertSee('000-000000-00')->assertSee('data-copy', false)->assertSee($order->number)
            ->assertDontSee('Pagas al recibir');

        Mail::assertSent(OrderPlacedMail::class, function (OrderPlacedMail $mail) {
            $html = $mail->render();

            return $mail->hasTo('ana@example.com') && str_contains($html, '000-000000-00')
                && str_contains($html, '5678') && ! str_contains($html, '1012345678');
        });
        Mail::assertNotSent(PaymentConfirmedMail::class);
    }

    public function test_confirming_payment_and_shipping_send_emails_to_buyer_and_seller(): void
    {
        Mail::fake();
        $this->checkout('transferencia');
        $order = Order::latest('id')->first();
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->post("/backoffice/pedidos/{$order->number}/pago-recibido", ['reference' => 'BANCO-1']);

        Mail::assertSent(PaymentConfirmedMail::class, fn ($m) => $m->hasTo('ana@example.com'));
        $so = $order->sellerOrders()->first();
        Mail::assertSent(SellerNewOrderMail::class, fn ($m) => $m->hasTo($so->store->user->email) && ! str_contains($m->render(), '1012345678'));

        $owner = $so->store->user;
        $this->actingAs($owner)->post("/seller-center/pedidos/{$so->id}/estado", ['to' => 'validado']);
        $this->actingAs($owner)->post("/seller-center/pedidos/{$so->id}/estado", ['to' => 'despachado', 'carrier' => 'servientrega', 'tracking_number' => 'G-555']);
        Mail::assertSent(OrderShippedMail::class, fn ($m) => $m->hasTo('ana@example.com') && str_contains($m->render(), 'G-555'));

        // El vendedor ve el documento enmascarado.
        $this->actingAs($owner)->get("/seller-center/pedidos/{$so->id}")->assertOk()->assertDontSee('1012345678')->assertSee('5678');
    }

    public function test_a_broken_mail_server_never_breaks_a_purchase(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);
        $this->checkout('transferencia')->assertRedirect();
        $this->assertSame(1, Order::count());
    }

    public function test_unpaid_transfers_expire_and_return_stock(): void
    {
        $p = Product::visible()->where('stock', '>', 2)->first();
        $stock = $p->stock;
        $this->checkout('transferencia', [], $p);
        $order = Order::latest('id')->first();
        $this->assertSame($stock - 1, $p->fresh()->stock);

        $this->artisan('pedidos:vencer-transferencias')->assertSuccessful();
        $this->assertSame('pending', $order->fresh()->payment_status);

        $this->travel(25)->hours();
        $this->artisan('pedidos:vencer-transferencias')->expectsOutputToContain('1')->assertSuccessful();
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame($stock, $p->fresh()->stock);
    }

    public function test_password_reset_lets_migrated_customers_create_a_password(): void
    {
        Notification::fake();
        $user = User::create(['name' => 'Cliente Migrado', 'email' => 'migrado@example.com', 'password' => bin2hex(random_bytes(16)), 'role' => 'customer']);

        $this->get('/ingresar')->assertSee('olvide-mi-contrasena', false);
        $this->post('/olvide-mi-contrasena', ['email' => 'migrado@example.com'])->assertSessionHas('success');
        $this->post('/olvide-mi-contrasena', ['email' => 'no-existe@example.com'])->assertSessionHas('success'); // misma respuesta

        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->get("/restablecer-contrasena/{$token}?email=migrado@example.com")->assertOk();
        $this->post('/restablecer-contrasena', ['token' => $token, 'email' => 'migrado@example.com', 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1'])
            ->assertRedirect('/ingresar');
        $user->refresh();
        $this->assertTrue(Hash::check('nueva-clave-1', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->post('/restablecer-contrasena', ['token' => $token, 'email' => 'migrado@example.com', 'password' => 'otra-clave-22', 'password_confirmation' => 'otra-clave-22'])
            ->assertSessionHas('error');
    }

    public function test_email_verification_link(): void
    {
        $user = User::where('email', 'cliente@marketcity.test')->first();
        $user->forceFill(['email_verified_at' => null])->save();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);

        $bad = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('otro@example.com')]);
        $this->get($bad)->assertForbidden();
        $this->get("/verificar-correo/{$user->id}/".sha1($user->email))->assertForbidden(); // sin firma
    }

    public function test_products_below_minimum_price_cannot_be_sold(): void
    {
        $p = Product::visible()->first();
        $p->update(['price' => 1]);
        $this->assertFalse(Product::visible()->whereKey($p->id)->exists());
        $this->post('/carrito', ['product_id' => $p->id])->assertNotFound();
        $this->get('/producto/'.$p->slug)->assertNotFound();
    }

    public function test_mask_document_helper(): void
    {
        $this->assertSame('••••••5678', mask_document('1012345678'));
        $this->assertSame('••5678', mask_document('5678'));
        $this->assertSame('', mask_document(null));
    }
}
