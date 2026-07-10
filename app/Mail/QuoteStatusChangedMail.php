<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $quote;
    public $client;
    public $oldStatus;
    public $newStatus;

    public function __construct(Quote $quote, $oldStatus, $newStatus)
    {
        $this->quote = $quote;
        $this->client = $quote->client;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quote Status Updated - ' . $this->quote->invoice_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-status-changed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}