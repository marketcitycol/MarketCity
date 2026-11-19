<?php

namespace App\Mail;

use App\Models\SellerOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderShippedMail extends Mailable
{
    public function __construct(public SellerOrder $sellerOrder) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tu pedido {$this->sellerOrder->order->number} va en camino");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order-shipped', with: ['so' => $this->sellerOrder->loadMissing('order', 'store', 'items')]);
    }
}
