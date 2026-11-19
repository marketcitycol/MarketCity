<?php

namespace App\Mail;

use App\Models\Pqr;
use App\Models\Store;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Aviso interno (equipo MarketCity) o a la tienda del pedido. A la tienda no se le envían datos personales. */
class PqrNoticeMail extends Mailable
{
    public function __construct(public Pqr $pqr, public bool $forSeller, public ?Store $store = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->forSeller
            ? "{$this->pqr->typeLabel()} sobre el pedido {$this->pqr->order_number}"
            : "Nueva PQRS {$this->pqr->radicado}: {$this->pqr->typeLabel()}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.pqr-notice', with: ['pqr' => $this->pqr, 'forSeller' => $this->forSeller, 'store' => $this->store]);
    }
}
