<?php

namespace Tests\Feature;

use App\Mail\BankAccountChangeRequestedMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ola 3: cuenta bancaria del vendedor con contraseña, revisión y retención de liquidaciones. */
class SellerPayoutSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function bankData(array $extra = []): array
    {
        return array_merge([
            'bank' => 'Bancolombia', 'account_type' => 'Ahorros', 'account_number' => '123 456 7890',
            'holder_name' => 'Tecno Andes SAS', 'holder_document_type' => 'NIT', 'holder_document_number' => '900123456',
            'current_password' => 'marketcity',
        ], $extra);
    }

    private function paidSellerOrder(Store $store)
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

        return $order->sellerOrders()->first();
    }

    public function test_bank_change_needs_password_and_review_and_holds_settlements(): void
    {
        Mail::fake();
        $store = Store::where('slug', 'tecnoandes')->first();
        $seller = $store->user;
        $admin = User::where('role', 'admin')->first();
        $so = $this->paidSellerOrder($store);

        // Sin cuenta aprobada no se liquida.
        $this->actingAs($admin)->post("/backoffice/liquidaciones/{$so->id}", ['reference' => 'TRF-1'])->assertSessionHas('error');
        $this->assertNull($so->fresh()->settled_at);

        // Contraseña equivocada: no se guarda nada.
        $this->actingAs($seller)->post('/seller-center/cuenta-bancaria', $this->bankData(['current_password' => 'otra']))->assertSessionHas('error');
        $this->assertSame(0, $store->bankAccounts()->count());

        $this->actingAs($seller)->post('/seller-center/cuenta-bancaria', $this->bankData())->assertSessionHas('success');
        $pending = $store->pendingBankAccount();
        $this->assertSame('1234567890', $pending->account_number);
        // Cifrada en la base: el número no queda en texto plano.
        $this->assertStringNotContainsString('1234567890', (string) DB::table('store_bank_accounts')->value('account_number'));
        Mail::assertSent(BankAccountChangeRequestedMail::class, fn ($m) => $m->hasTo($seller->email) && $m->forSeller);
        Mail::assertSent(BankAccountChangeRequestedMail::class, fn ($m) => $m->hasTo($admin->email) && ! $m->forSeller);

        // En revisión: sigue retenida.
        $this->actingAs($admin)->post("/backoffice/liquidaciones/{$so->id}", ['reference' => 'TRF-1'])->assertSessionHas('error');

        $this->actingAs($admin)->get("/backoffice/vendedores/{$store->slug}")->assertOk()->assertSee('Cambio de cuenta en revisión')->assertSee('1234567890');
        $this->actingAs($admin)->post("/backoffice/vendedores/{$store->slug}/cuenta/{$pending->id}/aprobar")->assertSessionHas('success');
        $this->assertSame('approved', $pending->fresh()->status);

        $this->actingAs($admin)->post("/backoffice/liquidaciones/{$so->id}", ['reference' => 'TRF-1'])->assertSessionHas('success');
        $this->assertNotNull($so->fresh()->settled_at);

        // Un cambio nuevo vuelve a retener y la cuenta aprobada se mantiene hasta decidir.
        $this->actingAs($seller)->post('/seller-center/cuenta-bancaria', $this->bankData(['account_number' => '999 888 7777', 'holder_document_number' => '111']));
        $this->assertSame('1234567890', $store->approvedBankAccount()->account_number);
        $this->assertNotNull($store->fresh()->settlementHoldReason());
        $this->actingAs($admin)->get("/backoffice/vendedores/{$store->slug}")->assertSee('no coincide');
        $new = $store->pendingBankAccount();
        $this->actingAs($admin)->post("/backoffice/vendedores/{$store->slug}/cuenta/{$new->id}/rechazar", ['note' => 'No confirmada por teléfono']);
        $this->assertSame('rejected', $new->fresh()->status);
        $this->assertNull($store->fresh()->settlementHoldReason());

        // El vendedor ve su cuenta enmascarada.
        $this->actingAs($seller)->get('/seller-center/datos-legales')->assertOk()->assertSee('7890')->assertDontSee('1234567890');
    }

    public function test_other_sellers_cannot_review_or_touch_accounts(): void
    {
        $store = Store::where('slug', 'tecnoandes')->first();
        $this->actingAs($store->user)->post('/seller-center/cuenta-bancaria', $this->bankData());
        $pending = $store->pendingBankAccount();
        $other = User::where('email', 'vendedor2@marketcity.test')->first();
        $this->actingAs($other)->post("/backoffice/vendedores/{$store->slug}/cuenta/{$pending->id}/aprobar")->assertForbidden();
        $this->assertSame('pending', $pending->fresh()->status);
    }

    public function test_legal_data_rut_and_verification(): void
    {
        Storage::fake('local');
        $store = Store::where('slug', 'tecnoandes')->first();
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($store->user)->put('/seller-center/datos-legales', [
            'legal_name' => 'Tecno Andes SAS', 'document_type' => 'NIT', 'document_number' => '900123456',
            'rut' => UploadedFile::fake()->create('rut.pdf', 200, 'application/pdf'),
        ])->assertSessionHas('success');
        $store->refresh();
        Storage::disk('local')->assertExists($store->rut_path);
        $this->assertFalse(str_starts_with($store->rut_path, 'public'));

        $this->actingAs($admin)->get("/backoffice/vendedores/{$store->slug}/rut")->assertOk();
        $this->actingAs($store->user)->get("/backoffice/vendedores/{$store->slug}/rut")->assertForbidden();

        $this->actingAs($admin)->post("/backoffice/vendedores/{$store->slug}/verificada", ['verified' => 1]);
        $this->assertTrue($store->fresh()->isVerified());
        $this->get('/tienda/'.$store->slug)->assertSee('Tienda verificada');

        // Cambiar el titular quita la verificación.
        $this->actingAs($store->user)->put('/seller-center/datos-legales', ['legal_name' => 'Otra Empresa SAS', 'document_type' => 'NIT', 'document_number' => '800000000']);
        $this->assertFalse($store->fresh()->isVerified());
    }
}
