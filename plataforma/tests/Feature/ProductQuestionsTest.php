<?php

namespace Tests\Feature;

use App\Mail\ProductQuestionAnsweredMail;
use App\Mail\ProductQuestionMail;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Preguntas al vendedor desde la página del producto, con correo a la tienda y al cliente. */
class ProductQuestionsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_guest_asks_seller_answers_and_it_is_published(): void
    {
        Mail::fake();
        $product = Product::visible()->with('store.user')->first();

        $this->get("/producto/{$product->slug}")->assertOk()->assertSee('Preguntas al vendedor');
        $this->post("/producto/{$product->slug}/preguntas", ['question' => '¿Sirve con el Galaxy S24?', 'name' => 'Laura Gómez', 'email' => 'Laura@Example.com'])
            ->assertRedirect()->assertSessionHas('success');
        $q = ProductQuestion::first();
        $this->assertSame('laura@example.com', $q->email);
        Mail::assertSent(ProductQuestionMail::class, fn ($m) => $m->hasTo($product->store->notificationEmail()));
        // Sin responder no se publica.
        $this->get("/producto/{$product->slug}")->assertDontSee('¿Sirve con el Galaxy S24?');

        $seller = $product->store->user;
        $this->actingAs($seller)->get('/seller-center')->assertSee('1 pregunta sin responder');
        $this->get('/seller-center/preguntas')->assertOk()->assertSee('¿Sirve con el Galaxy S24?');
        $this->post("/seller-center/preguntas/{$q->id}", ['answer' => 'Sí, escríbeme al 300 123 4567'])->assertSessionHasErrors('answer');
        $this->post("/seller-center/preguntas/{$q->id}", ['answer' => 'Sí, funciona con el S24 por Bluetooth 5.3.'])->assertSessionHas('success');
        Mail::assertSent(ProductQuestionAnsweredMail::class, fn ($m) => $m->hasTo('laura@example.com'));

        auth()->logout();
        $this->get("/producto/{$product->slug}")->assertSee('¿Sirve con el Galaxy S24?')->assertSee('Bluetooth 5.3')->assertSee('Laura')->assertDontSee('Gómez')->assertDontSee('laura@example.com');

        // El Backoffice la oculta.
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->get('/backoffice/preguntas')->assertOk()->assertSee('laura@example.com');
        $this->post("/backoffice/preguntas/{$q->id}/visibilidad")->assertSessionHas('success');
        auth()->logout();
        $this->get("/producto/{$product->slug}")->assertDontSee('Bluetooth 5.3');
    }

    public function test_questions_with_contact_data_or_from_bots_are_rejected_and_sellers_only_answer_their_own(): void
    {
        Mail::fake();
        $product = Product::visible()->first();
        $this->post("/producto/{$product->slug}/preguntas", ['question' => 'Me escriben a juan@gmail.com porfa', 'name' => 'Juan', 'email' => 'juan@example.com'])->assertSessionHasErrors('question');
        $this->post("/producto/{$product->slug}/preguntas", ['question' => '¿Lo dejan en 1.200.000?', 'name' => 'Juan', 'email' => 'juan@example.com', 'website' => 'x'])->assertSessionHasErrors('website');
        $this->post("/producto/{$product->slug}/preguntas", ['question' => '¿Lo dejan en 1.200.000?', 'name' => 'Juan', 'email' => 'juan@example.com'])->assertSessionHas('success');
        Mail::assertSentCount(1);

        $q = ProductQuestion::first();
        $other = User::where('role', 'seller')->whereHas('store', fn ($s) => $s->where('id', '!=', $product->store_id))->first();
        $this->actingAs($other)->post("/seller-center/preguntas/{$q->id}", ['answer' => 'Hola'])->assertNotFound();
    }
}
