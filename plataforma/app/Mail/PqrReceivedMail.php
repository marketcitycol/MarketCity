<?php

namespace App\Mail;

use App\Models\Pqr;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PqrReceivedMail extends Mailable
{
    public function __construct(public Pqr $pqr) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Radicamos tu solicitud {$this->pqr->radicado}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.pqr-received', with: ['pqr' => $this->pqr]);
    }
}
