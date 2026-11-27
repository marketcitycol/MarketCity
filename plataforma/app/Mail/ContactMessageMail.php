<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Mensaje de contacto: al equipo (con «responder a» el cliente) o la copia para quien escribió. */
class ContactMessageMail extends Mailable
{
    public function __construct(public ContactMessage $contact, public bool $copy = false) {}

    public function envelope(): Envelope
    {
        return $this->copy
            ? new Envelope(subject: 'Recibimos tu mensaje · MarketCity')
            : new Envelope(subject: "Contacto: {$this->contact->topicLabel()} · {$this->contact->name}", replyTo: [new Address($this->contact->email, $this->contact->name)]);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-message', with: ['c' => $this->contact, 'copy' => $this->copy]);
    }
}
