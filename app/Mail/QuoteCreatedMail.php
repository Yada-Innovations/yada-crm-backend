<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $quote;
    public $client;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
        $this->client = $quote->client;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Quote Created - ' . ($this->quote->quote_number ?? $this->quote->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-created',
            with: [
                'quote' => $this->quote,
                'client' => $this->client,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}