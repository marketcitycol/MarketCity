<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\Payments\BankTransferGateway;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderPlacedMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Recibimos tu pedido {$this->order->number}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order-placed', with: [
            'order' => $this->order->loadMissing('sellerOrders.store', 'sellerOrders.items'),
            'transfer' => $this->order->awaitingTransfer() ? BankTransferGateway::accountDetails() : null,
            'deadline' => BankTransferGateway::deadlineHours(),
        ]);
    }
}
