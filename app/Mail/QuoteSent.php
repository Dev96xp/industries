<?php

namespace App\Mail;

use App\Models\CompanySetting;
use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class QuoteSent extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public CompanySetting $company,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quote '.$this->quote->number.' — '.$this->company->company_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-sent',
            with: [
                'quote' => $this->quote,
                'company' => $this->company,
                'approveUrl' => URL::signedRoute('quotes.approve', ['quote' => $this->quote->id]),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
