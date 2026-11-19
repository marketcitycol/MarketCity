<?php

namespace App\Mail;

use App\Models\SellerOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SellerNewOrderMail extends Mailable
{
    public function __construct(public SellerOrder $sellerOrder) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Nuevo pedido pagado para {$this->sellerOrder->store->name}: {$this->sellerOrder->order->number}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.seller-new-order', with: ['so' => $this->sellerOrder->loadMissing('order', 'store', 'items')]);
    }
}
