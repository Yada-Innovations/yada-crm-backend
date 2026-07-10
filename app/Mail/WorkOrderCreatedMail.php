<?php

namespace App\Mail;

use App\Models\WorkDone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkOrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $workOrder;
    public $client;

    public function __construct(WorkDone $workOrder)
    {
        $this->workOrder = $workOrder;
        $this->client = $workOrder->client;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Work Order Created - ' . $this->workOrder->wo_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.work-order-created',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}