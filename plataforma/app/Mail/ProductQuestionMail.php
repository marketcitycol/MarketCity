<?php

namespace App\Mail;

use App\Models\ProductQuestion;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A la tienda: un cliente preguntó por uno de sus productos. */
class ProductQuestionMail extends Mailable
{
    public function __construct(public ProductQuestion $question) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Te preguntaron por «'.$this->question->product->name.'»');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.product-question', with: ['q' => $this->question->loadMissing('product', 'store')]);
    }
}
