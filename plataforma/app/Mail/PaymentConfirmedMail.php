<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentConfirmedMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pago confirmado: pedido {$this->order->number}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.payment-confirmed', with: ['order' => $this->order->loadMissing('sellerOrders.store', 'sellerOrders.items')]);
    }
}
