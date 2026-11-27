<?php

namespace App\Mail;

use App\Models\ProductQuestion;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Al cliente: la tienda respondió su pregunta. */
class ProductQuestionAnsweredMail extends Mailable
{
    public function __construct(public ProductQuestion $question) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->question->store->name.' respondió tu pregunta');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.product-question-answered', with: ['q' => $this->question->loadMissing('product', 'store')]);
    }
}
