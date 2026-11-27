<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Bienvenida a una cuenta nueva (en WooCommerce: «Cuenta nueva»). Nunca incluye la contraseña. */
class WelcomeMail extends Mailable
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bienvenido a MarketCity');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.welcome', with: ['user' => $this->user]);
    }
}
