<?php

namespace App\Mail;

use App\Models\CompanySetting;
use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteRequestReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QuoteRequest $quoteRequest,
        public string $replyMessage,
        public CompanySetting $company,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: Your Quote Request — '.$this->company->company_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-request-reply',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
