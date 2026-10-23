<?php

namespace App\Mail;

use App\Models\CompanySetting;
use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSent extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public CompanySetting $company,
        public ?string $payUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice '.$this->quote->invoice_number.' — '.$this->company->company_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-sent',
            with: [
                'quote' => $this->quote,
                'company' => $this->company,
                'payUrl' => $this->payUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
