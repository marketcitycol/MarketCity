<?php

namespace App\Mail;

use App\Models\EmailPriceCoupon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EmailPriceMail extends Mailable
{
    public function __construct(public EmailPriceCoupon $coupon) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tu precio {$this->coupon->campaign->name}: {$this->coupon->product->name}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.email-price', with: ['coupon' => $this->coupon]);
    }
}
