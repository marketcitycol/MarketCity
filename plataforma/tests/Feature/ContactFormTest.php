<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Formulario de contacto con copia al remitente (#66). */
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_contact_form_saves_emails_team_and_copy_and_backoffice_marks_it(): void
    {
        Mail::fake();
        $this->get('/contacto')->assertOk()->assertSee('Escríbenos')->assertSee('Enviarme una copia');
        $this->post('/contacto', ['name' => 'Ana Ruiz', 'email' => 'Ana@Example.com', 'topic' => 'compra', 'order_number' => 'mc-010001', 'message' => 'No me ha llegado la guía de mi pedido.', 'copy' => '1'])
            ->assertRedirect()->assertSessionHas('success');
        $c = ContactMessage::first();
        $this->assertSame('ana@example.com', $c->email);
        $this->assertSame('MC-010001', $c->order_number);
        Mail::assertSent(ContactMessageMail::class, fn ($m) => $m->hasTo('ana@example.com') && $m->copy);
        Mail::assertSent(ContactMessageMail::class, fn ($m) => ! $m->copy && $m->hasReplyTo('ana@example.com'));

        $this->post('/contacto', ['name' => 'Bot', 'email' => 'bot@example.com', 'topic' => 'otro', 'message' => 'Compra seguidores baratos', 'website' => 'http://spam'])->assertSessionHasErrors('website');
        $this->post('/contacto', ['name' => 'Sin copia', 'email' => 'luis@example.com', 'topic' => 'vender', 'message' => 'Quiero vender mis productos.', 'copy' => '0']);
        Mail::assertNotSent(ContactMessageMail::class, fn ($m) => $m->hasTo('luis@example.com'));

        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->get('/backoffice/contacto')->assertOk()->assertSee('No me ha llegado la guía');
        $this->post("/backoffice/contacto/{$c->id}")->assertSessionHas('success');
        $this->assertNotNull($c->fresh()->handled_at);
        $this->get('/backoffice/contacto')->assertDontSee('No me ha llegado la guía');
    }

    public function test_bnpl_page_with_simulator_and_honest_notice(): void
    {
        $this->get('/compra-ahora-paga-despues')->assertOk()->assertSee('Simula tu cuota')->assertSee('$ 50.000')
            ->assertSee('llegan muy pronto al pago');
    }
}
