<?php

namespace App\Mail;

use App\Models\StoreBankAccount;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BankAccountChangeRequestedMail extends Mailable
{
    public function __construct(public StoreBankAccount $account, public bool $forSeller) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->forSeller
            ? 'Solicitaste cambiar la cuenta bancaria de tu tienda'
            : "Revisar cambio de cuenta bancaria: {$this->account->store->name}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.bank-account-change', with: ['account' => $this->account->loadMissing('store'), 'forSeller' => $this->forSeller]);
    }
}
