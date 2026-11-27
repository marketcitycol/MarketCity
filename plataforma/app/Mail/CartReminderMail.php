<?php

namespace App\Mail;

use App\Models\AbandonedCart;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

class CartReminderMail extends Mailable
{
    /** @param array<int, array{name:string, quantity:int, price:int}> $lines */
    public function __construct(public AbandonedCart $cart, public array $lines, public string $restoreUrl, public string $unsubscribeUrl, public bool $unpaidOrder = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->unpaidOrder ? 'Tu pedido quedó sin pagar' : 'Dejaste productos en tu carrito');
    }

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click']);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.cart-reminder', with: ['cart' => $this->cart, 'lines' => $this->lines, 'restoreUrl' => $this->restoreUrl, 'unsubscribeUrl' => $this->unsubscribeUrl, 'unpaidOrder' => $this->unpaidOrder]);
    }
}
