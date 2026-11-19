<?php

namespace App\Mail;

use App\Models\Pqr;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PqrAnsweredMail extends Mailable
{
    public function __construct(public Pqr $pqr) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Respuesta a tu solicitud {$this->pqr->radicado}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.pqr-answered', with: ['pqr' => $this->pqr]);
    }
}
